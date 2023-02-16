<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Api\AmountFormatterInterface;
use Worldline\PaymentCore\Gateway\SubjectReader;

class AmountResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var AmountFormatterInterface
     */
    private $amountFormatter;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader,
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
        $transactionAmountOfMoney = $response->getCreatedPaymentOutput()
            ->getPayment()
            ->getPaymentOutput()
            ->getAmountOfMoney()
            ->getAmount();

        $currency = (string) $validationSubject['payment']->getPayment()->getOrder()->getOrderCurrencyCode();
        $orderAmountOfMoney = $this->amountFormatter->formatToInteger((float) $validationSubject['amount'], $currency);

        return $this->createResult($transactionAmountOfMoney === $orderAmountOfMoney);
    }
}
