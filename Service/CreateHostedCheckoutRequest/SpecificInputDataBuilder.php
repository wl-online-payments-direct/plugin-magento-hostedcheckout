<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckoutFactory;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFilterFactory;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckoutFactory;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInputFactory;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;

class SpecificInputDataBuilder
{
    public const HOSTED_CHECKOUT_SPECIFIC_INPUT = 'hosted_checkout_specific_input';
    public const RETURN_URL = 'wl_hostedcheckout/returns/returnUrl';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Resolver
     */
    private $store;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    /**
     * @var CardPaymentMethodSpecificInputForHostedCheckoutFactory
     */
    private $cardPaymentMethodDataFactory;

    /**
     * @var PaymentProductFilterFactory
     */
    private $paymentProductFilterFactory;

    /**
     * @var PaymentProductFiltersHostedCheckoutFactory
     */
    private $paymentProductFiltersHCFactory;

    public function __construct(
        Config $config,
        Resolver $store,
        ManagerInterface $eventManager,
        GeneralSettingsConfigInterface $generalSettings,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory,
        CardPaymentMethodSpecificInputForHostedCheckoutFactory $cardPaymentMethodDataFactory,
        PaymentProductFilterFactory $paymentProductFilterFactory,
        PaymentProductFiltersHostedCheckoutFactory $paymentProductFiltersHCFactory
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->eventManager = $eventManager;
        $this->generalSettings = $generalSettings;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
        $this->cardPaymentMethodDataFactory = $cardPaymentMethodDataFactory;
        $this->paymentProductFilterFactory = $paymentProductFilterFactory;
        $this->paymentProductFiltersHCFactory = $paymentProductFiltersHCFactory;
    }

    public function build(CartInterface $quote): HostedCheckoutSpecificInput
    {
        $storeId = (int)$quote->getStoreId();

        /** @var HostedCheckoutSpecificInput $hostedCheckoutSpecificInput */
        $hostedCheckoutSpecificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $hostedCheckoutSpecificInput->setLocale($this->store->getLocale());

        $attempts = $this->config->getAllowedAttempts($storeId);
        if ($attempts) {
            $hostedCheckoutSpecificInput->setAllowedNumberOfPaymentAttempts($attempts);
        }

        $sessionTimeout = $this->config->getSessionTimeout($storeId);
        if ($sessionTimeout) {
            $hostedCheckoutSpecificInput->setSessionTimeout($sessionTimeout);
        }

        /** @var CardPaymentMethodSpecificInputForHostedCheckout $cardPaymentMethodSpecificInputForHC */
        $cardPaymentMethodSpecificInputForHC = $this->cardPaymentMethodDataFactory->create();
        $cardPaymentMethodSpecificInputForHC->setGroupCards($this->config->isGroupCardsEnabled($storeId));
        $hostedCheckoutSpecificInput->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInputForHC);

        $hostedCheckoutSpecificInput->setReturnUrl($this->generalSettings->getReturnUrl(self::RETURN_URL, $storeId));
        if ($variant = $this->config->getTemplateId($storeId)) {
            $hostedCheckoutSpecificInput->setVariant($variant);
        }

        /** @var PaymentProductFilter $paymentProductFilterMealVoucher */
        $paymentProductFilterMealVoucher = $this->paymentProductFilterFactory->create();
        $paymentProductFilterMealVoucher->setProducts([PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID]);

        /** @var PaymentProductFiltersHostedCheckout $paymentProductFiltersHC */
        $paymentProductFiltersHC = $this->paymentProductFiltersHCFactory->create();
        $paymentProductFiltersHC->setExclude($paymentProductFilterMealVoucher);

        $hostedCheckoutSpecificInput->setPaymentProductFilters($paymentProductFiltersHC);

        $args = ['quote' => $quote, self::HOSTED_CHECKOUT_SPECIFIC_INPUT => $hostedCheckoutSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::HC_CODE . '_hosted_checkout_specific_input_builder', $args);

        return $hostedCheckoutSpecificInput;
    }
}
