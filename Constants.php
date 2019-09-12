<?php
namespace Payum\Stripe;

class Constants
{
    const STATUS_REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    const STATUS_REQUIRES_CONFIRMATION = 'requires_confirmation';
    const STATUS_REQUIRES_ACTION = 'requires_action';
    const STATUS_PROCESSING = 'processing';
    const STATUS_CANCELED = 'canceled';
    const STATUS_SUCCEEDED = 'succeeded';

    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_AUTHENTICATION_REQUIRED = 'authentication_required';
    const NEXT_ACTION_TYPE = 'use_stripe_sdk';

    private function __construct()
    {
    }
}
