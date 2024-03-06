<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Etailers\Mipago\Model\Method;

use Exception;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\ConfigFactoryInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Etailers\Mipago\Block\Form;
use Etailers\Mipago\Model\MipagoPaymentInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Mipago
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @api
 * @since 100.1.0
 */
class Mipago implements MipagoPaymentInterface
{
    /**
     * @var string
     */
    private static $activeKey = 'active';

    /**
     * @var string
     */
    private static $titleKey = 'title';

    /**
     * @var ConfigFactoryInterface
     */
    private $configFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var MethodInterface
     */
    private $mipagoProvider;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var ValueHandlerPoolInterface
     */
    private $valueHandlerPool;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var Command\CommandManagerPoolInterface
     */
    private $commandManagerPool;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var string
     */
    private $code;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Constructor
     *
     * @param ConfigInterface $config
     * @param ConfigFactoryInterface $configFactory
     * @param ObjectManagerInterface $objectManager
     * @param MethodInterface $mipagoProvider
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param Command\CommandManagerPoolInterface $commandManagerPool
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param string $code
     * @param Json|null $jsonSerializer
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigInterface $config,
        ConfigFactoryInterface $configFactory,
        ObjectManagerInterface $objectManager,
        MethodInterface $mipagoProvider,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        Command\CommandManagerPoolInterface $commandManagerPool,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        $code,
        Json $jsonSerializer = null
    ) {
        $this->config = $config;
        $this->configFactory = $configFactory;
        $this->objectManager = $objectManager;
        $this->valueHandlerPool = $valueHandlerPool;
        $this->mipagoProvider = $mipagoProvider;
        $this->eventManager = $eventManager;
        $this->commandManagerPool = $commandManagerPool;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->code = $code;
        $this->jsonSerializer = $jsonSerializer ?: $this->objectManager->get(Json::class);
    }

    /**
     * Unifies configured value handling logic
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    private function getConfiguredValue($field, $storeId = null)
    {
        $handler = $this->valueHandlerPool->get($field);
        $subject = ['field' => $field];

        return $handler->handle($subject, $storeId ?: $this->getStore());
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getFormBlockType()
    {
        return Form::class;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getTitle()
    {
        return $this->getConfiguredValue(self::$titleKey);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function setStore($storeId)
    {
        $this->storeId = (int)$storeId;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getStore()
    {
        return $this->storeId;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canOrder()
    {
        return false;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canAuthorize()
    {
        return $this->mipagoProvider->canAuthorize()
            && $this->mipagoProvider->getConfigData(static::CAN_AUTHORIZE);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canCapture()
    {
        return $this->mipagoProvider->canCapture()
            && $this->mipagoProvider->getConfigData(static::CAN_CAPTURE);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canCapturePartial()
    {
        return false;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canCaptureOnce()
    {
        return $this->mipagoProvider->canCaptureOnce();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canRefund()
    {
        return false;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canRefundPartialPerInvoice()
    {
        return false;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canVoid()
    {
        return false;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canUseInternal()
    {
        $isInternalAllowed = $this->getConfiguredValue('can_use_internal');
        // if config has't been specified for Mipago, need to check payment provider option
        if ($isInternalAllowed === null) {
            return $this->mipagoProvider->canUseInternal();
        }
        return (bool) $isInternalAllowed;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canUseCheckout()
    {
        return $this->mipagoProvider->canUseCheckout();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canEdit()
    {
        return $this->mipagoProvider->canEdit();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canFetchTransactionInfo()
    {
        return false;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function isGateway()
    {
        return $this->mipagoProvider->isGateway();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function isOffline()
    {
        return $this->mipagoProvider->isOffline();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function isInitializeNeeded()
    {
        return $this->mipagoProvider->isInitializeNeeded();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canUseForCountry($country)
    {
        return $this->mipagoProvider->canUseForCountry($country);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->mipagoProvider->canUseForCurrency($currencyCode);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getInfoBlockType()
    {
        return $this->mipagoProvider->getInfoBlockType();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getInfoInstance()
    {
        return $this->mipagoProvider->getInfoInstance();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->mipagoProvider->setInfoInstance($info);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function validate()
    {
        return $this->mipagoProvider->validate();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment instanceof OrderPaymentInterface) {
            throw new \DomainException('Not implemented');
        }
        /** @var $payment OrderPaymentInterface */

        $commandExecutor = $this->commandManagerPool->get(
            $this->mipagoProvider->getCode()
        );

        $commandExecutor->executeByCode(
            MipagoPaymentInterface::MIPAGO_AUTHORIZE_COMMAND,
            $payment,
            [
                'amount' => $amount
            ]
        );

        $payment->setMethod($this->mipagoProvider->getCode());

        return $this;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment instanceof OrderPaymentInterface) {
            throw new \DomainException('Not implemented');
        }
        /** @var $payment Payment */

        if ($payment->getAuthorizationTransaction()) {
            throw new \DomainException('Capture can not be performed through mipago');
        }

        $commandExecutor = $this->commandManagerPool->get(
            $this->mipagoProvider->getCode()
        );

        $commandExecutor->executeByCode(
            MipagoPaymentInterface::MIPAGO_SALE_COMMAND,
            $payment,
            [
                'amount' => $amount
            ]
        );

        $payment->setMethod($this->mipagoProvider->getCode());
    }

    /**
     * Returns Payment's extension attributes.
     *
     * @param OrderPaymentInterface $payment
     * @return \Magento\Sales\Api\Data\OrderPaymentExtensionInterface
     */
    private function getPaymentExtensionAttributes(OrderPaymentInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function canReviewPayment()
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function acceptPayment(InfoInterface $payment)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function denyPayment(InfoInterface $payment)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->getConfiguredValue($field, $storeId);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->eventManager->dispatch(
            'payment_method_assign_data_mipago',
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE => $data
            ]
        );

        $this->eventManager->dispatch(
            'payment_method_assign_data_mipago_' . $this->getProviderCode(),
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE => $data
            ]
        );

        return $this->mipagoProvider->assignData($data);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return $this->mipagoProvider->isAvailable($quote)
            && $this->config->getValue(self::$activeKey, $this->getStore() ?: ($quote ? $quote->getStoreId() : null));
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function isActive($storeId = null)
    {
        return $this->mipagoProvider->isActive($storeId)
            && $this->config->getValue(self::$activeKey, $this->getStore() ?: $storeId);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function initialize($paymentAction, $stateObject)
    {
        throw new \DomainException("Not implemented");
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getConfigPaymentAction()
    {
        return $this->mipagoProvider->getConfigPaymentAction();
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function getProviderCode()
    {
        return $this->mipagoProvider->getCode();
    }
}
