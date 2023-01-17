<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\PaymentCore\Api\Data\OrderStateInterfaceFactory;
use Worldline\PaymentCore\Model\Order\RejectOrderException;
use Worldline\PaymentCore\Model\OrderState;
use Worldline\PaymentCore\Model\ResourceModel\Quote as QuoteResource;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ReturnRequestProcessor
{
    public const SUCCESS_STATE = 'success';
    public const WAITING_STATE = 'waiting';
    public const FAIL_STATE = 'fail';

    private const SUCCESSFUL_STATUS_CATEGORY = 'SUCCESSFUL';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetHostedCheckoutStatusService
     */
    private $getRequest;

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var PaymentInfoCleaner
     */
    private $paymentInfoCleaner;

    /**
     * @var OrderStateInterfaceFactory
     */
    private $orderStateFactory;

    /**
     * @var AddressSaveProcessor
     */
    private $addressSaveProcessor;

    public function __construct(
        Session $checkoutSession,
        LoggerInterface $logger,
        GetHostedCheckoutStatusService $getRequest,
        QuoteResource $quoteResource,
        OrderFactory $orderFactory,
        PaymentInfoCleaner $paymentInfoCleaner,
        OrderStateInterfaceFactory $orderStateFactory,
        AddressSaveProcessor $addressSaveProcessor
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->getRequest = $getRequest;
        $this->quoteResource = $quoteResource;
        $this->orderFactory = $orderFactory;
        $this->paymentInfoCleaner = $paymentInfoCleaner;
        $this->orderStateFactory = $orderStateFactory;
        $this->addressSaveProcessor = $addressSaveProcessor;
    }

    public function processRequest(string $paymentId, string $returnId): OrderState
    {
        if (!$paymentId || !$returnId) {
            throw new LocalizedException(__('Invalid request'));
        }

        $quote = $this->quoteResource->getQuoteByWorldlinePaymentId($paymentId);

        try {
            $request = $this->getRequest->execute($paymentId, (int)$quote->getStoreId());
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new LocalizedException(__('The payment has failed, please, try again'));
        }

        /** @var OrderState $orderState */
        $orderState = $this->orderStateFactory->create();

        if (self::SUCCESSFUL_STATUS_CATEGORY !== $request->getCreatedPaymentOutput()->getPaymentStatusCategory()) {
            $quote->setIsActive(true);
            $this->addressSaveProcessor->saveAddress($quote);
            $this->paymentInfoCleaner->clean($quote);
            throw new RejectOrderException(__('The payment has rejected, please, try again'));
        }

        if ($quote->getPayment()->getAdditionalInformation('return_id') !== $returnId) {
            throw new LocalizedException(__('Wrong return id'));
        }

        $reservedOrderId = (string)$quote->getReservedOrderId();
        $orderState->setIncrementId($reservedOrderId);

        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        if (!$order->getId()) {
            $orderState->setState(self::WAITING_STATE);
            $this->checkoutSession->clearStorage();
            $this->checkoutSession->setLastRealOrderId($reservedOrderId);

            return $orderState;
        }

        $orderState->setState(self::SUCCESS_STATE);
        $this->checkoutSession->setLastOrderId((int) $order->getId());
        $this->checkoutSession->setLastRealOrderId($reservedOrderId);
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());

        return $orderState;
    }
}
