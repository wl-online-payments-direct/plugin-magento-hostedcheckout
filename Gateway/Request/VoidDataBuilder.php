<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\PaymentCore\Gateway\SubjectReader;

class VoidDataBuilder implements BuilderInterface
{
    public const STORE_ID = 'store_id';
    public const TRANSACTION_ID = 'transaction_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        return [
            self::STORE_ID => (int)$payment->getMethodInstance()->getStore(),
            self::TRANSACTION_ID => $payment->getParentTransactionId() ?: $payment->getLastTransId()
        ];
    }
}
