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
        if (
            !isset($buildSubject['payment'])
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
            "authkey" => "1575548537269x551789111684887000",
            "vd" => "1575548961850x942705562301413600",
            "montant" => 773.93,
            "productid" => 6,
            "notification" => "http://localhost:8080/index.php?fc=module&module=lenboxpresta&controller=validation&productid=6",
            "retour" => "http://localhost:8080/index.php?fc=module&module=lenboxpresta&controller=api&productid=6",
            "cancellink" => "http://localhost:8080/index.php?controller=order&step=1",
            "failurelink" => "http://localhost:8080/index.php?controller=order&step=1",
            "integration" => "magento2",
            "paymentoptions" => array("FLOA_3XG", "3XP", "FLOA_10XP"),
        ];
    }
}
