<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Infrastructure\Plugin\Service\HostedCheckout;

use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponseFactory;
use Worldline\HostedCheckout\Infrastructure\StubData\Service\HostedCheckout\GetHostedCheckoutServiceResponse;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

class GetHostedCheckoutStatusServiceMock
{
    /**
     * @var ServiceStubSwitcherInterface
     */
    private $serviceStubSwitcher;

    /**
     * @var GetHostedCheckoutResponseFactory
     */
    private $getHostedCheckoutResponseFactory;

    public function __construct(
        ServiceStubSwitcherInterface $serviceStubSwitcher,
        GetHostedCheckoutResponseFactory $getHostedCheckoutResponseFactory
    ) {
        $this->serviceStubSwitcher = $serviceStubSwitcher;
        $this->getHostedCheckoutResponseFactory = $getHostedCheckoutResponseFactory;
    }

    /**
     * @param GetHostedCheckoutStatusService $subject
     * @param callable $proceed
     * @param string $hostedCheckoutId
     * @param int|null $storeId
     * @return GetHostedCheckoutResponse
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        GetHostedCheckoutStatusService $subject,
        callable $proceed,
        string $hostedCheckoutId,
        ?int $storeId = null
    ): GetHostedCheckoutResponse {
        if ($this->serviceStubSwitcher->isEnabled()) {
            $response = $this->getHostedCheckoutResponseFactory->create();
            $response->fromJson(GetHostedCheckoutServiceResponse::getData($hostedCheckoutId));

            return $response;
        }

        return $proceed($hostedCheckoutId, $storeId);
    }
}
