<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Plugin\Payment\Model\Method\Adapter;

use Magento\Payment\Model\Method\Adapter;
use Worldline\HostedCheckout\Model\Config\PaymentActionReplaceHandlerInterface;
use Worldline\HostedCheckout\Model\Data\OrderPaymentContainer;

class ReplacePaymentAction
{
    /**
     * @var PaymentActionReplaceHandlerInterface[]
     */
    private $handlers;

    /**
     * @var OrderPaymentContainer
     */
    private $orderPaymentContainer;

    public function __construct(OrderPaymentContainer $orderPaymentContainer, $handlers = [])
    {
        $this->handlers = $handlers;
        $this->orderPaymentContainer = $orderPaymentContainer;
    }

    /**
     * Change the payment action value for some WL payments
     *
     * @param Adapter $subject
     * @param string|null $result
     * @return strings
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfigPaymentAction(Adapter $subject, $result = null): ?string
    {
        if (!$payment = $this->orderPaymentContainer->getPayment()) {
            return $result;
        }

        foreach ($this->handlers as $handler) {
            if (!$handler instanceof PaymentActionReplaceHandlerInterface) {
                continue;
            }

            if ($paymentAction = $handler->getPaymentAction($payment)) {
                return (string) $paymentAction;
            }
        }

        return $result;
    }
}
