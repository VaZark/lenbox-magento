<?php

namespace Lenbox\CbnxPayment\Model\Source;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Lenbox\CbnxPayment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * Mode constructor.
     *
     * @param \Lenbox\CbnxPayment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Lenbox\CbnxPayment\Helper\Data $paymentHelper
    ) {
        $this->_paymentHelper = $paymentHelper;
    }
    
    public function toOptionArray()
    {
        /** @var \Lenbox\CbnxPayment\Helper\Data $lenboxHelper */
        $lenboxHelper = $this->_paymentHelper;

        return [
            ['value' => $lenboxHelper::ONPAGE_MODE, 'label' => 'On Page'],
            ['value' => $lenboxHelper::IFRAME_MODE, 'label' => 'Iframe'],
            ['value' => $lenboxHelper::REDIRECT_MODE, 'label' => 'Redirect']
        ];
    }
}
