<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\PaymentAction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\PaymentRepositoryInterface;
use Worldline\PaymentCore\Api\TransactionRepositoryInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

class SepaDirectDebitHandler implements PaymentActionReplaceHandlerInterface
{
    private const PAYMENT_ACTION = 'authorize';

    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function getPaymentAction(OrderPaymentInterface $payment): ?string
    {
        $incrementId = $payment->getOrder()->getIncrementId();

        $worldlinePayment = $this->paymentRepository->get($incrementId);
        $paymentProductId = (int) $worldlinePayment->getPaymentProductId();

        $lastTransaction = $this->transactionRepository->getLastTransaction($incrementId);
        if (!$lastTransaction || $lastTransaction->getStatusCode() !== TransactionStatusInterface::CAPTURE_REQUESTED) {
            return null;
        }

        return PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID === $paymentProductId
            ? self::PAYMENT_ACTION : null;
    }
}
