<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Webhook;

use Magento\Sales\Model\OrderFactory;
use OnlinePayments\Sdk\Domain\WebhooksEvent;
use Worldline\PaymentCore\Api\Order\InvoiceManagerInterface;
use Worldline\PaymentCore\Api\PaymentDataManagerInterface;
use Worldline\PaymentCore\Api\Webhook\PlaceOrderManagerInterface;
use Worldline\PaymentCore\Api\Webhook\ProcessorInterface;
use Worldline\PaymentCore\Model\Webhook\PlaceOrderProcessor;

class SepaOrderProcessor implements ProcessorInterface
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var PaymentDataManagerInterface
     */
    private $paymentDataManager;

    /**
     * @var InvoiceManagerInterface
     */
    private $invoiceManager;

    /**
     * @var PlaceOrderManagerInterface
     */
    private $placeOrderManager;

    /**
     * @var PlaceOrderProcessor
     */
    private $placeOrderProcessor;

    public function __construct(
        OrderFactory $orderFactory,
        PaymentDataManagerInterface $paymentDataManager,
        InvoiceManagerInterface $invoiceManager,
        PlaceOrderManagerInterface $placeOrderManager,
        PlaceOrderProcessor $placeOrderProcessor
    ) {
        $this->orderFactory = $orderFactory;
        $this->paymentDataManager = $paymentDataManager;
        $this->invoiceManager = $invoiceManager;
        $this->placeOrderManager = $placeOrderManager;
        $this->placeOrderProcessor = $placeOrderProcessor;
    }

    public function process(WebhooksEvent $webhookEvent): void
    {
        $quote = $this->placeOrderManager->getValidatedQuote($webhookEvent);
        if (!$quote) {
            return;
        }

        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());

        if ($order->getId()) {
            $this->paymentDataManager->savePaymentData($webhookEvent->getPayment());
            $this->invoiceManager->createInvoice($order);
            return;
        }

        $this->placeOrderProcessor->process($webhookEvent);
    }
}
