<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Http\Client;

use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Psr\Log\LoggerInterface;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\GetHostedCheckoutStatusService;
use Worldline\PaymentCore\Gateway\Http\Client\AbstractTransaction;

class TransactionSale extends AbstractTransaction
{
    /**
     * @var GetHostedCheckoutStatusService
     */
    private $request;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    public function __construct(
        LoggerInterface                $logger,
        GetHostedCheckoutStatusService $request,
        GeneralSettingsConfigInterface $generalSettings
    ) {
        parent::__construct($logger);
        $this->request = $request;
        $this->generalSettings = $generalSettings;
    }

    /**
     * @param array $data
     * @return GetHostedCheckoutResponse
     * @throws LocalizedException
     */
    protected function process(array $data): GetHostedCheckoutResponse
    {
        $hostedCheckoutId = (string)($data[PaymentDataBuilder::HOSTED_CHECKOUT_ID] ?? '');
        if (!$hostedCheckoutId) {
            throw new LocalizedException(__('Hosted checkout id is missing'));
        }

        $response = $this->request->execute($hostedCheckoutId, $data[PaymentDataBuilder::STORE_ID]);
        $this->writeLogIfNeeded($data, $response);

        return $response;
    }

    /**
     * Write log for line items feature
     *
     * Setting: "Submit Customer Cart Items Data to Worldline"
     *
     * @param array $data
     * @param GetHostedCheckoutResponse $response
     * @return void
     * @throws LocalizedException
     */
    private function writeLogIfNeeded(array $data, GetHostedCheckoutResponse $response): void
    {
        $orderAmount = $data[PaymentDataBuilder::AMOUNT] ?? 0;
        $paymentOutput = $response->getCreatedPaymentOutput()->getPayment()->getPaymentOutput();
        $transactionAmount = $paymentOutput->getAmountOfMoney()->getAmount();
        if ($paymentOutput->getSurchargeSpecificOutput()) {
            $transactionAmount += $paymentOutput->getSurchargeSpecificOutput()->getSurchargeAmount()->getAmount();
        }

        if ($transactionAmount !== $orderAmount) {
            $this->logger->warning(__('Wrong amount'), [
                PaymentDataBuilder::HOSTED_CHECKOUT_ID => $response->getCreatedPaymentOutput()->getPayment()->getId(),
                'transaction_amount_of_money' => $transactionAmount,
                'order_amount_of_money' => $orderAmount,
            ]);

            $this->logger->warning(__('Order with amount discrepancy created'), [
                'quote_id' => $paymentOutput->getMerchantParameters(),
                'order_amount' => $orderAmount,
                'paid_amount' => $transactionAmount,
                'discrepancy' => $transactionAmount - $orderAmount
            ]);

            if (!$this->generalSettings->isAmountDiscrepancyEnabled()) {
                $this->logger->info(__('Skipping order creation due to amount discrepancy'), []);
                throw new LocalizedException(__('Wrong amount'));
            }
        }
    }
}
