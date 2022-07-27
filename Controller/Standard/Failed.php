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
use Magento\Quote\Api\CartRepositoryInterface;

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
        CartRepositoryInterface $quoteRepository,
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->cart = $cart;
        $this->product = $product;
        $this->request = $request;
        $this->salesorder = $salesorder;
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;
        $this->quoteRepository = $quoteRepository;

        parent::__construct($context);
    }

    /**
     * Success action to show inside iframe on return url Lenbox
     */
    public function execute()
    {

        $quote_id = $this->request->getParam('product_id');
        try {
            // Reactive old quote
            $quote = $this->quoteFactory->create()->load($quote_id);
            $quote->setIsActive(1)->setReservedOrderId(null);
            $this->quoteRepository->save($quote);
            $this->close_old_order($quote_id);
        } catch (\Exception $e) {
            $this->messageManager->addError("Error reloading cart");
        }

        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl('/checkout');
        return $redirect;
    }

    private function close_old_order($quote_id)
    {
        $orderObjArr = $this->salesorder->addFieldToFilter('quote_id', $quote_id)->getData();
        // Cancel orders for same quote with multiple attempts
        foreach ($orderObjArr as $orderObj) {
            $order_id = $orderObj['entity_id'];
            $order = $this->orderRepository->get($order_id);
            // If not in canceled state
            $order_state = $order->getState();
            if ($order_state == Order::STATE_CANCELED) {
                continue;
            }
            $order->setStatus(Order::STATE_CANCELED);
            $order->setState(Order::STATE_CANCELED);
            $order->save();
        }
    }
}
