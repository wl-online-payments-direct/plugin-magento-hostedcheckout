<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order;

use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Quote\Api\Data\CartItemInterface;
use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\AmountOfMoneyFactory;
use OnlinePayments\Sdk\Domain\LineItem;
use OnlinePayments\Sdk\Domain\LineItemFactory;
use OnlinePayments\Sdk\Domain\OrderLineDetails;
use OnlinePayments\Sdk\Domain\OrderLineDetailsFactory;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;
use Worldline\PaymentCore\Api\AmountFormatterInterface;
use Magento\Framework\Serialize\Serializer\Json;

class LineItemBuilder
{
    /**
     * @var LineItemFactory
     */
    private $lineItemFactory;

    /**
     * @var AmountOfMoneyFactory
     */
    private $amountOfMoneyFactory;

    /**
     * @var OrderLineDetailsFactory
     */
    private $orderLineDetailsFactory;

    /**
     * @var AmountFormatterInterface
     */
    private $amountFormatter;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;
    /**
     * @var Json
     */
    private $json;

    public function __construct(
        LineItemFactory $lineItemFactory,
        AmountOfMoneyFactory $amountOfMoneyFactory,
        OrderLineDetailsFactory $orderLineDetailsFactory,
        AmountFormatterInterface $amountFormatter,
        CurrencyFactory $currencyFactory,
        Json $json
    ) {
        $this->lineItemFactory = $lineItemFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->orderLineDetailsFactory = $orderLineDetailsFactory;
        $this->amountFormatter = $amountFormatter;
        $this->currencyFactory = $currencyFactory;
        $this->json = $json;
    }

    public function buildLineItem(CartItemInterface $item): LineItem
    {
        $lineItem = $this->lineItemFactory->create();

        $orderLineDetails = $this->getOrderLineDetails($item);
        $lineItem->setOrderLineDetails($orderLineDetails);

        $amountOfMoney = $this->getAmountOfMoney($item, $orderLineDetails);
        $lineItem->setAmountOfMoney($amountOfMoney);

        return $lineItem;
    }

    public function buildAdjustmentLineItem(int $amount, string $currency): LineItem
    {
        $lineItem = $this->lineItemFactory->create();

        $orderLineDetails = $this->orderLineDetailsFactory->create();
        $orderLineDetails->setDiscountAmount(0);
        $orderLineDetails->setProductName('Adjustment');
        $orderLineDetails->setProductCode('Adjustment');
        $orderLineDetails->setQuantity(1);
        $orderLineDetails->setTaxAmount(0);
        $orderLineDetails->setProductPrice($amount);

        $amountOfMoney = $this->amountOfMoneyFactory->create();
        $amountOfMoney->setAmount($amount);
        $amountOfMoney->setCurrencyCode($currency);

        $lineItem->setOrderLineDetails($orderLineDetails);
        $lineItem->setAmountOfMoney($amountOfMoney);

        return $lineItem;
    }

    /**
     * @param array $lineItems
     * @param string $currency
     *
     * @return LineItem[]
     */
    public function buildMergedProduct(array $lineItems, string $currency): array
    {
        $lineItem = $this->lineItemFactory->create();
        $orderLineDetails = $this->orderLineDetailsFactory->create();

        $amounts = $this->buildMergedProductAmounts($lineItems);

        $orderLineDetails->setDiscountAmount($amounts['totalDiscount']);
        $orderLineDetails->setProductPrice($amounts['productPrice']);
        $orderLineDetails->setTaxAmount($amounts['totalTax']);
        $orderLineDetails->setQuantity(1);
        $orderLineDetails->setProductName($this->getMergedProductName($lineItems));
        $orderLineDetails->setProductType($this->getMergedProductType($lineItems));
        $orderLineDetails->setProductCode($this->getProductCode($lineItems));

        $amount = $this->amountOfMoneyFactory->create();
        $amount->setAmount($amounts['totalAmount']);
        $amount->setCurrencyCode($currency);

        $lineItem->setOrderLineDetails($orderLineDetails);
        $lineItem->setAmountOfMoney($amount);

        return [$lineItem];
    }

