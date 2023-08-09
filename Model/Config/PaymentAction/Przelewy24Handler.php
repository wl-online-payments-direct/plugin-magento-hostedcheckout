<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\PaymentAction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\PaymentRepositoryInterface;

class Przelewy24Handler implements PaymentActionReplaceHandlerInterface
{
    private const PRZELEWY24_PAYMENT_ACTION = 'authorize_capture';

    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function getPaymentAction(OrderPaymentInterface $payment): ?string
    {
        $incrementId = $payment->getOrder()->getIncrementId();

        $worldlinePayment = $this->paymentRepository->get($incrementId);
        $paymentProductId = (int) $worldlinePayment->getPaymentProductId();

        return PaymentProductsDetailsInterface::PRZELEWY24_PRODUCT_ID === $paymentProductId
            ? self::PRZELEWY24_PAYMENT_ACTION : null;
    }
}
