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

        /** @var \Magento\Quote\Model\Quote $quote ID */
        $cartID = $this->session->getQuote()->getId();


        /**
         * @todo pegar
         * order id
         */
        $orderId = $order->getId();

        error_log('Cart ID' . json_encode($cartID), 3,  '/bitnami/magento/var/log/custom_error.log');

        // TODO : Fetch from settings
        $base_url = "http://localhost";
        $authkey = "1575548537269x551789111684887000";
        $client_id = "1575548961850x942705562301413600";
        $selected_options = array("FLOA_3XG", "3XP", "FLOA_10XP");

        $total = round($order->getGrandTotalAmount(), 2);


        return [
            "authkey" => $authkey,
            "vd" => $client_id,
            "montant" => $total,
            "productid" => $cartID,
            "notification" => $base_url . "/lenbox/standard/validation?product_id=" . $cartID,
            "retour" => $base_url . "/lenbox/standard/success?product_id=" . $cartID,
            "cancellink" => $base_url . "/checkout/cart",
            "failurelink" => $base_url . "/checkout/cart",
            "integration" => "magento2",
            "paymentoptions" => $selected_options,
        ];
    }
}
