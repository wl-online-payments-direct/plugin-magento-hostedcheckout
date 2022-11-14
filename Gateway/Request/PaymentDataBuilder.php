<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Worldline\PaymentCore\Gateway\SubjectReader;

class PaymentDataBuilder implements BuilderInterface
{
    public const AMOUNT = 'amount';
    public const STORE_ID = 'store_id';
    public const HOSTED_CHECKOUT_ID = 'payment_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $amount = (int)round($this->subjectReader->readAmount($buildSubject) * 100);

        return [
            self::AMOUNT => $amount,
            self::STORE_ID => (int)$payment->getMethodInstance()->getStore(),
            self::HOSTED_CHECKOUT_ID => $payment->getAdditionalInformation(self::HOSTED_CHECKOUT_ID),
        ];
    }
}
