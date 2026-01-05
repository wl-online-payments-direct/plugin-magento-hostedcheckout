<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Worldline\HostedCheckout\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\GPayThreeDSecure;
use OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest\SpecificInputDataBuilder as HCSpecificInputDataBuilder;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Model\ThreeDSecure\ParamsHandler;
use OnlinePayments\Sdk\Domain\RedirectionData;
use OnlinePayments\Sdk\Domain\MobilePaymentMethodHostedCheckoutSpecificInput;

class HostedMobilePaymentMethodSpecificInputDataBuilder
{
    const CHALLENGE_INDICATOR_NO_PREFERENCE = 'no-preference';
    const CHALLENGE_INDICATOR_REQUIRED = 'challenge-required';

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        GeneralSettingsConfigInterface $generalSettings,
        Config $config
    ) {
        $this->generalSettings = $generalSettings;
        $this->config = $config;
    }

    public function build(CartInterface $quote): MobilePaymentMethodHostedCheckoutSpecificInput
    {
        $mobilePaymentMethodSpecificInput = new MobilePaymentMethodHostedCheckoutSpecificInput();
        $mobilePaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode());
        $mobilePaymentMethodSpecificInput->setPaymentProduct320SpecificInput(
            $this->buildPaymentProduct320SpecificInput($quote->getStoreId(), (float)$quote->getGrandTotal())
        );

        return $mobilePaymentMethodSpecificInput;
    }

    private function buildPaymentProduct320SpecificInput($storeId, $baseSubtotalAmount):
        MobilePaymentProduct320SpecificInput
    {
        $paymentProduct320SpecificInput = new MobilePaymentProduct320SpecificInput();
        $gPayThreeDSecure = new GPayThreeDSecure();

        if (!$this->generalSettings->isThreeDEnabled($storeId)) {
            $gPayThreeDSecure->setSkipAuthentication(true);
        } else {
            if (!$this->generalSettings->isEnforceAuthEnabled($storeId)
                && !$this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $gPayThreeDSecure->setChallengeIndicator(self::CHALLENGE_INDICATOR_NO_PREFERENCE);
                $gPayThreeDSecure->setSkipAuthentication(false);
            } elseif ($this->generalSettings->isEnforceAuthEnabled($storeId)) {
                $gPayThreeDSecure->setChallengeIndicator(self::CHALLENGE_INDICATOR_REQUIRED);
                $gPayThreeDSecure->setSkipAuthentication(false);
            } elseif ($this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $threeDSExemptionType = $this->generalSettings->getAuthExemptionType($storeId);
                $threeDSExemptedAmount = $threeDSExemptionType === ParamsHandler::LOW_VALUE_EXEMPTION_TYPE ?
                    $this->generalSettings->getAuthLowValueAmount($storeId) :
                    $this->generalSettings->getAuthTransactionRiskAnalysisAmount($storeId);

                $gPayThreeDSecure->setSkipAuthentication(false);

                if ((float)$threeDSExemptedAmount >= (float)$baseSubtotalAmount) {
                    $gPayThreeDSecure->setExemptionRequest($threeDSExemptionType);
                    $gPayThreeDSecure->setChallengeIndicator(
                        $threeDSExemptionType === ParamsHandler::TRANSACTION_RISK_ANALYSIS_EXEMPTION_TYPE
                            ? ParamsHandler::ANALYSIS_PERFORMED_CHALLENGE_INDICATOR
                            : ParamsHandler::NO_CHALLENGE_REQUESTED_CHALLENGE_INDICATOR
                    );
                }
            }
            $gPayRedirectionData = new RedirectionData();
            $gPayRedirectionData->setReturnUrl($this->generalSettings->getReturnUrl(
                HCSpecificInputDataBuilder::RETURN_URL,
                $storeId
            ));
            $gPayThreeDSecure->setRedirectionData($gPayRedirectionData);
        }
        $paymentProduct320SpecificInput->setThreeDSecure($gPayThreeDSecure);

        return $paymentProduct320SpecificInput;
    }
}
