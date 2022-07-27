<?php

namespace Lenbox\CbnxPayment\Controller\Standard;


use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Sale\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;

class Failed extends \Magento\Framework\App\Action\Action
{

    protected $orderRepository;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        QuoteFactory $quoteFactory,
        Cart $cart,
        ProductFactory $product,
        Collection $salesorder,
        OrderRepositoryInterface $orderRepository,
        ResultFactory $resultFactory,
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->cart = $cart;
        $this->product = $product;
        $this->request = $request;
        $this->salesorder = $salesorder;
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;

        parent::__construct($context);
    }

    /**
     * Success action to show inside iframe on return url Lenbox
     */
    public function execute()
    {

        $quote_id = $this->request->getParam('product_id');
        $orderObjArr = $this->salesorder->addFieldToFilter('quote_id', $quote_id)->getData();
        if (count($orderObjArr) != 1) {
            return;
        }
        $order_id = $orderObjArr[0]['entity_id'];
        try {

            $order = $this->orderRepository->get($order_id);
            $quote = $this->quoteFactory->create()->load($quote_id);
            $quote->setIsActive(1)->setReservedOrderId(null);
            $this->quoteRepository->save($quote);

            $order->setStatus(Order::STATE_CANCELED);
            $order->setState(Order::STATE_CANCELED);
            $order->save();
        } catch (\Exception $e) {
            // Redirect to checkout
            $this->messageManager->addError("Return failed when returning from Lenbox" . json_encode($e));
        }

        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl('/checkout');
        return $redirect;
    }
}
