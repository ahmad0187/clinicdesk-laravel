<?php

// Details printed on invoices. Override with environment variables.
return [
    'address' => env('CLINIC_ADDRESS', '214 Maple Avenue, Suite 3, Springfield'),
    'phone' => env('CLINIC_PHONE', '(555) 014-2290'),
    'email' => env('CLINIC_EMAIL', 'billing@clinicdesk.test'),
];
