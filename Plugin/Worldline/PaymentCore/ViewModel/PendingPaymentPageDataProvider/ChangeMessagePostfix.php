<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Plugin\Worldline\PaymentCore\ViewModel\PendingPaymentPageDataProvider;

use Worldline\PaymentCore\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\ViewModel\PendingPaymentPageDataProvider;
use Worldline\PaymentCore\Model\ResourceModel\Quote as QuoteResource;

class ChangeMessagePostfix
{
    /**
     * @var QuoteResource
     */
    private $quoteResource;

    public function __construct(QuoteResource $quoteResource)
    {
        $this->quoteResource = $quoteResource;
    }

    public function beforeGetNotificationMessage(
        PendingPaymentPageDataProvider $subject,
        string $messagePostfix = ''
    ): array {
        $quote = $this->quoteResource->getQuoteByReservedOrderId($subject->getIncrementId());
        if (!$quote->getId()) {
            return [$messagePostfix];
        }

        $paymentProductId = (int) $quote->getPayment()->getAdditionalInformation(PaymentInterface::PAYMENT_PRODUCT_ID);

        $messagePostfix = $this->getMessage($paymentProductId) ?? $messagePostfix;
        return [$messagePostfix];
    }

    private function getMessage(int $paymentProductId): ?string
    {
        $mapping = [
            PaymentProductsDetailsInterface::MULTIBANCO_PRODUCT_ID =>
                __('Please go to an ATM to validate the Multibanco payment.')->render(),
        ];

        return $mapping[$paymentProductId] ?? null;
    }
}