    /**
     * @param array $lineItems
     *
     * @return array
     */
    private function buildMergedProductAmounts(array $lineItems): array
    {
        $totalAmount = 0;
        $totalDiscount = 0;
        $totalTax = 0;
        $productPrice = 0;

        foreach ($lineItems as $lineItem) {
            $totalAmount += $lineItem->getAmountOfMoney()->getAmount();
            $productPrice +=
                $lineItem->getOrderLineDetails()->getProductPrice() * $lineItem->getOrderLineDetails()->getQuantity();
            $totalDiscount +=
                $lineItem->getOrderLineDetails()->getDiscountAmount() * $lineItem->getOrderLineDetails()->getQuantity();
            $totalTax +=
                $lineItem->getOrderLineDetails()->getTaxAmount() * $lineItem->getOrderLineDetails()->getQuantity();
        }

        return [
          'totalAmount' => $totalAmount,
          'totalDiscount' => $totalDiscount,
          'totalTax' => $totalTax,
          'productPrice' => $productPrice
        ];
    }

    /**
     *  Determines the merged product type based on priority:
     *  - FoodAndDrink > HomeAndGarden > GiftAndFlowers
     *
     * @param array $products
     *
     * @return string
     */
    private function getMergedProductType(array $products): string
    {
        $hasHomeAndGarden = false;

        foreach ($products as $product) {
            $type = $product->getOrderLineDetails()->getProductType();

            if ($type === MealvouchersProductTypes::FOOD_AND_DRINK) {
                return MealvouchersProductTypes::FOOD_AND_DRINK;
            }

            if ($type === MealvouchersProductTypes::HOME_AND_GARDEN) {
                $hasHomeAndGarden = true;
            }
        }

        // If no FoodAndDrink but at least one HomeAndGarden
        if ($hasHomeAndGarden) {
            return MealvouchersProductTypes::HOME_AND_GARDEN;
        }

        // Default fallback (GiftAndFlowers)
        return MealvouchersProductTypes::GIFT_AND_FLOWERS;
    }

    /**
     * @param array $products
     *
     * @return string
     */
    private function getProductCode(array $products): string
    {
        if (count($products) === 1) {
            return $products[0]->getOrderLineDetails()->getProductCode();
        }

        return MealvouchersProductTypes::MERGED_PRODUCT_CODE;
    }

    /**
     * @param array $products
     *
     * @return string
     */
    private function getMergedProductName(array $products): string
    {
        $typeCounts = [];
        $names = [];

        foreach ($products as $product) {
            $type = $product->getOrderLineDetails()->getProductType();
            $names[] = $product->getOrderLineDetails()->getProductName();
            if ($type !== null) {
                $mealvoucherTypes = new MealvouchersProductTypes();
                $type = $mealvoucherTypes->optionsMap()[$type];
                if (!isset($typeCounts[$type])) {
                    $typeCounts[$type] = 0;
                }
                $typeCounts[$type] += (int) $product->getOrderLineDetails()->getQuantity();
            }
        }

        // Create a string like "Product A + Product B + Product C"
        $nameString = implode(' + ', $names);

        if (strlen($nameString) <= 50) {
            return $nameString;
        }

        $parts = [];
        foreach ($typeCounts as $type => $count) {
            $parts[] = "{$count} {$type}";
        }
        $result = implode(' & ', $parts);

        // Truncate if needed
        return strlen($result) > 50 ? substr($result, 0, 50) : $result;
    }

    private function getOrderLineDetails(CartItemInterface $item): OrderLineDetails
    {
        $orderLineDetails = $this->orderLineDetailsFactory->create();
        $orderLineDetails->setDiscountAmount($this->getDiscountAmount($item));
        $orderLineDetails->setProductCode($item->getSku());
        $orderLineDetails->setProductName($item->getName());
        $this->addProductType($item, $orderLineDetails);
        $orderLineDetails->setQuantity((int)$item->getQty());

        if (floor($item->getQty()) < $item->getQty()) {
            $orderLineDetails->setProductName($item->getName() . ' (quantity ' . $item->getQty() . ')');
            $orderLineDetails->setQuantity(1);
        }

        $taxAmount = $this->getTaxAmount($item);
        $orderLineDetails->setProductPrice($this->getProductPrice($item, $taxAmount));
        $orderLineDetails->setTaxAmount($taxAmount);

        return $orderLineDetails;
    }

