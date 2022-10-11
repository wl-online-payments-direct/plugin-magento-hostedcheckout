<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\PaymentAction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\PaymentCore\Api\TransactionRepositoryInterface;
use Worldline\PaymentCore\Api\Data\TransactionInterface;

class BancontactHandler implements PaymentActionReplaceHandlerInterface
{
    private const BANCONTACT_PRODUCT_ID = 3012;
    private const BANCONTACT_PAYMENT_ACTION = 'authorize_capture';

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function getPaymentAction(OrderPaymentInterface $payment): ?string
    {
        $incrementId = $payment->getOrder()->getIncrementId();

        $transaction = $this->transactionRepository->getCaptureTransaction($incrementId);
        if (!$transaction instanceof TransactionInterface) {
            return null;
        }

        $paymentProductId = $transaction->getAdditionalData()[TransactionInterface::PAYMENT_PRODUCT_ID] ?? 0;

        return self::BANCONTACT_PRODUCT_ID === $paymentProductId ? self::BANCONTACT_PAYMENT_ACTION : null;
    }
}
