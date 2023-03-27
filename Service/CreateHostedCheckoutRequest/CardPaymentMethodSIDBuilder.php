<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use Worldline\HostedCheckout\Api\TokenManagerInterface;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Service\CreateRequest\ThreeDSecureDataBuilderInterface;

class CardPaymentMethodSIDBuilder
{
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

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilderInterface $threeDSecureDataBuilder,
        TokenManagerInterface $tokenManager
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
        $this->tokenManager = $tokenManager;
    }

    public function build(CartInterface $quote): ?CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();
        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote));
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));

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
}
