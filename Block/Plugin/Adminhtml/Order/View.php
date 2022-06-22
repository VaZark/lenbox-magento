<?php

namespace Lenbox\CbnxPayment\Block\Plugin\Adminhtml\Order;

use \Magento\Backend\Model\UrlInterface;

class View
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * View constructor.
     * @param UrlInterface $urlBuilder
     */
    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $view
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $isLenbox = $view->getOrder()->getPayment()->getMethod() == \Lenbox\CbnxPayment\Model\Ui\ConfigProvider::CODE;
    
        if($isLenbox) {
            $message = __('Are you sure you want to Sync Lenbox Transaction?');
            $url = $this->urlBuilder->getUrl(
                'Lenbox_CbnxPayment/consult/index',
                ['order_id' => $view->getOrderId()]
            );
    
            $view->addButton(
                'lenbox_sync',
                [
                    'label' => __('Sync Lenbox Transaction'),
                    'class' => 'go',
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')"
                ]
            );
        }
    }
}