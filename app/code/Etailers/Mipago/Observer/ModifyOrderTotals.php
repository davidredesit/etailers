<?php

namespace Etailers\Mipago\Observer;

use Etailers\Mipago\Model\Mipago;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ModifyOrderTotals implements ObserverInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Este observer es el encargado de comprobar el PM utilizado en el pedido, y si corresponde sumar el recargo.
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        if ($method->getCode() == Mipago::PAYMENT_METHOD_CUSTOM_MIPAGO_CODE) {
            $fees = $this->scopeConfig->getValue(Mipago::PAYMENT_METHOD_CUSTOM_MIPAGO_FEES);

            if ($order->getSubtotal() > 0) {
                $order->setSubtotal($order->getSubtotal() + $fees);
            }

            if ($order->getGrandtotal() > 0) {
                $order->setGrandTotal($order->getGrandtotal() + $fees);
            }
        }

        return $this;
    }
}
