<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\PaymentCore\Model\Order\RejectOrderException;

class SuccessTransactionChecker
{
    public const SUCCESSFUL_STATUS_CATEGORY = 'SUCCESSFUL';

    /**
     * @var AddressSaveProcessor
     */
    private $addressSaveProcessor;

    /**
     * @var GetHostedCheckoutStatusService
     */
    private $getHCStatusService;

    public function __construct(
        AddressSaveProcessor $addressSaveProcessor,
        GetHostedCheckoutStatusService $getHCStatusService
    ) {
        $this->addressSaveProcessor = $addressSaveProcessor;
        $this->getHCStatusService = $getHCStatusService;
    }

    /**
     * @param CartInterface $quote
     * @param string $paymentId
     * @return GetHostedCheckoutResponse
     * @throws LocalizedException
     * @throws RejectOrderException
     */
    public function check(CartInterface $quote, string $paymentId): GetHostedCheckoutResponse
    {
        $payment = $quote->getPayment();

        if (!in_array($paymentId, $payment->getAdditionalInformation('payment_ids'), true)) {
            throw new LocalizedException(__('Wrong payment id'));
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
