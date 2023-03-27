<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Api\AmountFormatterInterface;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

class AmountResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var AmountFormatterInterface
     */
    private $amountFormatter;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReaderInterface $subjectReader,
        AmountFormatterInterface $amountFormatter
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->amountFormatter = $amountFormatter;
    }

    public function validate(array $validationSubject): ResultInterface
    {
        /** @var GetHostedCheckoutResponse $response */
        $response = $this->subjectReader->readResponseObject($validationSubject);

        $paymentOutput = $response->getCreatedPaymentOutput()->getPayment()->getPaymentOutput();
        $transactionAmountOfMoney = $paymentOutput->getAmountOfMoney()->getAmount();

        if ($paymentOutput->getSurchargeSpecificOutput()) {
            $surchargeAmount = $paymentOutput->getSurchargeSpecificOutput()->getSurchargeAmount()->getAmount();
            $transactionAmountOfMoney += $surchargeAmount;
        }

        $currency = (string) $validationSubject['payment']->getPayment()->getOrder()->getOrderCurrencyCode();
        $orderAmountOfMoney = $this->amountFormatter->formatToInteger((float) $validationSubject['amount'], $currency);

        return $this->createResult($transactionAmountOfMoney === $orderAmountOfMoney);
    }
}
