<?php

return [
    'bank' => [
        'name' => env('PAYMENT_BANK_NAME', 'BANK BCA'),
        'account_number' => env('PAYMENT_BANK_ACCOUNT', '1234567890'),
        'account_holder' => env('PAYMENT_BANK_HOLDER', 'Mahjong Medan'),
    ],

    'qris' => [
        'image' => env('PAYMENT_QRIS_IMAGE'),
    ],
];
