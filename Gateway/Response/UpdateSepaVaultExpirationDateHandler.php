<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Api\CardDateInterface;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

/**
 * So as soon as a mandate is used (be it for recurring or one-off), its validity extends by 36 months.
 */
class UpdateSepaVaultExpirationDateHandler implements HandlerInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var CardDateInterface
     */
    private $cardDate;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        CardDateInterface $cardDate,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->subjectReader = $subjectReader;
        $this->cardDate = $cardDate;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();
        $this->updateExpirationDateForToken($transaction, $payment);
    }

    private function updateExpirationDateForToken(
        GetHostedCheckoutResponse $transaction,
        InfoInterface $payment
    ): void {
        $output = $transaction->getCreatedPaymentOutput()
            ->getPayment()
            ->getPaymentOutput()
            ->getSepaDirectDebitPaymentMethodSpecificOutput();

        if (!$output) {
            return;
        }

        if (!$token = $output->getPaymentProduct771SpecificOutput()->getMandateReference()) {
            return;
        }

        $expiresAt = strtotime('now +36 months');
        $expirationDate = date('Y-m-d H:i:s', $expiresAt);

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $token,
            str_replace('_vault', '', $payment->getMethod()),
            (int) $payment->getAdditionalInformation('customer_id')
        );

        $paymentToken->setExpiresAt($expiresAt);
        $paymentToken->setTokenDetails($this->cardDate->convertDetailsToJSON([
            'type' => 'Sepa Direct Debit',
            'maskedCC' => 'Sepa Direct Debit',
            'expirationDate' => $expirationDate,
            'payment_product_id' => $output->getPaymentProductId(),
        ]));

        $this->paymentTokenRepository->save($paymentToken);
    }
}
