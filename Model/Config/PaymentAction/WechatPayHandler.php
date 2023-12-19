<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\PaymentAction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\PaymentRepositoryInterface;

class WechatPayHandler implements PaymentActionReplaceHandlerInterface
{
    private const WECHAT_PAY_PAYMENT_ACTION = 'authorize_capture';

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

        return PaymentProductsDetailsInterface::WECHAT_PAY_PRODUCT_ID === $paymentProductId
            ? self::WECHAT_PAY_PAYMENT_ACTION : null;
    }
}
