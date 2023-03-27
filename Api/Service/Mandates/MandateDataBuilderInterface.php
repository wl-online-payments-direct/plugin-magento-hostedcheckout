<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Api\Service\Mandates;

use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreateMandateRequest;

interface MandateDataBuilderInterface
{
    public function getMandate(CartInterface $quote, Config $config): CreateMandateRequest;
}
