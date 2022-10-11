<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\Creator;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class Request
{
    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        WorldlineConfig $worldlineConfig,
        ClientProvider $clientProvider,
        LoggerInterface $logger
    ) {
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
        $this->logger = $logger;
    }

    /**
     * Documentation:
     * @link https://support.direct.ingenico.com/documentation/api/reference/#operation/CreateHostedCheckoutApi
     *
     * @param CreateHostedCheckoutRequest $request
     * @param int|null $storeId
     * @return CreateHostedCheckoutResponse
     * @throws LocalizedException
     */
    public function create(CreateHostedCheckoutRequest $request, ?int $storeId = null): CreateHostedCheckoutResponse
    {
        try {
            return $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->hostedCheckout()
                ->createHostedCheckout($request);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
            throw new LocalizedException(__('Sorry, but something went wrong'));
        }
    }
}
