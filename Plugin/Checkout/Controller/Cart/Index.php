<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Plugin\Checkout\Controller\Cart;

use Exception;
use Magento\Checkout\Model\Session;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Model\AddressSaveProcessor;
use Worldline\HostedCheckout\Model\PaymentInfoCleaner;
use Worldline\HostedCheckout\Service\Getter\Request;
use Worldline\HostedCheckout\UI\ConfigProvider;

/**
 * This plugin is needed to save shipping and/or billing addresses in case of return from hosted checkout page
 */
class Index
{
    public const REJECTED = 'REJECTED';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var AddressSaveProcessor
     */
    private $addressSaveProcessor;

    /**
     * @var Request
     */
    private $hcRequest;

    /**
     * @var PaymentInfoCleaner
     */
    private $paymentInfoCleaner;

    public function __construct(
        Session $checkoutSession,
        AddressSaveProcessor $addressSaveProcessor,
        Request $hcRequest,
        PaymentInfoCleaner $paymentInfoCleaner
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->addressSaveProcessor = $addressSaveProcessor;
        $this->hcRequest = $hcRequest;
        $this->paymentInfoCleaner = $paymentInfoCleaner;
    }

    public function beforeExecute(): void
    {
        $quote = $this->checkoutSession->getQuote();

        try {
            $payment = $quote->getPayment();
            if (!$payment
                || ($payment->getMethod() !== ConfigProvider::HC_CODE)
                || !$hcId = $payment->getAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID ?? '')
            ) {
                return;
            }

            $hcResponse = $this->hcRequest->create($hcId, (int)$quote->getStoreId());

            $worldlinePayment = $hcResponse->getCreatedPaymentOutput()->getPayment();
        } catch (Exception $e) {
            ; //expected behavior when customer presses go back button in their browser at the HC page
        }

        if (empty($worldlinePayment) || $worldlinePayment->getStatus() == self::REJECTED) {
            $this->addressSaveProcessor->saveAddress($quote);
            $this->paymentInfoCleaner->clean($quote);
        }
    }
}
