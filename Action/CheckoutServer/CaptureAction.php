<?php

namespace Payum\Stripe\Action\CheckoutServer;

use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Capture;
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

        if (empty($model['id']) && empty($model['object'])) {
            $model['success_url'] = $request->getToken()->getTargetUrl();
            $model['cancel_url'] = $request->getToken()->getTargetUrl();

            $this->gateway->execute(new CreateSession($model));
            $this->gateway->execute(new RedirectToCheckoutServer($model));
        } elseif ($model->offsetExists('object') && $model['object'] === Session::OBJECT_NAME && !empty($model['id'])) {
            $this->gateway->execute(new RedirectToCheckoutServer($model));
        }

        $this->gateway->execute(new Sync($model));
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
