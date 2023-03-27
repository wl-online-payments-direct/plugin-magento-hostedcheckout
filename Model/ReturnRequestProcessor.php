<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\PaymentCore\Api\Data\OrderStateInterfaceFactory;
use Worldline\PaymentCore\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Api\SessionDataManagerInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Model\Order\RejectOrderException;
use Worldline\PaymentCore\Model\OrderState;

/**
 * @todo: this class should be refactored
 */
class ReturnRequestProcessor
{
    public const SUCCESS_STATE = 'success';
    public const WAITING_STATE = 'waiting';
    public const FAIL_STATE = 'fail';

    private const SUCCESSFUL_STATUS_CATEGORY = 'SUCCESSFUL';

    /**
     * @var SessionDataManagerInterface
     */
    private $sessionDataManager;

    /**
     * @var GetHostedCheckoutStatusService
     */
    private $getHostedCheckoutStatusService;

    /**
     * @var QuoteResourceInterface
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
        SessionDataManagerInterface $sessionDataManager,
        GetHostedCheckoutStatusService $getHostedCheckoutStatusService,
        QuoteResourceInterface $quoteResource,
        OrderFactory $orderFactory,
        PaymentInfoCleaner $paymentInfoCleaner,
        OrderStateInterfaceFactory $orderStateFactory,
        AddressSaveProcessor $addressSaveProcessor
    ) {
        $this->sessionDataManager = $sessionDataManager;
        $this->getHostedCheckoutStatusService = $getHostedCheckoutStatusService;
        $this->quoteResource = $quoteResource;
        $this->orderFactory = $orderFactory;
        $this->paymentInfoCleaner = $paymentInfoCleaner;
        $this->orderStateFactory = $orderStateFactory;
        $this->addressSaveProcessor = $addressSaveProcessor;
    }

    /**
     * @param string $paymentId
     * @param string $returnId
     * @return OrderState
     * @throws LocalizedException
     * @throws RejectOrderException
     */
    public function processRequest(string $paymentId, string $returnId): OrderState
    {
        if (!$paymentId || !$returnId) {
            throw new LocalizedException(__('Invalid request'));
        }

        $quote = $this->quoteResource->getQuoteByWorldlinePaymentId($paymentId);

        try {
            $response = $this->getHostedCheckoutStatusService->execute($paymentId, (int)$quote->getStoreId());
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('The payment has failed, please, try again'));
        }

        /** @var OrderState $orderState */
        $orderState = $this->orderStateFactory->create();
        if (self::SUCCESSFUL_STATUS_CATEGORY !== $response->getCreatedPaymentOutput()->getPaymentStatusCategory()) {
            $quote->setIsActive(true);
            $this->addressSaveProcessor->saveAddress($quote);
            $this->paymentInfoCleaner->clean($quote);
            throw new RejectOrderException(__('The payment has rejected, please, try again'));
        }

        $payment = $quote->getPayment();
        if ($payment->getAdditionalInformation('return_id') !== $returnId) {
            throw new LocalizedException(__('Wrong return id'));
        }

        $reservedOrderId = (string)$quote->getReservedOrderId();
        $orderState->setIncrementId($reservedOrderId);
        $orderState->setPaymentMethod((string)$payment->getMethod());

        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        if (!$order->getId()) {
            $this->sessionDataManager->reserveOrder($reservedOrderId);

            $redirectPaymentMethodSpecificOutput = $response->getCreatedPaymentOutput()
                ->getPayment()
                ->getPaymentOutput()
                ->getRedirectPaymentMethodSpecificOutput();

            if ($redirectPaymentMethodSpecificOutput) {
                $paymentProductId = (int) $redirectPaymentMethodSpecificOutput->getPaymentProductId();
                $this->quoteResource->setPaymentIdAndSave($quote, $paymentProductId);
                $orderState->setPaymentProductId($paymentProductId);
            }

            $orderState->setState(self::WAITING_STATE);

            return $orderState;
        }

        $orderState->setState(self::SUCCESS_STATE);
        $this->sessionDataManager->setOrderData($order);

        return $orderState;
    }
}
