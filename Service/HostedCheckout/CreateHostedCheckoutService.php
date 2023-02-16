<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\HostedCheckout;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Worldline\HostedCheckout\Api\Service\HostedCheckout\CreateHostedCheckoutServiceInterface;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

/**
 * @link https://support.direct.ingenico.com/documentation/api/reference/#operation/CreateHostedCheckoutApi
 */
class CreateHostedCheckoutService implements CreateHostedCheckoutServiceInterface
{
    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    public function __construct(
        WorldlineConfig $worldlineConfig,
        ClientProvider $clientProvider
    ) {
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
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
            throw new LocalizedException(
                __('CreateHostedCheckoutApi request has failed. Please contact the provider.')
            );
        }
    }
}
