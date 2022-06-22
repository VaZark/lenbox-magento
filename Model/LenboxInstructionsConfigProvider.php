<?php

namespace Lenbox\CbnxPayment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Repository as AssetsRepository;
use Lenbox\CbnxPayment\Helper\Data as PaymentHelper;

class LenboxInstructionsConfigProvider implements ConfigProviderInterface
{

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var AssetsRepository
     */
    protected $assetsRepository;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        AssetsRepository $assetsRepository
    ) {
        $this->escaper = $escaper;
        $this->paymentHelper = $paymentHelper;
        $this->assetsRepository = $assetsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        $config['payment']['lenbox_instructions'] = $this->getInstructions();
        $config['payment']['lenbox_checkout_mode'] = $this->paymentHelper->getCheckoutMode();
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions()
    {
        if ($this->paymentHelper->useCustomForm()) {
            return $this->paymentHelper->getCustomHtmlForm();
        }

        $logoAddress = $this->assetsRepository->getUrl('Lenbox_CbnxPayment::images/lenbox.jpg');

        return '<img width="150px" src="' . $logoAddress . '" alt="Lenbox Logo" '
            . 'style="background-color: white; border: 0; padding: 10px;" />'
            . '<br/>'
            . '<p>Want to know more Lenbox? '
            . '<a href="https://www.lenbox.io" target="_blank">Click here</a>.</p>';
    }
}
