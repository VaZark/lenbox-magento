<?php

namespace Lenbox\CbnxPayment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Lenbox\CbnxPayment\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'lenbox_standard';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => true,
                ]
            ]
        ];
    }
}