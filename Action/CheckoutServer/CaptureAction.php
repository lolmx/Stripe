<?php

namespace Payum\Stripe\Action\CheckoutServer;

use League\Uri\Http as HttpUri;
use League\Uri\Modifiers\MergeQuery;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Sync;
use Payum\Stripe\Request\Api\CreateSession;
use Payum\Stripe\Request\Api\RedirectToCheckoutServer;
use Stripe\Checkout\Session;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        /* @var $request Capture */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());
        if (isset($httpRequest->query['cancelled'])) {
            $model['CANCELLED'] = true;
            $this->gateway->execute(new Sync($model));

            return;
        }

        if (!$model->offsetExists('object')) {
            $model['success_url'] = $request->getToken()->getTargetUrl();
            $model['cancel_url'] = $this->generateCancelUrl($request->getToken()->getTargetUrl());

            $this->gateway->execute(new CreateSession($model));
            $this->gateway->execute(new RedirectToCheckoutServer($model));
        } elseif ($model['object'] === Session::OBJECT_NAME) {
            $modelClone = clone $model;
            $this->gateway->execute(new Sync($modelClone));
            $this->gateway->execute($status = new GetHumanStatus($modelClone));

            if ($status->isPending()) {
                $this->gateway->execute(new RedirectToCheckoutServer($model));
            }
        }

        $this->gateway->execute(new Sync($model));
    }

    protected function generateCancelUrl(string $url): string
    {
        $cancelUrl = HttpUri::createFromString($url);
        $modifier = new MergeQuery('cancelled=1');
        $cancelUrl = $modifier->process($cancelUrl);

        return (string)$cancelUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
