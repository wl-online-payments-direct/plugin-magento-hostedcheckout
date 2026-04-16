<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Webhook;

use OnlinePayments\Sdk\Domain\WebhooksEvent;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\Webhook\CustomProcessorStrategyInterface;
use Worldline\PaymentCore\Api\Webhook\ProcessorInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

class LinxoConnectCustomProcessorStrategy implements CustomProcessorStrategyInterface
{
    /**
     * @var LinxoConnectOrderProcessor
     */
    private $linxoConnectOrderProcessor;

    public function __construct(LinxoConnectOrderProcessor $linxoConnectOrderProcessor)
    {
        $this->linxoConnectOrderProcessor = $linxoConnectOrderProcessor;
    }

    public function getProcessor(WebhooksEvent $webhookEvent): ?ProcessorInterface
    {
        if (!$payment = $webhookEvent->getPayment()) {
            return null;
        }

        $redirectOutput = $payment->getPaymentOutput()->getRedirectPaymentMethodSpecificOutput();
        $statusCode = $payment->getStatusOutput()->getStatusCode();
        if (!$redirectOutput
            || $statusCode !== TransactionStatusInterface::CAPTURED_CODE
            || PaymentProductsDetailsInterface::LINXO_CONNECT_PRODUCT_ID !== $redirectOutput->getPaymentProductId()
        ) {
            return null;
        }

        return $this->linxoConnectOrderProcessor;
    }
}
