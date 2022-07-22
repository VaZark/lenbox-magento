<?php

namespace Lenbox\CbnxPayment\Controller\Standard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use  Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;

class Validation extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    protected $_pageFactory;
    protected $orderRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        CartManagementInterface $quoteManagement,
        QuoteFactory $quoteFactory,
        Curl $curl,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->curl = $curl;
        parent::__construct($context);
    }

    private function callFormStatus($data, $product_id)
    {

        $use_test = $this->scopeConfig->getValue('payment/lenbox_standard/test_mode', ScopeInterface::SCOPE_STORE);

        $authkey_field = 'payment/lenbox_standard/' . ($use_test ? 'test_auth_key' : 'live_auth_key');
        $clientid_field = 'payment/lenbox_standard/' . ($use_test ? 'test_client_id' : 'live_client_id');
        $base_url = $use_test ?  "https://app.finnocar.com/version-test/api/1.1/wf" : "https://app.finnocar.com/api/1.1/wf";
        $url = $base_url . "/getformstatus";

        $params = array(
            'vd' => $this->scopeConfig->getValue($clientid_field, ScopeInterface::SCOPE_STORE),
            'authkey' => $this->scopeConfig->getValue($authkey_field, ScopeInterface::SCOPE_STORE),
            'productId' => $product_id,
        );

        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post($url, json_encode($params));
        $result = $this->curl->getBody();

        error_log("Fetched formstatus" . json_encode($result), 3, "/bitnami/magento/var/log/custom_error.log");
        $response = json_decode($result, false);

        if ($response->status == "success") {
            if ($response->response->accepted) {
                $quote = $this->quoteFactory->create()->load($product_id);
                $order = $this->quoteManagement->submit($quote); // creates new order with quote obj
                $order->setStatus(Order::STATE_PROCESSING);
                $order->setState(Order::STATE_PROCESSING);
                $order->save();
                $data['has_error'] = false;
                $data['status'] = "SUCCESS";
                $data['action_details'] = 'Created new order for the Quote ID ' . $product_id;
            } else {
                $data['has_error'] = false;
                $data['status'] = "FAILED";
                $data['action_details'] = 'Not Creating order due to rejection for the Quote ID ' . $product_id;
            }
        } else {
            $data['has_error'] = true;
            $data['status'] = "ERROR";
            $data['action_details'] = $response->message ?? json_encode($response);
        }

        return $data;
    }

    /**
     * View  page action
     * @return ResultInterface
     */
    public function execute()
    {
        $data = [
            'has_error'      => null,
            'err_msg'        => null,
            'status'         => null,
            'action_details' => null,
        ];

        $product_id = $this->request->getParam('product_id');
        error_log("Fetched productID from URL " . json_encode($product_id), 3, "/bitnami/magento/var/log/custom_error.log");

        $quote = $this->quoteFactory->create()->load($product_id);
        $is_valid_quote = boolval($quote->getId());
        // TODO : 
        // 1. Check if it is a Lenbox order
        // 3. Avoid reordering
        // 4. Retry for api call

        if (!$is_valid_quote) {
            $data['has_error'] = true;
            $data['status'] = (!$product_id) ? 'MISSING_ID' : "INVALID_ID";
            $data['action_details'] = 'Invalid Quote ID ' . $product_id;
        } else {
            $data = $this->callFormStatus($data, $product_id);
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($data);
    }
}
