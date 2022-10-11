<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\Creator\Request;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreateMandateRequest;
use OnlinePayments\Sdk\Domain\CreateMandateRequestFactory;
use OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentMethodSpecificInputBase;
use OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentMethodSpecificInputBaseFactory;
use OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentProduct771SpecificInputBase;
use OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentProduct771SpecificInputBaseFactory;
use Worldline\HostedCheckout\Gateway\Config\Config;

class SepaDirectDebitPaymentMethodSpecificInputBuilder
{
    /**
     * @var SepaDirectDebitPaymentMethodSpecificInputBaseFactory
     */
    private $debitPaymentMethodSpecificInputBaseFactory;

    /**
     * @var SepaDirectDebitPaymentProduct771SpecificInputBaseFactory
     */
    private $debitPaymentProduct771SpecificInputBaseFactory;

    /**
     * @var CreateMandateRequestFactory
     */
    private $createMandateRequestFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        SepaDirectDebitPaymentMethodSpecificInputBaseFactory $debitPaymentMethodSpecificInputBaseFactory,
        SepaDirectDebitPaymentProduct771SpecificInputBaseFactory $debitPaymentProduct771SpecificInputBaseFactory,
        CreateMandateRequestFactory $createMandateRequestFactory,
        Config $config,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->debitPaymentMethodSpecificInputBaseFactory = $debitPaymentMethodSpecificInputBaseFactory;
        $this->debitPaymentProduct771SpecificInputBaseFactory = $debitPaymentProduct771SpecificInputBaseFactory;
        $this->createMandateRequestFactory = $createMandateRequestFactory;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    public function build(CartInterface $quote): SepaDirectDebitPaymentMethodSpecificInputBase
    {
        /** @var SepaDirectDebitPaymentMethodSpecificInputBase $debitPaymentMethodSpecificInput */
        $debitPaymentMethodSpecificInput = $this->debitPaymentMethodSpecificInputBaseFactory->create();

        /** @var SepaDirectDebitPaymentProduct771SpecificInputBase $paymentProduct */
        $paymentProduct = $this->debitPaymentProduct771SpecificInputBaseFactory->create();

        /** @var CreateMandateRequest $paymentProduct */
        $mandate = $this->createMandateRequestFactory->create();

        $mandate->setCustomerReference($quote->getReservedOrderId());
        $mandate->setRecurrenceType($this->config->getDirectDebitRecurrenceType());
        $mandate->setSignatureType($this->config->getDirectDebitSignatureType());

        $locale = $this->scopeConfig->getValue('general/locale/code');
        $mandate->setLanguage(strtoupper(substr($locale, 0, 2)));

        $paymentProduct->setMandate($mandate);

        $debitPaymentMethodSpecificInput->setPaymentProduct771SpecificInput($paymentProduct);

        return $debitPaymentMethodSpecificInput;
    }
}
