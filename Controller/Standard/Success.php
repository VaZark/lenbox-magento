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

class Success extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    protected $_pageFactory;
    protected $orderRepository;

    public function __construct(
        RequestInterface $request,
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        CartManagementInterface $quoteManagement,
        QuoteFactory $quoteFactory,
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context);
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

        // TODO : 
        // 1. Check Lenbox order
        // 2. Invoke getformstatus

        $quote = $this->quoteFactory->create()->load($product_id);
        $order = $this->quoteManagement->submit($quote); // creates new order with quote obj
        $payment = $order->getPayment();
        $amount = $payment->getAmountOrdered() ?? null;
        $payment->registerAuthorizationNotification($amount);
        $payment->registerCaptureNotification($amount);
        $order->setStatus(Order::STATE_PROCESSING);
        $order->setState(Order::STATE_PROCESSING);
        $order->save();


        $data['has_error'] = false;
        $data['status'] = "SUCCESS";

        $result = $this->resultJsonFactory->create();
        return $result->setData($data);
    }
}