    private function getAmountOfMoney(
        CartItemInterface $item,
        OrderLineDetails $orderLineDetails
    ): AmountOfMoney {
        $amountOfMoney = $this->amountOfMoneyFactory->create();
        if ($item->getQuote()->getCurrency()) {
            $amountOfMoney->setCurrencyCode($item->getQuote()->getCurrency()->getQuoteCurrencyCode());
        }

        $totalAmount = (
                $orderLineDetails->getProductPrice()
                + $orderLineDetails->getTaxAmount()
                - $orderLineDetails->getDiscountAmount()
            ) * $orderLineDetails->getQuantity();

        $amountOfMoney->setAmount((int)$totalAmount);

        return $amountOfMoney;
    }

    private function getDiscountAmount(CartItemInterface $item): int
    {
        $discountAmount = 0.0;
        if ($item->getProductType() === BundleProductType::TYPE_CODE) {
            foreach ($item->getChildren() as $child) {
                $discountAmount += $child->getDiscountAmount();
            }
        } else {
            $discountAmount = (float)$item->getDiscountAmount();
        }

        $quantity = $this->getQuantity($item);

        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();

        return $this->amountFormatter->formatToInteger((float)($discountAmount / $quantity), $currency);
    }

    /**
     * Quantity the line is reported with. Fractional quantities are collapsed into a single unit,
     * the original quantity is then carried in the product name.
     *
     * @param CartItemInterface $item
     *
     * @return float
     */
    private function getQuantity(CartItemInterface $item): float
    {
        if (floor($item->getQty()) < $item->getQty()) {
            return 1.0;
        }

        return (float)$item->getQty();
    }

    /**
     * Tax charged for the whole line, including fixed product taxes
     *
     * @param CartItemInterface $item
     *
     * @return float
     */
    private function getRowTaxAmount(CartItemInterface $item): float
    {
        $weeeTaxes = $this->json->unserialize($item->getWeeeTaxApplied() ?? '[]', true);
        $totalWeeeTaxes = 0;

        foreach ($weeeTaxes as $weeeTax) {
            $totalWeeeTaxes += (float)($weeeTax['row_amount_incl_tax'] ?? 0);
        }

        return (float)$item->getTaxAmount() + $totalWeeeTaxes;
    }

    private function addProductType(CartItemInterface $item, OrderLineDetails $orderLineDetails): void
    {
        if (!$item->getProduct()) {
            return;
        }

        $mealvouchersProductType = $item->getProduct()->getData(
            MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE
        );
        if ($mealvouchersProductType && $mealvouchersProductType !== MealvouchersProductTypes::NO) {
            $orderLineDetails->setProductType($mealvouchersProductType);
        }
    }

    /**
     * Net price of a single unit.
     *
     * The gross unit price is rounded once and the already rounded tax is subtracted from it, so
     * that (productPrice + taxAmount) * quantity stays aligned with the row total Magento charges.
     * Rounding the net price and the tax independently lets both halves round up and inflates the
     * line by up to one minor unit per item, which the payment API rejects with
     * "Payment detail amounts validation failed" (SM-284).
     *
     * @param CartItemInterface $item
     * @param int $taxAmount
     *
     * @return int
     */
    private function getProductPrice(CartItemInterface $item, int $taxAmount): int
    {
        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $quantity = $this->getQuantity($item);

        $rowGrossAmount = (float)$item->getRowTotal()
            + (float)$item->getDiscountTaxCompensationAmount()
            + $this->getRowTaxAmount($item);

        $unitGrossPrice = (int)round(
            $this->amountFormatter->formatToInteger($rowGrossAmount, $currency) / $quantity
        );

        return $unitGrossPrice - $taxAmount;
    }

    private function getTaxAmount(CartItemInterface $item): int
    {
        $quantity = $this->getQuantity($item);
        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();

        return $this->amountFormatter->formatToInteger($this->getRowTaxAmount($item) / $quantity, $currency);
    }
}
