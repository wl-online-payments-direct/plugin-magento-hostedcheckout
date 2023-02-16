<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config;

use Magento\Sales\Api\Data\OrderPaymentInterface;

interface PaymentActionReplaceHandlerInterface
{
    public function getPaymentAction(OrderPaymentInterface $payment): ?string;
}
