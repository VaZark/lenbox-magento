<?php

namespace Lenbox\CbnxPayment\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Lenbox\CbnxPayment\Helper\Data as Lenbox;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var Lenbox
     */
    private $lenbox;

    /**
     * @param TransferBuilder $transferBuilder
     * @param Lenbox $lenbox
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Lenbox $lenbox
    )
    {
        $this->transferBuilder = $transferBuilder;
        $this->lenbox = $lenbox;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $apiUrl = $request['api_url'];
        unset($request['api_url']);

        return $this->transferBuilder
            ->setMethod(\Zend_Http_Client::POST)
            ->setHeaders(
                [
                    "x-lenbox-token: {$this->lenbox->getToken()}",
                    "cache-control: no-cache",
                    "content-type: application/json"
                ]
            )
            ->setBody(json_encode($request, JSON_UNESCAPED_SLASHES))
            ->setUri($apiUrl)
            ->build();
    }
}