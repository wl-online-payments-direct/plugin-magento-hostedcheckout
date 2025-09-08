<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\Discount;
use OnlinePayments\Sdk\Domain\LineItem;
use OnlinePayments\Sdk\Domain\ShoppingCart;
use OnlinePayments\Sdk\Domain\ShoppingCartFactory;
use Worldline\PaymentCore\Api\AmountFormatterInterface;

class ShoppingCartDataBuilder
{
    public const WORLD_LINE_MEAL_VAUCHER_METHOD = 'worldline_redirect_payment_5402';

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

    public function __construct(
        ShoppingCartFactory $shoppingCartFactory,
        ShoppingCartDataDebugLogger $shoppingCartDataDebugLogger,
        AmountFormatterInterface $amountFormatter,
        LineItemBuilder $lineItemBuilder
    ) {
        $this->shoppingCartFactory = $shoppingCartFactory;
        $this->shoppingCartDataDebugLogger = $shoppingCartDataDebugLogger;
        $this->amountFormatter = $amountFormatter;
        $this->lineItemBuilder = $lineItemBuilder;
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

        if ($quote->getPayment()->getData('method') === self::WORLD_LINE_MEAL_VAUCHER_METHOD) {
            $lineItems = $this->lineItemBuilder->buildMergedProduct(
                $lineItems,
                (string)$quote->getCurrency()->getQuoteCurrencyCode()
            );
        }
        $lineItems = $this->adjustAmount($lineItems, $quote, $cartTotal);

        $shoppingCart = $this->shoppingCartFactory->create();
        $shoppingCart->setItems($lineItems);

        if ($this->skipLineItems($quote, $cartTotal)) {
            $this->shoppingCartDataDebugLogger->log($quote, $shoppingCart);
            return null;
        }

        return $shoppingCart;
    }

    public function getDiscountAdjustment(CartInterface $quote, ShoppingCart $cart): ?Discount
    {
        $cartTotal = 0;

        foreach ($cart->getItems() as $item) {
            $cartTotal += $item->getAmountOfMoney()->getAmount();
        }

        $amountDifference = $this->getAmountDifference($quote, $cartTotal);
        $allowedDifference = $this->getAllowedDifference(
            (string) $quote->getCurrency()->getQuoteCurrencyCode()
        );

        if ($amountDifference < 0 && $amountDifference > -$allowedDifference) {
            $discount = new Discount();
            $discount->setAmount(-$amountDifference);

            return $discount;
        }

        return null;
    }

    /**
     * @param CartInterface $quote
     * @param int $cartTotal
     *
     * @return int
     */
    public function getAmountDifference(CartInterface $quote, int $cartTotal)
    {
        $currency = (string) $quote->getCurrency()->getQuoteCurrencyCode();

        $shippingAmount = $this->amountFormatter->formatToInteger(
            (float) $quote->getShippingAddress()->getShippingInclTax(),
            $currency
        );
        $cartGrandTotal = $this->amountFormatter->formatToInteger((float) $quote->getGrandTotal(), $currency);

        return $cartGrandTotal - $cartTotal - $shippingAmount;
    }

    /**
     * @param string $currency
     *
     * @return int
     */
    public function getAllowedDifference(string $currency): int
    {
        $numberOfDecimals = $this->amountFormatter->currencies[$currency] ?? 0;
        $allowedDifference = 100;

        if ($numberOfDecimals === 0) {
            $allowedDifference = 10;
        }

        if ($numberOfDecimals === 3) {
            $allowedDifference = 1000;
        }

        if ($numberOfDecimals === 4) {
            $allowedDifference = 10000;
        }

        return $allowedDifference;
    }

    /**
     * @param LineItem[] $lineItems
     * @param CartInterface $quote
     * @param int $cartTotal
     *
     * @return LineItem[]
     */
    private function adjustAmount(array $lineItems, CartInterface $quote, int &$cartTotal): array
    {
        $amountDifference = $this->getAmountDifference($quote, $cartTotal);
        $currency = (string) $quote->getCurrency()->getQuoteCurrencyCode();
        $allowedDifference = $this->getAllowedDifference($currency);

        if ($amountDifference > 0 && $amountDifference < $allowedDifference) {
            $lineItem = $this->lineItemBuilder->buildAdjustmentLineItem($amountDifference, $currency);
            $lineItems[] = $lineItem;
            $cartTotal += $amountDifference;
        }

        return $lineItems;
    }

    private function skipLineItems(CartInterface $quote, int $cartTotal): bool
    {
        $difference = $this->getAmountDifference($quote, $cartTotal);

        $currency = (string)$quote->getCurrency()->getQuoteCurrencyCode();

        return $difference > 0 || $difference < -$this->getAllowedDifference($currency);
    }
}
