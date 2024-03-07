<?php
namespace Etailers\Mipago\Observer;

use Etailers\Mipago\Model\Mipago;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ModifyQuoteTotals implements ObserverInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Este observer es el encargado de comprobar el PM utilizado en el carrito, y si corresponde sumar el recargo.
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();

        try {
            $payment = $quote->getPayment();
            $method = $payment->getMethod();
        } catch (\Exception) {
            return $this;
        }

        if ($method == Mipago::PAYMENT_METHOD_CUSTOM_MIPAGO_CODE) {
            $fees = $this->scopeConfig->getValue(Mipago::PAYMENT_METHOD_CUSTOM_MIPAGO_FEES);

            if ($quote->getSubtotal() > 0) {
                $quote->setSubtotal($quote->getSubtotal() + $fees);
            }

            if ($quote->getGrandtotal() > 0) {
                $quote->setGrandTotal($quote->getGrandtotal() + $fees);
            }
        }

        return $this;
    }
}
