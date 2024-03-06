<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Etailers\Mipago\Model;

use Magento\Payment\Model\MethodInterface;

/**
 * Interface MipagoPaymentInterface
 * @api
 * @since 100.1.0
 */
interface MipagoPaymentInterface extends MethodInterface
{
    const MIPAGO_AUTHORIZE_COMMAND = 'mipago_authorize';

    const MIPAGO_SALE_COMMAND = 'mipago_sale';

    const CAN_AUTHORIZE = 'can_authorize_mipago';

    const CAN_CAPTURE = 'can_capture_mipago';

    /**
     * @return string|null
     * @since 100.1.0
     */
    public function getProviderCode();
}
