<?php

return [
    'acceptance_fee' => (float) env('PAYMENT_ACCEPTANCE_FEE', 50.00),

    'paypal' => [
        'approval_url' => env('PAYPAL_APPROVAL_URL', 'https://www.sandbox.paypal.com/checkoutnow'),
    ],
];
