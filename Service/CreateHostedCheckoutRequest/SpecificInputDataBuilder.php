<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInputFactory;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\HostedCheckout\Ui\ConfigProvider;

class SpecificInputDataBuilder
{
    public const HOSTED_CHECKOUT_SPECIFIC_INPUT = 'hosted_checkout_specific_input';

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
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    public function __construct(
        Config $config,
        Resolver $store,
        ManagerInterface $eventManager,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->eventManager = $eventManager;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
    }

    public function build(CartInterface $quote): HostedCheckoutSpecificInput
    {
        $hostedCheckoutSpecificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $hostedCheckoutSpecificInput->setLocale($this->store->getLocale());
        $storeId = (int)$quote->getStoreId();

        $hostedCheckoutSpecificInput->setReturnUrl($this->config->getReturnUrl($storeId));
        if ($variant = $this->config->getTemplateId($storeId)) {
            $hostedCheckoutSpecificInput->setVariant($variant);
        }

        $args = ['quote' => $quote, self::HOSTED_CHECKOUT_SPECIFIC_INPUT => $hostedCheckoutSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::HC_CODE . '_hosted_checkout_specific_input_builder', $args);

        return $hostedCheckoutSpecificInput;
    }
}
