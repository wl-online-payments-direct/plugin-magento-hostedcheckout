<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\OrderFactory;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order\ShoppingCartDataBuilder;
use Worldline\PaymentCore\Model\MethodNameExtractor;
use Worldline\PaymentCore\Service\CreateRequest\Order\AmountDataBuilder;
use Worldline\PaymentCore\Service\CreateRequest\Order\CustomerDataBuilder;
use Worldline\PaymentCore\Service\CreateRequest\Order\ReferenceDataBuilder;
use Worldline\PaymentCore\Service\CreateRequest\Order\ShippingAddressDataBuilder;

class OrderDataBuilder
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var MethodNameExtractor
     */
    private $methodNameExtractor;

    /**
     * @var AmountDataBuilder
     */
    private $amountDataBuilder;

    /**
     * @var CustomerDataBuilder
     */
    private $customerDataBuilder;

    /**
     * @var ReferenceDataBuilder
     */
    private $referenceDataBuilder;

    /**
     * @var ShippingAddressDataBuilder
     */
    private $shippingAddressDataBuilder;

    /**
     * @var ShoppingCartDataBuilder
     */
    private $shoppingCartDataBuilder;

    /**
     * @var Config[]
     */
    private $configProviders;

    public function __construct(
        OrderFactory $orderFactory,
        MethodNameExtractor $methodNameExtractor,
        AmountDataBuilder $amountDataBuilder,
        CustomerDataBuilder $customerDataBuilder,
        ReferenceDataBuilder $referenceDataBuilder,
        ShippingAddressDataBuilder $shippingAddressDataBuilder,
        ShoppingCartDataBuilder $shoppingCartDataBuilder,
        array $configProviders = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->methodNameExtractor = $methodNameExtractor;
        $this->amountDataBuilder = $amountDataBuilder;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->referenceDataBuilder = $referenceDataBuilder;
        $this->shippingAddressDataBuilder = $shippingAddressDataBuilder;
        $this->shoppingCartDataBuilder = $shoppingCartDataBuilder;
        $this->configProviders = $configProviders;
    }

    public function build(CartInterface $quote): Order
    {
        $order = $this->orderFactory->create();

        $order->setAmountOfMoney($this->amountDataBuilder->build($quote));
        $order->setCustomer($this->customerDataBuilder->build($quote));
        $order->setReferences($this->referenceDataBuilder->build($quote));
        $order->setShipping($this->shippingAddressDataBuilder->build($quote));

        $methodCode = $this->methodNameExtractor->extract($quote->getPayment());
        $config = $this->configProviders[$methodCode] ?? null;

        if (!$config instanceof Config || !$config->isCartLines((int)$quote->getStoreId())) {
            return $order;
        }

        if ($cart = $this->shoppingCartDataBuilder->build($quote)) {
            $order->setShoppingCart($cart);
        }

        return $order;
    }
}
