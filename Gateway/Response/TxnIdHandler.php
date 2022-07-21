<?php

namespace Lenbox\CbnxPayment\Gateway\Response;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;

class TxnIdHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     *
     * @throws LocalizedException
     * @throws InvalidArgumentException
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (
            !isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $this->validateLenboxResponse($response);

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $payment->setIsTransactionPending(true);
        // $payment->getOrder()->setCanSendNewEmailFlag(true);

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        // $payment->setTransactionId($response['return']['referenceId']);
        $payment->setAdditionalInformation('redirect_url', $response['return']['response']['url']);
    }

    /**
     * @param array $response
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateLenboxResponse(array $response): void
    {
        try {
            $this->validateRequiredFields($response);
        } catch (InvalidArgumentException $exception) {
            $context = [
                'message' => $exception->getMessage(),
            ];

            if (isset($response['return']['errors'])) {
                $context['errors'] = array_column($response['return']['errors'], 'message');
            }

            $this->logger->error('lenbox creating order error', $context);

            throw $exception;
        }
    }

    private function validateRequiredFields(array $response): void
    {
        error_log('Response in TxnIdHandler', 3,  '/bitnami/magento/var/log/custom_error.log');
        error_log(json_encode($response), 3,  '/bitnami/magento/var/log/custom_error.log');

        $errorMessage = $response['return']['message'] ?? null;
        if ($response['return']['status'] !== "success") {
            throw new InvalidArgumentException($errorMessage ?? 'Error from Lenbox');
        }
    }
}
