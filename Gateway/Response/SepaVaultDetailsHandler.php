<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Worldline\PaymentCore\Api\CardDateInterface;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

class SepaVaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var CardDateInterface
     */
    private $cardDate;

    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        SubjectReaderInterface $subjectReader,
        CardDateInterface $cardDate
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->subjectReader = $subjectReader;
        $this->cardDate = $cardDate;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();
        if ($paymentToken = $this->getVaultPaymentToken($transaction, $payment)) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    private function getVaultPaymentToken(
        GetHostedCheckoutResponse $transaction,
        InfoInterface $payment
    ): ?PaymentTokenInterface {
        $output = $transaction->getCreatedPaymentOutput()
            ->getPayment()
            ->getPaymentOutput()
            ->getSepaDirectDebitPaymentMethodSpecificOutput();

        if (!$output) {
            return null;
        }

        if (!$token = $output->getPaymentProduct771SpecificOutput()->getMandateReference()) {
            return null;
        }

        if (!$payment->getAdditionalInformation('is_active_payment_token_enabler')) {
            return null;
        }

        $expiresAt = strtotime('now +36 months');
        $expirationDate = date('Y-m-d H:i:s', $expiresAt);

        $payment->setAdditionalInformation('card_number', 'Sepa Direct Debit');
        $payment->setAdditionalInformation('is_active_payment_token_enabler', true);
        $payment->setAdditionalInformation('payment_product_id', $output->getPaymentProductId());
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);

        $paymentToken->setExpiresAt($expiresAt);
        $paymentToken->setTokenDetails($this->cardDate->convertDetailsToJSON([
            'type' => 'Sepa Direct Debit',
            'maskedCC' => 'Sepa Direct Debit',
            'expirationDate' => $expirationDate,
            'payment_product_id' => $output->getPaymentProductId(),
        ]));

        return $paymentToken;
    }

    private function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }
}
