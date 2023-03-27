<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Api\Service\HostedCheckout;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;

interface CreateHostedCheckoutServiceInterface
{
    /**
     * Create hosted checkout payment
     *
     * @param CreateHostedCheckoutRequest $request
     * @param int|null $storeId
     * @return CreateHostedCheckoutResponse
     * @throws LocalizedException
     */
    public function execute(CreateHostedCheckoutRequest $request, ?int $storeId = null): CreateHostedCheckoutResponse;
}
