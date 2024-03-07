<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Etailers\Mipago\Model;

/**
 *
 */
class Mipago extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CUSTOM_MIPAGO_CODE = 'mipago';
    const PAYMENT_METHOD_CUSTOM_MIPAGO_FEES = 'payment/mipago/fees';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CUSTOM_MIPAGO_CODE;
}
