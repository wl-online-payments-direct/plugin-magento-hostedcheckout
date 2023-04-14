<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Api\OrderStateManagerInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\SessionDataManagerInterface;
use Worldline\PaymentCore\Model\Order\RejectOrderException;
use Worldline\PaymentCore\Model\OrderState\OrderState;

class ReturnRequestProcessor
{
    public const SUCCESS_STATE = 'success';
    public const WAITING_STATE = 'waiting';
    public const FAIL_STATE = 'fail';

    /**
     * @var SessionDataManagerInterface
     */
    private $sessionDataManager;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteResource;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderStateManagerInterface
     */
    private $orderStateManager;

    /**
     * @var SuccessTransactionChecker
     */
    private $successTransactionChecker;

    public function __construct(
        SessionDataManagerInterface $sessionDataManager,
        QuoteResourceInterface $quoteResource,
        OrderFactory $orderFactory,
        OrderStateManagerInterface $orderStateManager,
        SuccessTransactionChecker $successTransactionChecker
    ) {
        $this->sessionDataManager = $sessionDataManager;
        $this->quoteResource = $quoteResource;
        $this->orderFactory = $orderFactory;
        $this->orderStateManager = $orderStateManager;
        $this->successTransactionChecker = $successTransactionChecker;
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

        $response = $this->successTransactionChecker->check($quote, $paymentId, $returnId);

        $reservedOrderId = (string)$quote->getReservedOrderId();
        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        if (!$order->getId()) {
            return $this->processWaitingState($quote, $response);
        }

        return $this->processSuccessState($quote, $order);
    }

    private function processWaitingState(CartInterface $quote, GetHostedCheckoutResponse $response): OrderState
    {
        $reservedOrderId = (string)$quote->getReservedOrderId();
        $this->sessionDataManager->reserveOrder($reservedOrderId);
        $paymentCode = (string)$quote->getPayment()->getMethod();

        $orderState = $this->orderStateManager->create($reservedOrderId, $paymentCode, self::WAITING_STATE);

        $redirectPaymentMethodSpecificOutput = $response->getCreatedPaymentOutput()
            ->getPayment()
            ->getPaymentOutput()
            ->getRedirectPaymentMethodSpecificOutput();

        if ($redirectPaymentMethodSpecificOutput) {
            $paymentProductId = (int) $redirectPaymentMethodSpecificOutput->getPaymentProductId();
            $this->quoteResource->setPaymentIdAndSave($quote, $paymentProductId);
            $orderState->setPaymentProductId($paymentProductId);
        }

        return $orderState;
    }

    private function processSuccessState(CartInterface $quote, OrderInterface $order): OrderState
    {
        $this->sessionDataManager->setOrderData($order);

        return $this->orderStateManager->create(
            (string)$quote->getReservedOrderId(),
            (string)$quote->getPayment()->getMethod(),
            self::SUCCESS_STATE
        );
    }
}
