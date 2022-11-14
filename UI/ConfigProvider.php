<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\UI;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\PaymentCore\Model\Ui\PaymentIconsProvider;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    public const HC_CODE = 'worldline_hosted_checkout';

    /**
     * @var string
     */
    public const HC_VAULT_CODE = 'worldline_hosted_checkout_vault';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var PaymentIconsProvider
     */
    private $iconProvider;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        SessionManagerInterface $session,
        PaymentIconsProvider $iconProvider
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->session = $session;
        $this->iconProvider = $iconProvider;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        try {
            $storeId = (int) $this->session->getStoreId();

            return [
                'payment' => [
                    self::HC_CODE => [
                        'isActive' => $this->config->isActive($storeId),
                        'icons' => $this->iconProvider->getIcons($storeId),
                        'hcVaultCode' => self::HC_VAULT_CODE
                    ]
                ]
            ];
        } catch (Exception $e) {
            $this->logger->critical($e);
            return [];
        }
    }
}
