<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Api\Service\HostedCheckout;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;

interface GetHostedCheckoutStatusServiceInterface
{
    /**
     * Retrieve hosted checkout payment method details
     *
     * @param string $hostedCheckoutId
     * @param int|null $storeId
     * @return GetHostedCheckoutResponse
     * @throws LocalizedException
     */
    public function execute(string $hostedCheckoutId, ?int $storeId = null): GetHostedCheckoutResponse;
}
