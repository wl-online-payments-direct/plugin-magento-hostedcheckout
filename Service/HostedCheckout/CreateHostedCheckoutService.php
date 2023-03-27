<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\HostedCheckout;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Api\Service\HostedCheckout\CreateHostedCheckoutServiceInterface;
use Worldline\PaymentCore\Api\ClientProviderInterface;
use Worldline\PaymentCore\Api\Config\WorldlineConfigInterface;

/**
 * @link https://support.direct.ingenico.com/documentation/api/reference/#operation/CreateHostedCheckoutApi
 */
class CreateHostedCheckoutService implements CreateHostedCheckoutServiceInterface
{
    /**
     * @var WorldlineConfigInterface
     */
    private $worldlineConfig;

    /**
     * @var ClientProviderInterface
     */
    private $clientProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        WorldlineConfigInterface $worldlineConfig,
        ClientProviderInterface $clientProvider,
        LoggerInterface $logger
    ) {
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
        $this->logger = $logger;
    }

    /**
     * Create hosted checkout payment
     *
     * @param CreateHostedCheckoutRequest $request
     * @param int|null $storeId
     * @return CreateHostedCheckoutResponse
     * @throws LocalizedException
     */
    public function execute(CreateHostedCheckoutRequest $request, ?int $storeId = null): CreateHostedCheckoutResponse
    {
        try {
            return $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->hostedCheckout()
                ->createHostedCheckout($request);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new LocalizedException(
                __('CreateHostedCheckoutApi request has failed. Please contact the provider.')
            );
        }
    }
}
