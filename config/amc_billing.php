<?php

return [
    'bank' => [
        'Bank name' => env('AMC_BILL_BANK_NAME') ?: 'Bank of Baroda',
        'Account name' => env('AMC_BILL_ACCOUNT_NAME') ?: 'I H H H',
        'Account number' => env('AMC_BILL_ACCOUNT_NUMBER') ?: '77890200001686',
        'IFSC' => env('AMC_BILL_IFSC') ?: 'BARB0VJRARH',
        'Branch' => env('AMC_BILL_BANK_BRANCH'),
    ],
    'payment_qr_url' => env('AMC_BILL_PAYMENT_QR_URL') ?: 'images/amc-payment-qr.png',
];
