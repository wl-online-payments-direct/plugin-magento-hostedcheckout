<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\Customer;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\OrderReferences;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order\ShoppingCartDataBuilder;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\MethodNameExtractorInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\GeneralDataBuilderInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\SurchargeDataBuilderInterface;
use Magento\Framework\Locale\Resolver;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderDataBuilder
{
    public const ORDER_DATA = 'order_data';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var MethodNameExtractorInterface
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
     * @var SurchargeDataBuilderInterface
     */
    private $surchargeDataBuilder;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @var Config[]
     */
    private $configProviders;

    /** @var Config */
    private $hostedConfig;

    /** @var Resolver */
    private $localeResolver;

    public function __construct(
        ManagerInterface                                 $eventManager,
        MethodNameExtractorInterface                     $methodNameExtractor,
        GeneralDataBuilderInterface                      $generalOrderDataBuilder,
        ShoppingCartDataBuilder                          $shoppingCartDataBuilder,
        SurchargeDataBuilderInterface                    $surchargeDataBuilder,
        GeneralSettingsConfigInterface                   $generalSettings,
        Config                                           $hostedConfig,
        Resolver                                         $localeResolver,
        array                                            $configProviders = []
    ) {
        $this->eventManager = $eventManager;
        $this->methodNameExtractor = $methodNameExtractor;
        $this->generalOrderDataBuilder = $generalOrderDataBuilder;
        $this->shoppingCartDataBuilder = $shoppingCartDataBuilder;
        $this->surchargeDataBuilder = $surchargeDataBuilder;
        $this->generalSettings = $generalSettings;
        $this->hostedConfig = $hostedConfig;
        $this->localeResolver = $localeResolver;
        $this->configProviders = $configProviders;
    }

    public function build(CartInterface $quote): Order
    {
        $storeId = (int)$quote->getStoreId();

        $order = $this->generalOrderDataBuilder->build($quote);

        $methodCode = $this->methodNameExtractor->extract($quote->getPayment());
        $config = $this->configProviders[$methodCode] ?? null;

        if ($this->generalSettings->isApplySurcharge($storeId) && (float)$quote->getGrandTotal() > 0.00001) {
            $order->setSurchargeSpecificInput($this->surchargeDataBuilder->build());
        }

        $args = ['quote' => $quote, self::ORDER_DATA => $order];
        $this->eventManager->dispatch(ConfigProvider::HC_CODE . '_order_data_builder', $args);

        $this->applyPaymentProductData($quote, $order);
        $this->applyDescriptor($quote, $order, $storeId);

        if (!$config || !method_exists($config, 'isCartLines') || !$config->isCartLines($storeId)) {
            return $order;
        }

        if ($cart = $this->shoppingCartDataBuilder->build($quote)) {
            $order->setShoppingCart($cart);
            $discount = $this->shoppingCartDataBuilder->getDiscountAdjustment($quote, $cart);

            if ($discount) {
                $order->setDiscount($discount);
            }
        }

        return $order;
    }

    /**
     * @param CartInterface $quote
     * @param Order $order
     * @param int $storeId
     */
    private function applyDescriptor(CartInterface $quote, Order $order, int $storeId): void
    {
        $paymentProductId = (int)$quote->getPayment()
            ->getAdditionalInformation('selected_payment_product');

        if ($paymentProductId) {
            return;
        }

        $descriptor = $this->hostedConfig->getValue(
            'fixed_soft_descriptor',
            $storeId
        );

        $references = $order->getReferences() ?? new OrderReferences();
        $references->setDescriptor($descriptor);
        $order->setReferences($references);
    }

    /**
     * @param CartInterface $quote
     * @param Order $order
     */
    private function applyPaymentProductData(CartInterface $quote, Order $order): void
    {
        $paymentProductId = (int)$quote->getPayment()
            ->getAdditionalInformation('selected_payment_product');

        if ($paymentProductId === PaymentProductsDetailsInterface::PLEDG_PRODUCT_ID) {
            $this->applyPledgData($order, $quote);
        }

        if ($paymentProductId === PaymentProductsDetailsInterface::LINXO_CONNECT_PRODUCT_ID) {
            $this->applyLinxoConnectData($order, $quote);
        }

        if ($paymentProductId === PaymentProductsDetailsInterface::ILLICADO_PRODUCT_ID) {
            $this->applyIllicadoData($order, $quote);
        }

        if ($paymentProductId === PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID) {
            $this->applyMealVoucherData($order, $quote);
        }
    }

    private function applyLinxoConnectData(Order $order, CartInterface $quote): void
    {
        $this->applyCommonRedirectData($order, $quote);
    }

    private function applyMealVoucherData(Order $order, CartInterface $quote): void
    {
        $this->applyCommonRedirectData($order, $quote);
    }

    private function applyPledgData(Order $order, CartInterface $quote): void
    {
        $this->applyCommonRedirectData($order, $quote);

        $billing = $quote->getBillingAddress();
        $customer = $order->getCustomer();

        $name = $customer->getPersonalInformation()->getName();
        $name->setFirstName($billing->getFirstname());
        $name->setSurname($billing->getLastname());

        $billingInput = $customer->getBillingAddress();
        $billingInput->setCountryCode($billing->getCountryId());
        $billingInput->setCity($billing->getCity());
        $billingInput->setZip($billing->getPostcode());
        $billingInput->setStreet($billing->getStreetLine(1) ?: '');

        $customer->setBillingAddress($billingInput);
    }

    private function applyCommonRedirectData(Order $order, CartInterface $quote): void
    {
        $storeId = (int)$quote->getStoreId();

        $customer = $order->getCustomer();
        if (!$customer) {
            $customer = new Customer();
            $order->setCustomer($customer);
        }

        $customer->setMerchantCustomerId(
            $quote->getCustomerId() ?: ('guest-' . $quote->getId())
        );

        $customer->getContactDetails()->setEmailAddress(
            $quote->getCustomerEmail()
        );

        $customer->setLocale($this->localeResolver->getLocale());

        $descriptor = $this->hostedConfig->getValue('fixed_soft_descriptor', $storeId) ?: $quote->getStore()->getName();

        $references = $order->getReferences() ?? new OrderReferences();
        $references->setDescriptor(substr($descriptor, 0, 15));
        $order->setReferences($references);
    }

    private function applyIllicadoData(Order $order, CartInterface $quote): void
    {
        $this->applyCommonRedirectData($order, $quote);
    }
}
