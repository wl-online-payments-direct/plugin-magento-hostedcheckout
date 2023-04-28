<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\HostedCheckout\Model\Data\OrderPaymentContainer;

/**
 * Store the order payment to use it further to identify what payment method has been used by the customer
 * to replace payment action for some payment methods in config
 */
class StorePayment implements ObserverInterface
{
    /**
     * @var OrderPaymentContainer
     */
    private $orderPaymentContainer;

    /**
     * @var string[]
     */
    private $paymentMethods;

    public function __construct(OrderPaymentContainer $orderPaymentStorage, array $paymentMethods = [])
    {
        $this->orderPaymentContainer = $orderPaymentStorage;
        $this->paymentMethods = $paymentMethods;
    }

    public function execute(Observer $observer): void
    {
        if ($observer->getPayment() instanceof OrderPaymentInterface
            && in_array($observer->getPayment()->getMethod(), $this->paymentMethods, true)) {
            $this->orderPaymentContainer->setPayment($observer->getPayment());
        }
    }
}
