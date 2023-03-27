<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\AmountOfMoneyFactory;
use OnlinePayments\Sdk\Domain\LineItem;
use OnlinePayments\Sdk\Domain\LineItemFactory;
use OnlinePayments\Sdk\Domain\OrderLineDetails;
use OnlinePayments\Sdk\Domain\OrderLineDetailsFactory;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;
use Worldline\PaymentCore\Api\AmountFormatterInterface;

class ShippingLineItemBuilder
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

    public function __construct(
        LineItemFactory $lineItemFactory,
        AmountOfMoneyFactory $amountOfMoneyFactory,
        OrderLineDetailsFactory $orderLineDetailsFactory,
        AmountFormatterInterface $amountFormatter
    ) {
        $this->lineItemFactory = $lineItemFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->orderLineDetailsFactory = $orderLineDetailsFactory;
        $this->amountFormatter = $amountFormatter;
    }

    public function buildShippingLineItem(CartInterface $quote): LineItem
    {
        $amountOfMoney = $this->getAmountOfMoney($quote);
        $lineItem = $this->lineItemFactory->create();
        $lineItem->setAmountOfMoney($amountOfMoney);

        $orderLineDetails = $this->getOrderLineDetails($quote, $amountOfMoney);
        $lineItem->setOrderLineDetails($orderLineDetails);

        return $lineItem;
    }

    private function getAmountOfMoney(CartInterface $quote): AmountOfMoney
    {
        $amountOfMoney = $this->amountOfMoneyFactory->create();
        $amountOfMoney->setCurrencyCode($quote->getCurrency()->getQuoteCurrencyCode());

        $shippingAmount = $this->amountFormatter->formatToInteger(
            (float) $quote->getShippingAddress()->getShippingAmount(),
            (string) $quote->getCurrency()->getQuoteCurrencyCode()
        );
        $amountOfMoney->setAmount($shippingAmount);

        return $amountOfMoney;
    }

    private function getOrderLineDetails(CartInterface $quote, AmountOfMoney $amountOfMoney): OrderLineDetails
    {
        $orderLineDetails = $this->orderLineDetailsFactory->create();
        $orderLineDetails->setDiscountAmount(0);
        $orderLineDetails->setTaxAmount(0);
        $orderLineDetails->setProductCode(__('Shipping'));
        $orderLineDetails->setProductName(__('Shipping'));
        $orderLineDetails->setQuantity(1);
        $orderLineDetails->setProductPrice($amountOfMoney->getAmount());
        $mealVouchersProductType = $quote->getData(MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE);
        if ($mealVouchersProductType && $mealVouchersProductType !== MealvouchersProductTypes::NO) {
            $orderLineDetails->setProductType($mealVouchersProductType);
        }

        return $orderLineDetails;
    }
}
