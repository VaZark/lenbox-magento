<?php

namespace Lenbox\CbnxPayment\Controller\Standard;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use  Magento\Sales\Api\OrderRepositoryInterface;

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
        OrderRepositoryInterface $orderRepository
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
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

        $orderId = $this->request->getParam('product_id');
        error_log("Fetched productID from URL " . json_encode($orderId), 3, "/bitnami/magento/var/log/custom_error.log");

        $order = $this->orderRepository->get($orderId);
        $payment = $order->getPayment();
        $amount = $payment->getAmountOrdered() ?? null;
        error_log("Payment obj " . json_encode($amount), 3, "/bitnami/magento/var/log/custom_error.log");

        $payment->registerAuthorizationNotification($amount);
        $payment->registerCaptureNotification($amount);


        $result = $this->resultJsonFactory->create();

        $data['has_error'] = false;
        $data['status'] = "SUCCESS";

        return $result->setData($data);
    }
}
