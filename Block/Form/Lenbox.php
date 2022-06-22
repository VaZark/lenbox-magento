<?php
namespace Lenbox\CbnxPayment\Block\Form;


class Lenbox extends \Magento\Payment\Block\Form
{
    /**
     * Especifica template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('lenbox/form/lenbox.phtml');
    }
}