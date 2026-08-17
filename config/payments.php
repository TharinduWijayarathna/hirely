<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Require Payments
    |--------------------------------------------------------------------------
    |
    | When true, paid plans go through Stripe and feature limits are enforced.
    | Set PAYMENTS_REQUIRED=false to skip the payment gateway so local and
    | demo environments can use paid features without checkout.
    |
    */

    'required' => filter_var(env('PAYMENTS_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),

];
