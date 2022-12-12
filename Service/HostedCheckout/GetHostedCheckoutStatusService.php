<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\HostedCheckout;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\HostedCheckout\Api\Service\HostedCheckout\GetHostedCheckoutStatusServiceInterface;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

/**
 * @link https://support.direct.ingenico.com/documentation/api/reference/#operation/GetHostedCheckoutApi
 */
class GetHostedCheckoutStatusService implements GetHostedCheckoutStatusServiceInterface
{
    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var array
     */
    private $cachedRequests = [];

    public function __construct(
        ClientProvider $clientProvider,
        WorldlineConfig $worldlineConfig
    ) {
        $this->clientProvider = $clientProvider;
        $this->worldlineConfig = $worldlineConfig;
    }

    /**
     * Retrieve hosted checkout payment method details
     *
     * @param string $hostedCheckoutId
     * @param int|null $storeId
     * @return GetHostedCheckoutResponse
     * @throws LocalizedException
     */
    public function execute(string $hostedCheckoutId, ?int $storeId = null): GetHostedCheckoutResponse
    {
        if (isset($this->cachedRequests[$hostedCheckoutId])) {
            return $this->cachedRequests[$hostedCheckoutId];
        }

        try {
            $this->cachedRequests[$hostedCheckoutId] = $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->hostedCheckout()
                ->getHostedCheckout($hostedCheckoutId);

            return $this->cachedRequests[$hostedCheckoutId];
        } catch (\Exception $e) {
            throw new LocalizedException(__('GetHostedCheckoutApi request has failed. Please contact the provider.'));
        }
    }
}
