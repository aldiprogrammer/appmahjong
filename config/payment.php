<?php

return [
    'bank' => [
        'name' => env('PAYMENT_BANK_NAME', 'BCA'),
        'account_number' => env('PAYMENT_BANK_ACCOUNT', '1234567890'),
        'account_holder' => env('PAYMENT_BANK_HOLDER', 'Mahjong Club'),
    ],

    'qris' => [
        'image' => env('PAYMENT_QRIS_IMAGE'),
    ],
];
