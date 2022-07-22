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

        // Fetching from settings
        $use_test = $this->scopeConfig->getValue('payment/lenbox_standard/test_mode', ScopeInterface::SCOPE_STORE);
        $base_url = $use_test ?  "https://app.finnocar.com/version-test/api/1.1/wf" : "https://app.finnocar.com/api/1.1/wf";
        $authkey_field = 'payment/lenbox_standard/' . ($use_test ? 'test_auth_key' : 'live_auth_key');
        $clientid_field = 'payment/lenbox_standard/' . ($use_test ? 'test_client_id' : 'live_client_id');
        $authkey =   $this->scopeConfig->getValue($authkey_field, ScopeInterface::SCOPE_STORE);
        $client_id = $this->scopeConfig->getValue($clientid_field, ScopeInterface::SCOPE_STORE);

        /** @var \Magento\Quote\Model\Quote $quote ID */
        $cartID = $this->session->getQuote()->getId();

        // TODO : Fetch from settings
        $selected_options = array("FLOA_3XG", "3XP", "FLOA_10XP");

        $total = round($order->getGrandTotalAmount(), 2);

        $params = [
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

        error_log("Params for getFormSplit" . json_encode($params), 3, "/bitnami/magento/var/log/custom_error.log");

        return $params;
    }
}
