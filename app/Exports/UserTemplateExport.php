<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class UserTemplateExport implements FromArray, WithHeadings, WithTitle, WithStrictNullComparison, WithColumnFormatting
{
    public function array(): array
    {
        return [
            // Example row (can be deleted)
            [
                'jhandey',              // username
                '111_112',              // externalSystemId
                '1234567',              // barcode
                'true',                 // active
                'staff',                // patronGroup
                'Handey',               // personal.lastName
                'Jack',                 // personal.firstName
                'Michael',              // personal.middleName
                'Jackie',               // personal.preferredFirstName
                'john.doe@university.edu', // personal.email
                '+36 55 230 348',       // personal.phone
                '+36 55 379 130',       // personal.mobilePhone
                '1995-10-10',           // personal.dateOfBirth
                'HU',                   // personal.addresses.countryId
                'Andrássy Street 1.',   // personal.addresses.addressLine1
                '',                     // personal.addresses.addressLine2
                'Budapest',             // personal.addresses.city
                'Pest',                 // personal.addresses.region
                '1061',                 // personal.addresses.postalCode
                'Home',                 // personal.addresses.addressTypeId
                'true',                 // personal.addresses.primaryAddress
                'mail',                 // personal.preferredContactTypeId
                '2017-01-01',           // enrollmentDate
                '2019-01-01',           // expirationDate
                'true',                 // requestPreference.holdShelf
                'true',                 // requestPreference.delivery
                '3a40852d-49fd-4df2-a1f9-6e2641a6e91f', // requestPreference.defaultServicePointId (updated to Circ Desk 1)
                'Home',                 // requestPreference.defaultDeliveryAddressTypeId
                'Hold Shelf',           // requestPreference.fulfillment
                'Accounting',           // departments
                'true',                 // deactivateMissingUsers
                'false',                // updateOnlyPresentFields
                'test',                 // sourceType
            ],
            // Empty template row
            array_fill(0, 32, ''),
        ];
    }

    public function headings(): array
    {
        return [
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
    }

    public function columnFormats(): array
    {
        return [
            'M' => NumberFormat::FORMAT_DATE_YYYYMMDD, // personal.dateOfBirth
            'W' => NumberFormat::FORMAT_DATE_YYYYMMDD, // enrollmentDate
            'X' => NumberFormat::FORMAT_DATE_YYYYMMDD, // expirationDate
        ];
    }

    public function title(): string
    {
        return 'User Data';
    }
}