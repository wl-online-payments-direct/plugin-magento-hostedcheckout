<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\ShoppingCart;
use OnlinePayments\Sdk\Domain\ShoppingCartFactory;
use Worldline\PaymentCore\Api\AmountFormatterInterface;

class ShoppingCartDataBuilder
{
    /**
     * @var ShoppingCartFactory
     */
    private $shoppingCartFactory;

    /**
     * @var ShoppingCartDataDebugLogger
     */
    private $shoppingCartDataDebugLogger;

    /**
     * @var AmountFormatterInterface
     */
    private $amountFormatter;

    /**
     * @var LineItemBuilder
     */
    private $lineItemBuilder;

    /**
     * @var ShippingLineItemBuilder
     */
    private $shippingLineItemBuilder;

    public function __construct(
        ShoppingCartFactory $shoppingCartFactory,
        ShoppingCartDataDebugLogger $shoppingCartDataDebugLogger,
        AmountFormatterInterface $amountFormatter,
        LineItemBuilder $lineItemBuilder,
        ShippingLineItemBuilder $shippingLineItemBuilder
    ) {
        $this->shoppingCartFactory = $shoppingCartFactory;
        $this->shoppingCartDataDebugLogger = $shoppingCartDataDebugLogger;
        $this->amountFormatter = $amountFormatter;
        $this->lineItemBuilder = $lineItemBuilder;
        $this->shippingLineItemBuilder = $shippingLineItemBuilder;
    }

    public function build(CartInterface $quote): ?ShoppingCart
    {
        $lineItems = [];
        $cartTotal = 0;

        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $lineItems[] = $lineItem = $this->lineItemBuilder->buildLineItem($item);
            $cartTotal += $lineItem->getAmountOfMoney()->getAmount();
        }

        $lineItems[] = $this->shippingLineItemBuilder->buildShippingLineItem($quote);

        $shoppingCart = $this->shoppingCartFactory->create();
        $shoppingCart->setItems($lineItems);

        if ($this->skipLineItems($quote, $cartTotal)) {
            $this->shoppingCartDataDebugLogger->log($quote, $shoppingCart);
            return null;
        }

        return $shoppingCart;
    }

    private function skipLineItems(CartInterface $quote, int $cartTotal): bool
    {
        $currency = (string) $quote->getCurrency()->getQuoteCurrencyCode();

        $shippingAmount = $this->amountFormatter->formatToInteger(
            (float) $quote->getShippingAddress()->getShippingAmount(),
            $currency
        );
        $cartGrandTotal = $this->amountFormatter->formatToInteger((float) $quote->getGrandTotal(), $currency);

        return (bool) ($cartGrandTotal - $cartTotal - $shippingAmount);
    }
}
