<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Quote\Model\Quote\Payment;
use Worldline\HostedCheckout\Service\Getter\Request as GetterRequest;
use Worldline\PaymentCore\Api\TransactionWLResponseManagerInterface;
use Worldline\PaymentCore\Model\PaymentStatusCode\StatusCodeRetrieverInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

class StatusCodeRetriever implements StatusCodeRetrieverInterface
{
    /**
     * @var GetterRequest
     */
    private $getterRequest;

    /**
     * @var TransactionWLResponseManagerInterface
     */
    private $transactionWLResponseManager;

    public function __construct(
        GetterRequest $getterRequest,
        TransactionWLResponseManagerInterface $transactionWLResponseManager
    ) {
        $this->getterRequest = $getterRequest;
        $this->transactionWLResponseManager = $transactionWLResponseManager;
    }

    public function getStatusCode(Payment $payment): ?int
    {
        $hostedCheckoutId = (string)$payment->getAdditionalInformation('hosted_checkout_id');
        if (!$hostedCheckoutId) {
            return null;
        }

        $storeId = (int)$payment->getMethodInstance()->getStore();
        $response = $this->getterRequest->create($hostedCheckoutId, $storeId);
        $paymentResponse = $response->getCreatedPaymentOutput()->getPayment();
        if (!$paymentResponse) {
            return null;
        }

        $statusCode = (int)$paymentResponse->getStatusOutput()->getStatusCode();
        if (in_array(
            $statusCode,
            [TransactionStatusInterface::PENDING_CAPTURE_CODE, TransactionStatusInterface::CAPTURED_CODE]
        )) {
            $this->transactionWLResponseManager->saveTransaction($paymentResponse);
        }

        return $statusCode;
    }
}
