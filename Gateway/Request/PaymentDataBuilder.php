<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Worldline\PaymentCore\Api\AmountFormatterInterface;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

class PaymentDataBuilder implements BuilderInterface
{
    public const AMOUNT = 'amount';
    public const HOSTED_CHECKOUT_ID = 'payment_id';
    public const STORE_ID = 'store_id';

    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var AmountFormatterInterface
     */
    private $amountFormatter;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        AmountFormatterInterface $amountFormatter
    ) {
        $this->subjectReader = $subjectReader;
        $this->amountFormatter = $amountFormatter;
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $amount = $this->amountFormatter->formatToInteger(
            (float) $this->subjectReader->readAmount($buildSubject),
            (string) $payment->getOrder()->getOrderCurrencyCode()
        );

        return [
            self::AMOUNT => $amount,
            self::STORE_ID => (int)$payment->getMethodInstance()->getStore(),
            self::HOSTED_CHECKOUT_ID => (string) (int) $payment->getAdditionalInformation(self::HOSTED_CHECKOUT_ID),
        ];
    }
}
