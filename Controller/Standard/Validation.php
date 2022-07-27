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
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\ResourceModel\Sale\Collection;

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
        Collection $salesorder,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->curl = $curl;
        $this->salesorder = $salesorder;
        parent::__construct($context);
    }

    private function callFormStatus(&$data, $order)
    {


        $use_test = $this->scopeConfig->getValue('payment/lenbox_standard/test_mode', ScopeInterface::SCOPE_STORE);
        $quote_id = $order->getQuoteId();

        $authkey_field = 'payment/lenbox_standard/' . ($use_test ? 'test_auth_key' : 'live_auth_key');
        $clientid_field = 'payment/lenbox_standard/' . ($use_test ? 'test_client_id' : 'live_client_id');
        $base_url = $use_test ?  "https://app.finnocar.com/version-test/api/1.1/wf" : "https://app.finnocar.com/api/1.1/wf";
        $url = $base_url . "/getformstatus";

        $params = array(
            'vd' => $this->scopeConfig->getValue($clientid_field, ScopeInterface::SCOPE_STORE),
            'authkey' => $this->scopeConfig->getValue($authkey_field, ScopeInterface::SCOPE_STORE),
            'productId' => $quote_id,
        );

        $this->curl->addHeader("Content-Type", "application/json");

        $attempt = 0;
        $http_success = false;
        do {
            try {
                $attempt += 1;
                $this->curl->post($url, json_encode($params));
                $status_code = $this->curl->getStatus();
                if ($status_code == 200) {
                    $http_success = true;
                    break;
                }
            } catch (\Throwable $th) {
            }
        } while ($attempt < 4);

        // Connection error
        if (!$http_success) {
            $data['has_error'] = true;
            $data['status'] = "CONNECTION_ERROR";
            $data['action_details'] = "Error invoking getformstatus for productId " . $quote_id;
            return;
        }

        $response = json_decode($this->curl->getBody(), false);

        if ($response->status == "success") {
            if ($response->response->accepted) {
                $order->setStatus(Order::STATE_PROCESSING);
                $order->setState(Order::STATE_PROCESSING);
                $data['has_error'] = false;
                $data['status'] = "SUCCESS";
                $data['action_details'] = 'Created new order for the Quote ID ' . $quote_id;
            } else {
                // Rejected by Lenbox
                $order->setStatus(Order::STATE_CANCELED);
                $order->setState(Order::STATE_CANCELED);
                $data['has_error'] = false;
                $data['status'] = "FAILED";
                $data['action_details'] = 'Canceling order no.' . $order->getId() . ' due to rejection for the Quote ID ' . $quote_id;
            }
            $order->save();
        } else {
            // Unexpected Error (usually config errors)
            $data['has_error'] = true;
            $data['status'] = "ERROR";
            $data['action_details'] = $response->message ?? json_encode($response);
        }
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
        // error_log("Fetched productID from URL " . json_encode($product_id), 3, "/bitnami/magento/var/log/custom_error.log");

        $order = $this->validate_quote($data, $product_id);
        if (!$data['has_error']) {
            $this->callFormStatus($data, $order);
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData($data);
    }

    private function validate_quote(&$data, $product_id)
    {

        $order = null;
        $quote = $this->quoteFactory->create()->load($product_id);

        if (!boolval($quote->getId())) {
            // Invalid input
            $data['has_error'] = true;
            $data['status'] = (!$product_id) ? 'MISSING_ID' : "INVALID_ID";
            $data['action_details'] = 'Invalid Quote ID ' . $product_id;
            return;
        }

        try {
            $orderObjArr = $this->salesorder->addFieldToFilter('quote_id', $product_id)->getData();
            // If order is in payment review state or is a Lenbox Order, select that instance
            foreach ($orderObjArr as $orderObj) {
                $order_id = $orderObj['entity_id'];
                $current_order = $this->orderRepository->get($order_id);
                $order_state = $current_order->getState();
                $methodTitle = $current_order->getPayment()->getMethodInstance()->getTitle();
                if ($order_state === Order::STATE_PAYMENT_REVIEW && $methodTitle === "Lenbox CBNX") {
                    $order = $current_order;
                    break;
                }
            }
            if (empty($order)) {
                $data['has_error'] = true;
                $data['status'] = 'NO_PERFECT_MATCH';
                $data['action_details'] = 'There exists no lenbox order for the Quote ID ' . $product_id . ' which is in a pending state';
                return;
            }
        } catch (\Throwable $th) {
            $data['has_error'] = true;
            $data['status'] = 'MISSING_ORDER';
            $data['action_details'] = 'Order does not exist for Quote ID ' . $product_id;
            return;
        }

        return $order;
    }
}
