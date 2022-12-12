<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Quote\Model\Quote\Payment;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService as GetterRequest;
use Worldline\PaymentCore\Api\PaymentManagerInterface;
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

    /**
     * @var PaymentManagerInterface
     */
    private $paymentManager;

    public function __construct(
        GetterRequest $getterRequest,
        TransactionWLResponseManagerInterface $transactionWLResponseManager,
        PaymentManagerInterface $paymentManager
    ) {
        $this->getterRequest = $getterRequest;
        $this->transactionWLResponseManager = $transactionWLResponseManager;
        $this->paymentManager = $paymentManager;
    }

    public function getStatusCode(Payment $payment): ?int
    {
        $hostedCheckoutId = (string)$payment->getAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID);
        if (!$hostedCheckoutId) {
            return null;
        }

        $storeId = (int)$payment->getMethodInstance()->getStore();
        $response = $this->getterRequest->execute($hostedCheckoutId, $storeId);
        $paymentResponse = $response->getCreatedPaymentOutput()->getPayment();
        if (!$paymentResponse) {
            return null;
        }

        $statusCode = (int)$paymentResponse->getStatusOutput()->getStatusCode();
        if (in_array(
            $statusCode,
            [TransactionStatusInterface::PENDING_CAPTURE_CODE, TransactionStatusInterface::CAPTURED_CODE]
        )) {
            $this->paymentManager->savePayment($paymentResponse);
            $this->transactionWLResponseManager->saveTransaction($paymentResponse);
        }

        return $statusCode;
    }
}
