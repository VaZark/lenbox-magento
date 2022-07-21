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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
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

    private function callFormStatus($product_id)
    {
        // TODO : Fetch from settings
        $base_url = "https://app.finnocar.com/version-test/api/1.1/wf";
        $url = $base_url . "/getformstatus";
        $authkey = "1575548537269x551789111684887000";
        $client_id = "1575548961850x942705562301413600";


        $this->curl->addHeader("Content-Type", "application/json");
        $params = array(
            "authkey" => $authkey,
            "vd" => $client_id,
            "productId" => 43,
        );
        $this->curl->post($url, json_encode($params));
        $result = $this->curl->getBody();
        error_log("Fetched formstatus" . json_encode($result), 3, "/bitnami/magento/var/log/custom_error.log");
        $response = json_decode($result, false);
        return $response->response->accepted;
    }

    /**
     * View  page action
     * @return ResultInterface
     */
    public function execute()
    {

        $data = [
            'client_id' => $this->scopeConfig->getValue('payment/lenbox_standard/test_client_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        ];
        // $data = [
        //     'has_error'      => null,
        //     'err_msg'        => null,
        //     'status'         => null,
        //     'action_details' => null,
        // ];

        // $product_id = $this->request->getParam('product_id');
        // error_log("Fetched productID from URL " . json_encode($product_id), 3, "/bitnami/magento/var/log/custom_error.log");

        // // TODO : 
        // // 1. Check if it is a Lenbox order
        // // 2. Avoid reordering
        // // 3. Retry for api call
        // $is_accepted = $this->callFormStatus($product_id);
        // if ($is_accepted) {
        //     $quote = $this->quoteFactory->create()->load($product_id);
        //     $order = $this->quoteManagement->submit($quote); // creates new order with quote obj
        //     $order->setStatus(Order::STATE_PROCESSING);
        //     $order->setState(Order::STATE_PROCESSING);
        //     $order->save();
        //     $data['has_error'] = false;
        //     $data['status'] = "SUCCESS";
        //     $data['action_details'] = 'Created new order for the Quote ID ' . $product_id;
        // } else {
        //     $data['has_error'] = false;
        //     $data['status'] = "FAILED";
        //     $data['action_details'] = 'Not Creating order due to rejection for the Quote ID ' . $product_id;
        // }

        $result = $this->resultJsonFactory->create();
        return $result->setData($data);
    }
}
