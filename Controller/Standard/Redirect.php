<?php

namespace Lenbox\CbnxPayment\Controller\Standard;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Redirect extends \Magento\Framework\App\Action\Action
{

    protected $checkoutSession;

    protected $orderRepository;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;

        parent::__construct($context);
    }

    /**
     * Success action to show inside iframe on return url Lenbox
     */
    public function execute()
    {
        /** @var Order $order */
        $order = $this->getOrder();
        $payment = $order->getPayment();
        if ($payment && $payment->getAdditionalInformation("redirect_url")) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($payment->getAdditionalInformation("redirect_url"));
            // $payment->setIsTransactionClosed(false);
            // $payment->setIsTransactionPending(true);
            return $resultRedirect;
        }
    }

    /**
     * @return mixed
     */
    protected function getRealOrderId()
    {
        return $this->checkoutSession->getLastOrderId();
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        $id = $this->getRealOrderId();
        return $this->orderRepository->get($id);
    }
}
