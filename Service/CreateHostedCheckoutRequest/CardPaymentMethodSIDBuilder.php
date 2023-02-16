<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Service\CreateRequest\ThreeDSecureDataBuilder;

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
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ThreeDSecureDataBuilder
     */
    private $threeDSecureDataBuilder;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilder $threeDSecureDataBuilder
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
    }

    public function build(CartInterface $quote): CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput */
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();
        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote));
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));

        if ($token = $this->getToken($quote)) {
            $cardPaymentMethodSpecificInput->setToken($token);
        }

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::HC_CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function getToken(CartInterface $quote): ?string
    {
        $payment = $quote->getPayment();
        if (!$publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH)) {
            return null;
        }

        $token = $this->paymentTokenManagement->getByPublicHash($publicHash, (int) $quote->getCustomerId());
        return $token instanceof PaymentTokenInterface ? $token->getGatewayToken() : null;
    }
}
