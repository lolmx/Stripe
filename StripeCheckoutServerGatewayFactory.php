<?php

namespace Payum\Stripe;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayFactory;
use Payum\Stripe\Action\CheckoutServer\CaptureAction;
use Payum\Stripe\Action\CheckoutServer\ConvertPaymentAction;
use Payum\Stripe\Action\CheckoutServer\StatusAction;
use Payum\Stripe\Action\CheckoutServer\SyncAction;
use Payum\Stripe\Action\Api\CreateSessionAction;
use Payum\Stripe\Action\Api\RetrievePaymentIntentAction;
use Stripe\Stripe;

class StripeCheckoutServerGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        if (
            false === class_exists(Stripe::class) ||
            $this->parseStripeVersion(Stripe::VERSION)['major'] !== 6 ||
            $this->parseStripeVersion(Stripe::VERSION)['minor'] < 9
        ) {
            throw new LogicException('You must install "stripe/stripe-php:^6.9" library.');
        }

        $config->defaults([
            'payum.factory_name' => 'stripe_checkout_server',
            'payum.factory_title' => 'Stripe Checkout Server',

            'payum.template.redirect_session_to_checkout' => '@PayumStripe/Action/redirect_session_to_checkout.html.twig',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.sync' => new SyncAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.create_session' => function (ArrayObject $config) {
                return new CreateSessionAction($config['payum.template.redirect_session_to_checkout']);
            },
            'payum.action.retrieve_payment_intent' => new RetrievePaymentIntentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'publishable_key' => '',
                'secret_key' => ''
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['publishable_key', 'secret_key'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Keys($config['publishable_key'], $config['secret_key']);
            };
        }

        $config['payum.paths'] = array_replace([
            'PayumStripe' => __DIR__.'/Resources/views',
        ], $config['payum.paths'] ?: []);
    }

    protected function parseStripeVersion(string $version): array
    {
        $parsed = explode('.', $version);

        $parsedVersion = [];
        $parsedVersion['major'] = (int)$parsed[0];
        $parsedVersion['minor'] = $parsed[1] ? (int)$parsed[1] : null;
        $parsedVersion['patch'] = $parsed[2] ? (int)$parsed[2] : null;

        return $parsedVersion;
    }
}
