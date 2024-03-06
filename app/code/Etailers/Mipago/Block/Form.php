<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Etailers\Mipago\Block;

use Magento\Framework\View\Element\Template\Context;

/**
 * Class Form
 */
class Form extends \Magento\Payment\Block\Form
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        $this->createMipagoBlocks();
        return $this;
    }

    /**
     * Create block for own configuration for each payment token
     *
     * @return void
     */
    protected function createMipagoBlocks()
    {
        /*$icons = $this->cardConfigProvider->getIcons();
        $payments = $this->tokensProvider->getTokensComponents($this->_nameInLayout);
        foreach ($payments as $key => $payment) {
            $this->addChild(
                $key,
                $payment->getName(),
                array_merge(
                    ['id' => $this->_nameInLayout . $key, 'icons' => $icons],
                    $payment->getConfig()
                )
            );
        }*/
    }
}
