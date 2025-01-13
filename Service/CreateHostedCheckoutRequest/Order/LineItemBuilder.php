<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order;

use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Store\Model\StoreManagerInterface;
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        Json $json
    ) {
        $this->lineItemFactory = $lineItemFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->orderLineDetailsFactory = $orderLineDetailsFactory;
        $this->amountFormatter = $amountFormatter;
        $this->storeManager = $storeManager;
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

    private function getOrderLineDetails(CartItemInterface $item): OrderLineDetails
    {
        $orderLineDetails = $this->orderLineDetailsFactory->create();
        $orderLineDetails->setDiscountAmount($this->getDiscountAmount($item));
        $orderLineDetails->setProductCode($item->getSku());
        $orderLineDetails->setProductName($item->getName());
        $this->addProductType($item, $orderLineDetails);
        $orderLineDetails->setQuantity((float)$item->getQty());

        if (floor($item->getQty()) < $item->getQty()) {
            $orderLineDetails->setProductName($item->getName() . ' (quantity ' . $item->getQty() . ')');
            $orderLineDetails->setQuantity(1);
        }

        $orderLineDetails->setProductPrice($this->getProductPrice($item));
        $orderLineDetails->setTaxAmount($this->getTaxAmount($item));

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

        $quantity = $item->getQty();

        if (floor($item->getQty()) < $item->getQty()) {
            $quantity = 1;
        }

        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();

        return $this->amountFormatter->formatToInteger((float)($discountAmount / $quantity), $currency);
    }

    private function addProductType(CartItemInterface $item, OrderLineDetails $orderLineDetails): void
    {
        $mealvouchersProductType = $item->getData(MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE);
        if ($mealvouchersProductType && $mealvouchersProductType !== MealvouchersProductTypes::NO) {
            $orderLineDetails->setProductType($mealvouchersProductType);
        }
    }

    private function getProductPrice(CartItemInterface $item): int
    {
        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $quantity = $item->getQty();

        if (floor($item->getQty()) < $item->getQty()) {
            $quantity = 1;
        }

        $compensation = $this->amountFormatter->formatToInteger(
            (float)($item->getDiscountTaxCompensationAmount() / $quantity),
            $currency
        );
        $price = $item->getRowTotal() / $quantity;

        return $this->amountFormatter->formatToInteger((float)$price, $currency) + $compensation;
    }

    private function getTaxAmount(CartItemInterface $item): int
    {
        $quantity = $item->getQty();

        if (floor($item->getQty()) < $item->getQty()) {
            $quantity = 1;
        }

        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $weeeTaxes = $this->json->unserialize($item->getWeeeTaxApplied() ?? '[]', true);
        $totalWeeeTaxes = 0;

        foreach ($weeeTaxes as $weeeTax) {
            $totalWeeeTaxes += (float)($weeeTax['row_amount_incl_tax'] ?? 0);
        }

        $totalTaxes = (float)$item->getTaxAmount() + $totalWeeeTaxes;

        return $this->amountFormatter->formatToInteger($totalTaxes / $quantity, $currency);
    }
}
