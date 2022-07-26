<?php

namespace Lenbox\CbnxPayment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Lenbox\CbnxPayment\Helper\Data as Lenbox;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

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
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ConfigInterface $config,
        Lenbox $lenbox,
        LoggerInterface $logger,
        Session $session
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->config = $config;
        $this->lenbox = $lenbox;
        $this->session = $session;
    }

    private function get_payment_options($total)
    {
        $payment_option_keys = array(
            '3xg' => 'FLOA_3XG',
            '3xp'  => 'FLOA_3XP',
            '4xg' => 'FLOA_4XG',
            '4xp' => 'FLOA_4XP',
            '10xg' => 'FLOA_10XG',
            '10xp' => 'FLOA_10XP',
        );
        $active_options = array();

        foreach ($payment_option_keys as $key => $value) {
            $base_settings_path = 'payment/lenbox_standard/lenbox_' . $key;
            $is_enabled = $this->scopeConfig->getValue($base_settings_path . '/enable_' . $key, ScopeInterface::SCOPE_STORE);

            if (!$is_enabled) {
                continue;
            }

            $lower_bound = $this->scopeConfig->getValue($base_settings_path . '/min_' . $key, ScopeInterface::SCOPE_STORE);
            $upper_bound = $this->scopeConfig->getValue($base_settings_path . '/max_' . $key, ScopeInterface::SCOPE_STORE);
            // error_log("Lower Bound value " . json_encode($lower_bound), 3,  '/bitnami/magento/var/log/custom_error.log');
            // error_log("Upper Bound value " . json_encode($upper_bound), 3,  '/bitnami/magento/var/log/custom_error.log');

            $is_valid_lower = $lower_bound ? $lower_bound <= $total : true;
            $is_valid_upper = $upper_bound ? $upper_bound >= $total : true;
            if ($is_valid_upper && $is_valid_lower) {
                array_push($active_options, $value);
            }
        }

        return $active_options;
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
        $base_url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $use_test = $this->scopeConfig->getValue('payment/lenbox_standard/test_mode', ScopeInterface::SCOPE_STORE);
        $authkey_field = 'payment/lenbox_standard/' . ($use_test ? 'test_auth_key' : 'live_auth_key');
        $clientid_field = 'payment/lenbox_standard/' . ($use_test ? 'test_client_id' : 'live_client_id');
        $authkey =   $this->scopeConfig->getValue($authkey_field, ScopeInterface::SCOPE_STORE);
        $client_id = $this->scopeConfig->getValue($clientid_field, ScopeInterface::SCOPE_STORE);

        // Calcuated params
        $cartID = $this->session->getQuote()->getId();
        $total = round($order->getGrandTotalAmount(), 2);
        $selected_options = $this->get_payment_options($total);

        $params = [
            "authkey" => $authkey,
            "vd" => $client_id,
            "montant" => $total,
            "productid" => $cartID,
            "notification" => $base_url . "lenbox/standard/validation?product_id=" . $cartID,
            "retour" => $base_url . "lenbox/standard/success/" . $cartID,
            "cancellink" => $base_url . "checkout/cart",
            "failurelink" => $base_url . "checkout/cart",
            "integration" => "magento2",
            "paymentoptions" => $selected_options,
        ];

        // error_log("Params for getFormSplit" . json_encode($params), 3, "/bitnami/magento/var/log/custom_error.log");

        return $params;
    }
}
