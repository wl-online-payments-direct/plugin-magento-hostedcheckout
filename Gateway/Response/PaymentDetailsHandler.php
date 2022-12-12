<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Gateway\SubjectReader;

class PaymentDetailsHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        /** @var GetHostedCheckoutResponse $response */
        $response = $response['object'] ?? false;
        if (!$response) {
            return;
        }

        $wlPayment = $response->getCreatedPaymentOutput()->getPayment();

        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        $payment->setCcTransId($wlPayment->getId());
        $payment->setLastTransId($wlPayment->getId());
        $payment->setCcStatusDescription($wlPayment->getStatus());
    }
}
