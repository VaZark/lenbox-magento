<?php

namespace Lenbox\CbnxPayment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CaptureRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        error_log('Loading CaptureRequest', 3,  '/bitnami/magento/var/log/custom_error.log');
        error_log(json_encode($buildSubject), 3,  '/bitnami/magento/var/log/custom_error.log');
        if (
            !isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();


        error_log('Order in CaptureRequest', 3,  '/bitnami/magento/var/log/custom_error.log');
        error_log(json_encode($order), 3,  '/bitnami/magento/var/log/custom_error.log');

        $payment = $paymentDO->getPayment();

        error_log('payment in CaptureRequest', 3,  '/bitnami/magento/var/log/custom_error.log');
        error_log(json_encode($payment), 3,  '/bitnami/magento/var/log/custom_error.log');

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }
        return [
            'TXN_TYPE' => 'S',
            'TXN_ID' => $payment->getLastTransId(),
            'MERCHANT_KEY' => $this->config->getValue(
                'seller_token',
                $order->getStoreId()
            )
        ];
    }
}
