<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\Mandates;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreateMandateRequest;
use OnlinePayments\Sdk\Domain\CreateMandateRequestFactory;
use Worldline\HostedCheckout\Api\Service\Mandates\MandateDataBuilderInterface;

class MandateDataBuilder implements MandateDataBuilderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CreateMandateRequestFactory
     */
    private $createMandateRequestFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CreateMandateRequestFactory $createMandateRequestFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->createMandateRequestFactory = $createMandateRequestFactory;
    }

    public function getMandate(CartInterface $quote, Config $config): CreateMandateRequest
    {
        /** @var CreateMandateRequest $paymentProduct */
        $mandate = $this->createMandateRequestFactory->create();

        $mandate->setCustomerReference($quote->getReservedOrderId());
        $mandate->setRecurrenceType($config->getDirectDebitRecurrenceType());
        $mandate->setSignatureType($config->getDirectDebitSignatureType());

        $locale = $this->scopeConfig->getValue('general/locale/code');
        $mandate->setLanguage(strtoupper(substr($locale, 0, 2)));

        return $mandate;
    }
}
