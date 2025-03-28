<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserTemplateExport;
use App\Imports\UserDataImport;
use App\Services\FolioService;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function downloadTemplate()
    {
        return Excel::download(new UserTemplateExport, 'users-template.xlsx');
    }

    public function processImport(Request $request, FolioService $folio)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        // Check if authenticated
        if (!session('folio_token') || !session('folio_tenant')) {
            return back()->with('error', 'Not authenticated. Please reload the page to authenticate.');
        }

        try {
            // Get valid service points
            $servicePointsResponse = $folio->getServicePoints(
                session('folio_token'),
                session('folio_tenant')
            );
            $validServicePointIds = array_column($servicePointsResponse['servicepoints'] ?? [], 'id');
            $validPickupServicePoints = array_filter($servicePointsResponse['servicepoints'] ?? [], function ($sp) {
                return $sp['pickupLocation'] === true;
            });
            $defaultServicePointId = !empty($validPickupServicePoints)
                ? reset($validPickupServicePoints)['id']
                : null;

            if (!$defaultServicePointId) {
                throw new \Exception('No valid service points with pickupLocation=true found in the system.');
            }

            // Get the collection of users
            $usersCollection = Excel::toCollection(new UserDataImport, $request->file('file'))[0];

            // Log the collection for debugging
            \Log::debug('Users Collection:', ['collection' => $usersCollection->toArray()]);

            // Check if the collection contains associative arrays (transformed data)
            $firstItem = $usersCollection->first();
            if (!is_array($firstItem) || !isset($firstItem['username'])) {
                // The collection contains raw data (indexed arrays), so transform it manually
                \Log::debug('Transforming raw data manually in processImport');
                $userDataImport = new UserDataImport();
                $usersCollection = $userDataImport->collection($usersCollection);
            }

            // Ensure the collection contains associative arrays
            $usersArray = $usersCollection->map(function ($user) use ($validServicePointIds, $defaultServicePointId) {
                // Ensure $user is an array with the expected keys
                if (!is_array($user) || !isset($user['username'])) {
                    throw new \Exception('Invalid user data structure: ' . json_encode($user));
                }

                // Validate defaultServicePointId
                $providedServicePointId = $user['requestPreference']['defaultServicePointId'] ?? null;
                if ($providedServicePointId && !in_array($providedServicePointId, $validServicePointIds)) {
                    \Log::warning("Invalid defaultServicePointId for user {$user['username']}: {$providedServicePointId}. Defaulting to {$defaultServicePointId}.");
                    $user['requestPreference']['defaultServicePointId'] = $defaultServicePointId;
                } elseif (!$providedServicePointId) {
                    $user['requestPreference']['defaultServicePointId'] = $defaultServicePointId;
                }

                $userData = [
                    'username' => $user['username'],
                    'externalSystemId' => $user['externalSystemId'],
                    'barcode' => $user['barcode'],
                    'active' => $user['active'],
                    'patronGroup' => $user['patronGroup'],
                    'personal' => $user['personal'],
                    'enrollmentDate' => $user['enrollmentDate'],
                    'expirationDate' => $user['expirationDate'],
                    'requestPreference' => [
                        'holdShelf' => $user['requestPreference']['holdShelf'],
                        'delivery' => $user['requestPreference']['delivery'],
                        'defaultDeliveryAddressTypeId' => $user['requestPreference']['defaultDeliveryAddressTypeId'],
                        'fulfillment' => $user['requestPreference']['fulfillment'],
                    ],
                    'departments' => $user['departments'],
                ];

                // Include defaultServicePointId (already validated or defaulted)
                $userData['requestPreference']['defaultServicePointId'] = $user['requestPreference']['defaultServicePointId'];

                return $userData;
            })->all();

            // Extract departments for the included section
            $departments = [];
            foreach ($usersArray as $user) {
                // Log each user to confirm structure
                \Log::debug('User Structure:', ['user' => $user]);

                $dept = $user['departments'][0] ?? null; // Single value
                if ($dept && !in_array($dept, array_column($departments, 'name'))) {
                    $departments[] = ['name' => $dept, 'code' => strtoupper(substr($dept, 0, 3))];
                }
            }

            $payload = [
                'users' => array_values($usersArray), // Ensure numeric keys for the users array
                'included' => [
                    'departments' => $departments,
                ],
                'totalRecords' => count($usersArray),
                'deactivateMissingUsers' => filter_var($request->input('deactivateMissingUsers', 'true'), FILTER_VALIDATE_BOOLEAN),
                'updateOnlyPresentFields' => filter_var($request->input('updateOnlyPresentFields', 'false'), FILTER_VALIDATE_BOOLEAN),
                'sourceType' => $request->input('sourceType', 'test'),
            ];

            // Log the payload for debugging
            \Log::debug('Final Payload:', ['payload' => $payload]);

            $response = $folio->importUsers(
                session('folio_token'),
                session('folio_tenant'),
                $payload
            );

            // Check if the import was successful based on failedRecords
            if (isset($response['failedRecords']) && $response['failedRecords'] > 0) {
                throw new \Exception('Users were imported with failures. Details: ' . json_encode($response));
            }

            return back()->with([
                'success' => 'Successfully processed ' . count($usersArray) . ' users',
                'logs' => [
                    'Message' => $response['message'] ?? 'No message provided',
                    'Created Records' => $response['createdRecords'] ?? 0,
                    'Updated Records' => $response['updatedRecords'] ?? 0,
                    'Failed Records' => $response['failedRecords'] ?? 0,
                    'Failed External System IDs' => $response['failedExternalSystemIds'] ?? [],
                    'Total Records' => $response['totalRecords'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
