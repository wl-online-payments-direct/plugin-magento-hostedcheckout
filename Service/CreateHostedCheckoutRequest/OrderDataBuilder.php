<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use OnlinePayments\Sdk\Domain\Customer;
use OnlinePayments\Sdk\Domain\Order;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\Order\ShoppingCartDataBuilder;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\MethodNameExtractorInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\GeneralDataBuilderInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\SurchargeDataBuilderInterface;
use Magento\Framework\Locale\Resolver;

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

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
        StoreManagerInterface                            $storeManager,
        Config $hostedConfig,
        Resolver $localeResolver,
        array                                            $configProviders = []
    ) {
        $this->eventManager = $eventManager;
        $this->methodNameExtractor = $methodNameExtractor;
        $this->generalOrderDataBuilder = $generalOrderDataBuilder;
        $this->shoppingCartDataBuilder = $shoppingCartDataBuilder;
        $this->surchargeDataBuilder = $surchargeDataBuilder;
        $this->generalSettings = $generalSettings;
        $this->storeManager = $storeManager;
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

        $paymentProductId = (int)$quote->getPayment()->getAdditionalInformation('selected_payment_product');
        if ($paymentProductId === PaymentProductsDetailsInterface::PLEDG_PRODUCT_ID) {
            $this->applyPledgData($order, $quote);
        }

        if (!$paymentProductId) {
            $descriptor = $this->hostedConfig->getValue(
                'fixed_soft_descriptor',
                $storeId
            );

            $references = $order->getReferences() ?? new OrderReferences();
            $references->setDescriptor($descriptor);
            $order->setReferences($references);
        }

        if (!$config instanceof Config || !$config->isCartLines($storeId)) {
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

    private function applyPledgData(Order $order, CartInterface $quote): void
    {
        $billing = $quote->getBillingAddress();
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

        $name = $customer->getPersonalInformation()->getName();
        $name->setFirstName($billing->getFirstname());
        $name->setSurname($billing->getLastname());

        $billingInput = $customer->getBillingAddress();
        $billingInput->setCountryCode($billing->getCountryId());
        $billingInput->setCity($billing->getCity());
        $billingInput->setZip($billing->getPostcode());
        $billingInput->setStreet($billing->getStreetLine(1) ?: '');

        $customer->setBillingAddress($billingInput);

        $locale = $this->localeResolver->getLocale();
        $customer->setLocale($locale);

        $descriptor = $this->hostedConfig->getValue(
            'fixed_soft_descriptor',
            $storeId
        );

        if (!$descriptor) {
            $descriptor = $this->storeManager->getStore($storeId)->getName();
        }

        $order->getReferences()->setDescriptor(substr($descriptor, 0, 15));
    }
}
