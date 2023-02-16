<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\Order;
use Worldline\HostedCheckout\Model\MealvouchersProductTypeBuilder;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order\ShoppingCartDataBuilder;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\GeneralDataBuilderInterface;
use Worldline\PaymentCore\Model\MethodNameExtractor;

class OrderDataBuilder
{
    public const ORDER_DATA = 'order_data';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var MethodNameExtractor
     */
    private $methodNameExtractor;

    /**
     * @var GeneralDataBuilderInterface
     */
    private $generalOrderDataBuilder;

    /**
     * @var ShoppingCartDataBuilder
     */
    private $shoppingCartDataBuilder;

    /**
     * @var MealvouchersProductTypeBuilder
     */
    private $mealvouchersProductTypeBuilder;

    /**
     * @var Config[]
     */
    private $configProviders;

    public function __construct(
        ManagerInterface $eventManager,
        MethodNameExtractor $methodNameExtractor,
        GeneralDataBuilderInterface $generalOrderDataBuilder,
        ShoppingCartDataBuilder $shoppingCartDataBuilder,
        MealvouchersProductTypeBuilder $mealvouchersProductTypeBuilder,
        array $configProviders = []
    ) {
        $this->eventManager = $eventManager;
        $this->methodNameExtractor = $methodNameExtractor;
        $this->generalOrderDataBuilder = $generalOrderDataBuilder;
        $this->shoppingCartDataBuilder = $shoppingCartDataBuilder;
        $this->mealvouchersProductTypeBuilder = $mealvouchersProductTypeBuilder;
        $this->configProviders = $configProviders;
    }

    public function build(CartInterface $quote): Order
    {
        $storeId = (int)$quote->getStoreId();

        $order = $this->generalOrderDataBuilder->build($quote);

        $methodCode = $this->methodNameExtractor->extract($quote->getPayment());
        $config = $this->configProviders[$methodCode] ?? null;

        $args = ['quote' => $quote, self::ORDER_DATA => $order];
        $this->eventManager->dispatch(ConfigProvider::HC_CODE . '_order_data_builder', $args);

        if (!$config instanceof Config || !$config->isCartLines($storeId)) {
            return $order;
        }

        if ($config->isProcessMealvouchers($storeId)) {
            $this->mealvouchersProductTypeBuilder->shapeMealvouchersProductType($quote);
        }

        if ($cart = $this->shoppingCartDataBuilder->build($quote)) {
            $order->setShoppingCart($cart);
        }

        return $order;
    }
}
