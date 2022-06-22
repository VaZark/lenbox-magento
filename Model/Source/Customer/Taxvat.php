<?php

namespace Lenbox\CbnxPayment\Model\Source\Customer;

class Taxvat
{
    /**
     * @var \Lenbox\CbnxPayment\Helper\Data
     */
    protected $paymentHelper;

    public function __construct(
        \Lenbox\CbnxPayment\Helper\Data $paymentHelper
    ) {
        $this->paymentHelper = $paymentHelper;
    }
    
    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Lenbox\CbnxPayment\Helper\Data $lenboxHelper */
        $lenboxHelper = $this->paymentHelper;
        $fields = $lenboxHelper->getFields('customer');

        $options = array();
        $options[] = array(
            'value' => '',
            'label' => __('Select the taxvat attribute')
        );
        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options['customer|'.$value['frontend_label']] = array(
                    'value' => 'customer|'.$value['attribute_code'],
                    'label' => 'Customer: '.$value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        $addressFields = $lenboxHelper->getFields('customer_address');
        foreach ($addressFields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options['address|'.$value['frontend_label']] = array(
                    'value' => 'billing|'.$value['attribute_code'],
                    'label' => 'Billing: '.$value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        return $options;
    }
}