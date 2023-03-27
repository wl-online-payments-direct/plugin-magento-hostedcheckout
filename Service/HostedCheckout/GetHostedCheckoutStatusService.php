<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\HostedCheckout;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Api\Service\HostedCheckout\GetHostedCheckoutStatusServiceInterface;
use Worldline\PaymentCore\Api\ClientProviderInterface;
use Worldline\PaymentCore\Api\Config\WorldlineConfigInterface;

/**
 * @link https://support.direct.ingenico.com/documentation/api/reference/#operation/GetHostedCheckoutApi
 */
class GetHostedCheckoutStatusService implements GetHostedCheckoutStatusServiceInterface
{
    /**
     * @var ClientProviderInterface
     */
    private $clientProvider;

    /**
     * @var WorldlineConfigInterface
     */
    private $worldlineConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $cachedRequests = [];

    public function __construct(
        ClientProviderInterface $clientProvider,
        WorldlineConfigInterface $worldlineConfig,
        LoggerInterface $logger
    ) {
        $this->clientProvider = $clientProvider;
        $this->worldlineConfig = $worldlineConfig;
        $this->logger = $logger;
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
            $this->logger->debug($e->getMessage());
            throw new LocalizedException(__('GetHostedCheckoutApi request has failed. Please contact the provider.'));
        }
    }
}
