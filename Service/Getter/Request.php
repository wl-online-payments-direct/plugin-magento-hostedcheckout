<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\Getter;

use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class Request
{
    /**
     * @var array
     */
    private $cachedRequests = [];

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    public function __construct(
        ClientProvider $clientProvider,
        WorldlineConfig $worldlineConfig
    ) {
        $this->clientProvider = $clientProvider;
        $this->worldlineConfig = $worldlineConfig;
    }

    /**
     * Documentation:
     * @link: https://support.direct.ingenico.com/documentation/api/reference/#operation/GetHostedCheckoutApi
     *
     * @param string $hostedCheckoutId
     * @param int|null $storeId
     * @return GetHostedCheckoutResponse
     * @throws \Exception
     */
    public function create(string $hostedCheckoutId, ?int $storeId = null): GetHostedCheckoutResponse
    {
        if (!isset($this->cachedRequests[$hostedCheckoutId])) {
            $this->cachedRequests[$hostedCheckoutId] = $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->hostedCheckout()
                ->getHostedCheckout($hostedCheckoutId);
        }

        return $this->cachedRequests[$hostedCheckoutId];
    }
}
