<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Data;

use Magento\Sales\Api\Data\OrderPaymentInterface;

class OrderPaymentContainer
{
    /**
     * @var OrderPaymentInterface|null
     */
    private $payment;

    public function setPayment(OrderPaymentInterface $payment)
    {
        $this->payment = $payment;
        return $this;
    }

    public function getPayment(): ?OrderPaymentInterface
    {
        return $this->payment;
    }
}
