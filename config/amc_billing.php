<?php

return [
    'bank' => [
        'Account name' => env('AMC_BILL_ACCOUNT_NAME'),
        'Bank name' => env('AMC_BILL_BANK_NAME'),
        'Account number' => env('AMC_BILL_ACCOUNT_NUMBER'),
        'IFSC' => env('AMC_BILL_IFSC'),
        'Branch' => env('AMC_BILL_BANK_BRANCH'),
    ],
    'payment_qr_url' => env('AMC_BILL_PAYMENT_QR_URL'),
];
