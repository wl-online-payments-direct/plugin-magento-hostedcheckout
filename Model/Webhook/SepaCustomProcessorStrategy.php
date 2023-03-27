<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Webhook;

use OnlinePayments\Sdk\Domain\WebhooksEvent;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Webhook\CustomProcessorStrategyInterface;
use Worldline\PaymentCore\Api\Webhook\ProcessorInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

class SepaCustomProcessorStrategy implements CustomProcessorStrategyInterface
{
    /**
     * @var SepaOrderProcessor
     */
    private $sepaOrderProcessor;

    public function __construct(SepaOrderProcessor $sepaOrderProcessor)
    {
        $this->sepaOrderProcessor = $sepaOrderProcessor;
    }

    public function getProcessor(WebhooksEvent $webhookEvent): ?ProcessorInterface
    {
        if (!$payment = $webhookEvent->getPayment()) {
            return null;
        }

        $sepaOutput = $payment->getPaymentOutput()->getSepaDirectDebitPaymentMethodSpecificOutput();
        $statusCode = $payment->getStatusOutput()->getStatusCode();
        if (!$sepaOutput
            || PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID !== $sepaOutput->getPaymentProductId()
            || $statusCode !== TransactionStatusInterface::CAPTURED_CODE) {
            return null;
        }

        return $this->sepaOrderProcessor;
    }
}
