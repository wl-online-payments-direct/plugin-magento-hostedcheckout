<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Plugin\Magento\Checkout\Controller\Cart\Index;

use Magento\Checkout\Controller\Index\Index;
use Magento\Framework\Exception\LocalizedException;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Model\AddressSaveProcessor;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\PaymentInfoCleanerInterface;

/**
 * This plugin is needed to save shipping and/or billing addresses in case of return from the hosted checkout page
 */
class SaveAddress
{
    public const REJECTED = 'REJECTED';

    /**
     * @var AddressSaveProcessor
     */
    private $addressSaveProcessor;

    /**
     * @var GetHostedCheckoutStatusService
     */
    private $hcRequest;

    /**
     * @var PaymentInfoCleanerInterface
     */
    private $paymentInfoCleaner;

    public function __construct(
        AddressSaveProcessor $addressSaveProcessor,
        GetHostedCheckoutStatusService $hcRequest,
        PaymentInfoCleanerInterface $paymentInfoCleaner
    ) {
        $this->addressSaveProcessor = $addressSaveProcessor;
        $this->hcRequest = $hcRequest;
        $this->paymentInfoCleaner = $paymentInfoCleaner;
    }

    public function beforeExecute(Index $subject): void
    {
        $quote = $subject->getOnepage()->getQuote();
        if (!$quote) {
            return;
        }

        $payment = $quote->getPayment();

        if (!$payment
            || ($payment->getMethod() !== ConfigProvider::HC_CODE)
            || !$hcId = $payment->getAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID ?? '')
        ) {
            return;
        }

        try {
            $hcResponse = $this->hcRequest->execute($hcId, (int)$quote->getStoreId());
            $worldlinePayment = $hcResponse->getCreatedPaymentOutput()->getPayment();
            if (!$worldlinePayment || $worldlinePayment->getStatus() === self::REJECTED) {
                $this->addressSaveProcessor->saveAddress($quote);
            }
        } catch (LocalizedException $e) {
            //expected behavior when customer presses go back button in their browser at the HC page
            $this->addressSaveProcessor->saveAddress($quote);
            $this->paymentInfoCleaner->clean($quote);
        }
    }
}
