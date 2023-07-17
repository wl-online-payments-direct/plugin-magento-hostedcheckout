<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\PaymentCore\Api\PaymentInfoCleanerInterface;
use Worldline\PaymentCore\Model\Order\RejectOrderException;

class SuccessTransactionChecker
{
    public const SUCCESSFUL_STATUS_CATEGORY = 'SUCCESSFUL';

    /**
     * @var AddressSaveProcessor
     */
    private $addressSaveProcessor;

    /**
     * @var PaymentInfoCleanerInterface
     */
    private $paymentInfoCleaner;

    /**
     * @var GetHostedCheckoutStatusService
     */
    private $getHCStatusService;

    public function __construct(
        AddressSaveProcessor $addressSaveProcessor,
        PaymentInfoCleanerInterface $paymentInfoCleaner,
        GetHostedCheckoutStatusService $getHCStatusService
    ) {
        $this->addressSaveProcessor = $addressSaveProcessor;
        $this->paymentInfoCleaner = $paymentInfoCleaner;
        $this->getHCStatusService = $getHCStatusService;
    }

    /**
     * @param CartInterface $quote
     * @param string $paymentId
     * @param string $returnId
     * @return GetHostedCheckoutResponse
     * @throws LocalizedException
     * @throws RejectOrderException
     */
    public function check(CartInterface $quote, string $paymentId, string $returnId): GetHostedCheckoutResponse
    {
        $payment = $quote->getPayment();
        if ($payment->getAdditionalInformation('return_id') !== $returnId) {
            throw new LocalizedException(__('Wrong return id'));
        }

        try {
            $response = $this->getHCStatusService->execute($paymentId, (int)$quote->getStoreId());
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('The payment has failed, please, try again'));
        }

        if (self::SUCCESSFUL_STATUS_CATEGORY !== $response->getCreatedPaymentOutput()->getPaymentStatusCategory()) {
            $quote->setIsActive(true);
            $this->addressSaveProcessor->saveAddress($quote);
            throw new RejectOrderException(__('The payment has rejected, please, try again'));
        }

        return $response;
    }
}
