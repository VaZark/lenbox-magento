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
    ) {
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
        error_log('triggering create transfer', 3,  '/bitnami/magento/var/log/custom_error.log');
        error_log(json_encode($request), 3,  '/bitnami/magento/var/log/custom_error.log');

        // $apiUrl = $request['api_url'];
        // unset($request['api_url']);
        $apiUrl = "https://app.finnocar.com/version-test/api/1.1/wf/getformsplit";

        return $this->transferBuilder
            ->setMethod(\Zend_Http_Client::POST)
            ->setHeaders(
                [
                    "cache-control: no-cache",
                    "content-type: application/json"
                ]
            )
            ->setBody(json_encode($request, JSON_UNESCAPED_SLASHES))
            ->setUri($apiUrl)
            ->build();
    }
}
