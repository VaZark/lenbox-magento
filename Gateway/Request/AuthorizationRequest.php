<?php

namespace Lenbox\CbnxPayment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Lenbox\CbnxPayment\Helper\Data as Lenbox;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Lenbox
     */
    private $lenbox;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Session
     */
    private $session;

    /**
     * AuthorizationRequest constructor.
     * @param ConfigInterface $config
     * @param Lenbox $lenbox
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(
        ConfigInterface $config,
        Lenbox $lenbox,
        LoggerInterface $logger,
        Session $session
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->lenbox = $lenbox;
        $this->session = $session;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order   = $payment->getOrder();
        $address = $order->getShippingAddress();

        $version = $this->lenbox->getVersion();
        $expiresAt = $this->lenbox->getExpiresAt($order);
        $incrementId = $order->getOrderIncrementId();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->session->getQuote();

        /**
         * @todo pegar
         * order id
         */
        $orderId = $order->getId();

        return [
            'TXN_TYPE'      => 'A',
            'referenceId'   => $incrementId,
            'callbackUrl'   => $this->lenbox->getCallbackUrl(),
            'returnUrl'     => $this->lenbox->getReturnUrl($orderId),
            'value'         => round($order->getGrandTotalAmount(), 2),
            'buyer'         => $this->lenbox->getBuyer($order, $quote),
            'plugin'        => "Magento 2". $version,
            'api_url'       => $this->lenbox->getApiUrl("/payments"),
            'expiresAt'     => $expiresAt
        ];
    }
}
