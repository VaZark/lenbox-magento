<?php

namespace Lenbox\CbnxPayment\Gateway\Http\Client;

use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Lenbox\CbnxPayment\Helper\Data as Lenbox;

class ClientMock implements ClientInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Lenbox
     */
    private $lenbox;

    /**
     * @param ZendClientFactory $clientFactory
     * @param Logger $logger
     * @param ConverterInterface | null $converter
     */
    public function __construct(
        Logger $logger,
        Lenbox $lenbox
    ) {
        $this->logger = $logger;
        $this->lenbox = $lenbox;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        error_log('triggering place req', 3,  '/bitnami/magento/var/log/custom_error.log');
        error_log(json_encode($transferObject), 3,  '/bitnami/magento/var/log/custom_error.log');

        $log = [
            'request'       => $transferObject->getBody(),
            'request_uri'   => $transferObject->getUri(),
            'token'         => $this->lenbox->getToken(),
            'uri'         => $transferObject->getUri(),
            'body'         => $transferObject->getBody(),
        ];

        $result = [];

        try {
            $result = $this->lenbox->requestApi(
                $transferObject->getUri(),
                $transferObject->getBody()
            );
            //            $result = ['success' => 1];
            $log['response'] = $result;
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
