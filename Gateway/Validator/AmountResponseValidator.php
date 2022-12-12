<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Gateway\SubjectReader;

class AmountResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
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

        $orderAmountOfMoney = (int)round($validationSubject['amount'] * 100);

        return $this->createResult($transactionAmountOfMoney === $orderAmountOfMoney);
    }
}
