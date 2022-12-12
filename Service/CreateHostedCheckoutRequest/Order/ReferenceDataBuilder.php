<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\OrderReferences;
use OnlinePayments\Sdk\Domain\OrderReferencesFactory;

class ReferenceDataBuilder
{
    /**
     * @var OrderReferencesFactory
     */
    private $orderReferencesFactory;

    public function __construct(
        OrderReferencesFactory $orderReferencesFactory
    ) {
        $this->orderReferencesFactory = $orderReferencesFactory;
    }

    public function build(CartInterface $quote): OrderReferences
    {
        $references = $this->orderReferencesFactory->create();
        $references->setMerchantReference($quote->getReservedOrderId());
        return $references;
    }
}
