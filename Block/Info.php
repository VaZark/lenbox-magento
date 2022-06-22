<?php

namespace Lenbox\CbnxPayment\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Lenbox\CbnxPayment\Gateway\Response\FraudHandler;

class Info extends ConfigurableInfo
{
    protected $_order = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Lenbox\CbnxPayment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var string
     */
    protected $_template = 'Lenbox_CbnxPayment::info.phtml';

    public function __construct(
        Context $context,
        ConfigInterface $config,
        \Magento\Framework\Registry $registry,
        \Lenbox\CbnxPayment\Helper\Data $paymentHelper,
        array $data = []
    )
    {
        parent::__construct($context, $config, $data);
        $this->registry = $registry;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Get order object instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->registry->registry('current_order');
            if (!$this->_order) {
                $info = $this->getInfo();
                if ($this->getInfo() instanceof \Magento\Sales\Model\Order\Payment) {
                    $this->_order = $this->getInfo()->getOrder();
                }
            }
        }
        return $this->_order;
    }

    public function getPaymentUrl()
    {
        $order = $this->getOrder();
        if (is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("paymentUrl");
    }

    public function getCancellationId()
    {
        $order = $this->getOrder();
        if (is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("cancellationId");
    }

    public function getAuthorizationId()
    {
        $order = $this->getOrder();
        if (is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("authorizationId");
    }

    public function getQrcodeSource()
    {
        $order = $this->getOrder();
        if(is_null($order)) {
            return "";
        }
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $order->getPayment();
        return $payment->getAdditionalInformation("qrcode");
    }

    public function getQrcode()
    {
        if ($paymentUrl = $this->getQrcodeSource()) {
            /** @var \Lenbox\CbnxPayment\Helper\Data $lenboxHelper */
            $lenboxHelper = $this->paymentHelper;

            $imageSize = $lenboxHelper->getQrcodeInfoWidth()
                ? $lenboxHelper->getQrcodeInfoWidth()
                : $lenboxHelper::DEFAULT_QRCODE_WIDTH;

            return $lenboxHelper->generateQrCode($paymentUrl, $imageSize);
        }
    }
}