<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\PaymentAction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\PaymentRepositoryInterface;
use Worldline\PaymentCore\Api\TransactionRepositoryInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

class BankTransferHandler implements PaymentActionReplaceHandlerInterface
{
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
        $paymentAction = 'authorize';
        $incrementId = $payment->getOrder()->getIncrementId();

        $worldlinePayment = $this->paymentRepository->get($incrementId);
        $paymentProductId = (int) $worldlinePayment->getPaymentProductId();

        $lastTransaction = $this->transactionRepository->getLastTransaction($incrementId);
        if (!$lastTransaction || $lastTransaction->getStatusCode() !== TransactionStatusInterface::CAPTURE_REQUESTED) {
            $paymentAction = 'authorize_capture';
        }

        return PaymentProductsDetailsInterface::BANK_TRANSFER_PRODUCT_ID === $paymentProductId
            ? $paymentAction : null;
    }
}
