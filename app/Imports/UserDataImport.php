<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UserDataImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Log that the collection method is being called
        \Log::debug('UserDataImport::collection called', ['row_count' => $rows->count()]);

        // Log the raw rows for debugging
        \Log::debug('Raw Excel Rows:', ['rows' => $rows->toArray()]);

        // Validate headers
        if ($rows->isEmpty()) {
            throw new \Exception("Excel file is empty");
        }

        $expectedHeaders = [
            'username*',
            'externalSystemId*',
            'barcode',
            'active',
            'patronGroup*',
            'personal.lastName*',
            'personal.firstName*',
            'personal.middleName',
            'personal.preferredFirstName',
            'personal.email',
            'personal.phone',
            'personal.mobilePhone',
            'personal.dateOfBirth (YYYY-MM-DD)',
            'personal.addresses.countryId',
            'personal.addresses.addressLine1',
            'personal.addresses.addressLine2',
            'personal.addresses.city',
            'personal.addresses.region',
            'personal.addresses.postalCode',
            'personal.addresses.addressTypeId',
            'personal.addresses.primaryAddress',
            'personal.preferredContactTypeId',
            'enrollmentDate (YYYY-MM-DD)',
            'expirationDate (YYYY-MM-DD)',
            'requestPreference.holdShelf',
            'requestPreference.delivery',
            'requestPreference.defaultServicePointId',
            'requestPreference.defaultDeliveryAddressTypeId',
            'requestPreference.fulfillment',
            'departments',
            'deactivateMissingUsers',
            'updateOnlyPresentFields',
            'sourceType',
        ];

        $actualHeaders = $rows->first()->toArray();
        if ($actualHeaders !== $expectedHeaders) {
            throw new \Exception("Excel file headers do not match the expected structure. Expected: " . json_encode($expectedHeaders) . ", Got: " . json_encode($actualHeaders));
        }

        $transformedCollection = $rows->reject(function ($row, $index) {
            // Skip header row and empty rows
            return $index === 0 || empty(array_filter($row->toArray()));
        })->map(function ($row, $index) {
            try {
                $transformedRow = $this->transformRow($row, $index + 1); // +1 to account for header
                // Log the transformed row for debugging
                \Log::debug("Transformed Row {$index}:", ['row' => $transformedRow]);
                return $transformedRow;
            } catch (\Exception $e) {
                Log::error("Row {$index} error: " . $e->getMessage());
                throw new \Exception("Row {$index}: " . $e->getMessage());
            }
        });

        // Log the final transformed collection
        \Log::debug('Transformed Collection:', ['collection' => $transformedCollection->toArray()]);

        return $transformedCollection;
    }

    protected function transformRow($row, $rowNumber)
    {
        $user = [
            'username' => $this->getRequired($row[0] ?? null, 'username', $rowNumber),
            'externalSystemId' => $this->getRequired($row[1] ?? null, 'externalSystemId', $rowNumber),
            'barcode' => $row[2] ?? null,
            'active' => filter_var($row[3] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'patronGroup' => $this->getRequired($row[4] ?? null, 'patronGroup', $rowNumber),
            'personal' => [
                'lastName' => $this->getRequired($row[5] ?? null, 'personal.lastName', $rowNumber),
                'firstName' => $this->getRequired($row[6] ?? null, 'personal.firstName', $rowNumber),
                'middleName' => $row[7] ?? null,
                'preferredFirstName' => $row[8] ?? null,
                'email' => $row[9] ?? null,
                'phone' => $row[10] ?? null,
                'mobilePhone' => $row[11] ?? null,
                'dateOfBirth' => $row[12] ?? null,
                'addresses' => [
                    [
                        'countryId' => $row[13] ?? null,
                        'addressLine1' => $row[14] ?? null,
                        'addressLine2' => $row[15] ?? null,
                        'city' => $row[16] ?? null,
                        'region' => $row[17] ?? null,
                        'postalCode' => $row[18] ?? null,
                        'addressTypeId' => $this->getRequired($row[19] ?? null, 'personal.addresses.addressTypeId', $rowNumber),
                        'primaryAddress' => filter_var($row[20] ?? 'true', FILTER_VALIDATE_BOOLEAN),
                    ],
                ],
                'preferredContactTypeId' => $this->getRequired($row[21] ?? null, 'personal.preferredContactTypeId', $rowNumber),
            ],
            'enrollmentDate' => $row[22] ?? null,
            'expirationDate' => $row[23] ?? null,
            'requestPreference' => [
                'holdShelf' => filter_var($row[24] ?? 'true', FILTER_VALIDATE_BOOLEAN),
                'delivery' => filter_var($row[25] ?? 'true', FILTER_VALIDATE_BOOLEAN),
                'defaultServicePointId' => $row[26] ?? null,
                'defaultDeliveryAddressTypeId' => $this->getRequired($row[27] ?? null, 'requestPreference.defaultDeliveryAddressTypeId', $rowNumber),
                'fulfillment' => $this->getRequired($row[28] ?? null, 'requestPreference.fulfillment', $rowNumber),
            ],
            'departments' => [$row[29] ?? null], // Single value wrapped in array
        ];

        // Additional validation for email if required by the API
        if (empty($user['personal']['email'])) {
            throw new \Exception("personal.email is required (row {$rowNumber})");
        }

        // Validate that departments are not comma-separated
        if (strpos($user['departments'][0] ?? '', ',') !== false) {
            throw new \Exception("departments must be a single value, not comma-separated (row {$rowNumber})");
        }

        return $user;
    }

    protected function getRequired($value, $fieldName, $rowNumber)
    {
        if (empty($value)) {
            throw new \Exception("{$fieldName} is required (row {$rowNumber})");
        }
        return $value;
    }
}