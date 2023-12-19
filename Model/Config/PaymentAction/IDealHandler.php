<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\PaymentAction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\PaymentRepositoryInterface;

class IDealHandler implements PaymentActionReplaceHandlerInterface
{
    private const IDEAL_PAYMENT_ACTION = 'authorize_capture';

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

        return PaymentProductsDetailsInterface::IDEAL_PRODUCT_ID === $paymentProductId
            ? self::IDEAL_PAYMENT_ACTION : null;
    }
}
