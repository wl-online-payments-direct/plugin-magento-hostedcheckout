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

    public function __construct(
        LineItemFactory $lineItemFactory,
        AmountOfMoneyFactory $amountOfMoneyFactory,
        OrderLineDetailsFactory $orderLineDetailsFactory,
        AmountFormatterInterface $amountFormatter,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory
    ) {
        $this->lineItemFactory = $lineItemFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->orderLineDetailsFactory = $orderLineDetailsFactory;
        $this->amountFormatter = $amountFormatter;
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
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

    private function getOrderLineDetails(CartItemInterface $item): OrderLineDetails
    {
        $orderLineDetails = $this->orderLineDetailsFactory->create();
        $orderLineDetails->setDiscountAmount($this->getDiscountAmount($item));
        $orderLineDetails->setProductCode($item->getSku());
        $orderLineDetails->setProductName($item->getName());
        $this->addProductType($item, $orderLineDetails);
        $orderLineDetails->setQuantity((float)$item->getQty());
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
            ) * $item->getQty();

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

        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();

        return $this->amountFormatter->formatToInteger((float)($discountAmount / $item->getQty()), $currency);
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

        $compensation = $this->amountFormatter->formatToInteger(
            (float)($item->getDiscountTaxCompensationAmount() / $item->getQty()),
            $currency
        );
        $price = $item->getPrice();
        $baseCurrency = $this->storeManager->getStore()->getBaseCurrency()->getCode();
        $rate = $this->currencyFactory->create()->load($currency)->getAnyRate($baseCurrency);

        return $this->amountFormatter->formatToInteger((float)$price / $rate, $currency) + $compensation;
    }

    private function getTaxAmount(CartItemInterface $item): int
    {
        $currency = (string)$item->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $weeeTax = json_decode($item->getWeeeTaxApplied(), true);
        $totalTaxes = (float)$item->getTaxAmount() +
            (float)(isset($weeeTax[0], $weeeTax[0]['base_amount_incl_tax']) ? $weeeTax[0]['base_amount_incl_tax'] : 0);

        return $this->amountFormatter->formatToInteger($totalTaxes / $item->getQty(), $currency);
    }
}
