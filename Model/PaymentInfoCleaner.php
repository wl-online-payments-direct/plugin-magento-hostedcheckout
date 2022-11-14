<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;

class PaymentInfoCleaner
{
    /**
     * @var QuoteResource
     */
    private $quoteResource;

    public function __construct(QuoteResource $quoteResource)
    {
        $this->quoteResource = $quoteResource;
    }

    public function clean(CartInterface $quote): void
    {
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('device');
        $payment->setAdditionalInformation('public_hash');
        $payment->setAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID);
        $payment->setAdditionalInformation('is_active_payment_token_enabler');

        $this->quoteResource->save($quote);
    }
}
