<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
use Worldline\HostedCheckout\Api\TokenManagerInterface;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\ThreeDSecureDataBuilderInterface;
use Worldline\PaymentCore\Model\ThreeDSecure\ParamsHandler;

class CardPaymentMethodSIDBuilder
{
    const SINGLE_AMOUNT_USE_CASE = 'single-amount';
    const MAX_SUPPORTED_NUMBER_OF_ITEMS = 99;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ThreeDSecureDataBuilderInterface
     */
    private $threeDSecureDataBuilder;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilderInterface $threeDSecureDataBuilder,
        TokenManagerInterface $tokenManager,
        GeneralSettingsConfigInterface $generalSettings
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
        $this->tokenManager = $tokenManager;
        $this->generalSettings = $generalSettings;
    }

    public function build(CartInterface $quote): ?CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();
        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote));
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));
        $paymentProduct130SpecificInput = $this->buildPaymentProduct130SpecificInput($quote);
        if ($paymentProduct130SpecificInput) {
            $cardPaymentMethodSpecificInput->setPaymentProduct130SpecificInput($paymentProduct130SpecificInput);
        }

        if ($token = $this->tokenManager->getToken($quote)) {
            if ($this->tokenManager->isSepaToken($token)) {
                return null;
            }

            $cardPaymentMethodSpecificInput->setToken($token->getGatewayToken());
        }

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::HC_CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function buildPaymentProduct130SpecificInput(CartInterface $quote): ?PaymentProduct130SpecificInput
    {
        $storeId = (int)$quote->getStoreId();

        if (true === $this->generalSettings->isThreeDEnabled($storeId)) {
            $paymentProduct130SpecificInput = new PaymentProduct130SpecificInput();
            $paymentProduct130ThreeDSecure = new PaymentProduct130SpecificThreeDSecure();

            $paymentProduct130ThreeDSecure->setUsecase(self::SINGLE_AMOUNT_USE_CASE);
            $numberOfItems = $quote->getItemsQty() <= self::MAX_SUPPORTED_NUMBER_OF_ITEMS
                ? $quote->getItemsQty()
                : self::MAX_SUPPORTED_NUMBER_OF_ITEMS;
            $paymentProduct130ThreeDSecure->setNumberOfItems($numberOfItems);

            if (!$this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $paymentProduct130ThreeDSecure->setAcquirerExemption(false);
            } elseif ($this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $threeDSExemptionType = $this->generalSettings->getAuthExemptionType($storeId);
                $threeDSExemptedAmount = $threeDSExemptionType === ParamsHandler::LOW_VALUE_EXEMPTION_TYPE ?
                    $this->generalSettings->getAuthLowValueAmount($storeId) :
                    $this->generalSettings->getAuthTransactionRiskAnalysisAmount($storeId);

                (float)$threeDSExemptedAmount >= (float)$quote->getGrandTotal() ?
                    $paymentProduct130ThreeDSecure->setAcquirerExemption(true) :
                    $paymentProduct130ThreeDSecure->setAcquirerExemption(false);
            }
            $paymentProduct130SpecificInput->setThreeDSecure($paymentProduct130ThreeDSecure);

            return $paymentProduct130SpecificInput;
        }

        return null;
    }
}
