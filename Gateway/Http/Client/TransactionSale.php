<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Http\Client;

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

    public function __construct(LoggerInterface $logger, GetHostedCheckoutStatusService $request)
    {
        parent::__construct($logger);
        $this->request = $request;
    }

    /**
     * @param array $data
     * @return GetHostedCheckoutResponse
     * @throws LocalizedException
     */
    protected function process(array $data): GetHostedCheckoutResponse
    {
        $hostedCheckoutId = (string) ($data[PaymentDataBuilder::HOSTED_CHECKOUT_ID] ?? '');
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
        $transactionAmountOfMoney = $response->getCreatedPaymentOutput()
            ->getPayment()
            ->getPaymentOutput()
            ->getAmountOfMoney()
            ->getAmount();
        $orderAmountOfMoney = $data[PaymentDataBuilder::AMOUNT] ?? 0;

        if ($transactionAmountOfMoney !== $orderAmountOfMoney) {
            $this->logger->warning(__('Wrong amount'), [
                PaymentDataBuilder::HOSTED_CHECKOUT_ID => $response->getCreatedPaymentOutput()->getPayment()->getId(),
                'transaction_amount_of_money' => $transactionAmountOfMoney,
                'order_amount_of_money' => $orderAmountOfMoney,
            ]);
            throw new LocalizedException(__('Wrong amount'));
        }
    }
}
