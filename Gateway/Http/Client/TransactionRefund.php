<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Http\Client;

use OnlinePayments\Sdk\DataObject;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Gateway\Http\Client\AbstractTransaction;
use Worldline\HostedCheckout\Gateway\Request\RefundDataBuilder;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class TransactionRefund extends AbstractTransaction
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
        LoggerInterface $logger,
        WorldlineConfig $worldlineConfig,
        ClientProvider $clientProvider
    ) {
        parent::__construct($logger);
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
    }

    protected function process(array $data): DataObject
    {
        return $this->clientProvider->getClient()
            ->merchant($this->worldlineConfig->getMerchantId())
            ->payments()
            ->refundPayment(
                $data[RefundDataBuilder::TRANSACTION_ID],
                $data[RefundDataBuilder::REFUND_REQUEST]
            );
    }
}
