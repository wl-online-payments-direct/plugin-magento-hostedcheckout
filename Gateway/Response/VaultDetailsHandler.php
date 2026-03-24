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
use Worldline\PaymentCore\Api\Config\WorldlineConfigInterface;

class VaultDetailsHandler implements HandlerInterface
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
     * @var WorldlineConfigInterface
     */
    private $worldlineConfig;

    /**
     * @var CardDateInterface
     */
    private $cardDate;

    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        SubjectReaderInterface $subjectReader,
        WorldlineConfigInterface $worldlineConfig,
        CardDateInterface $cardDate
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->subjectReader = $subjectReader;
        $this->worldlineConfig = $worldlineConfig;
        $this->cardDate = $cardDate;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();

        $paymentToken = $this->getVaultPaymentToken($transaction, $payment);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    private function getVaultPaymentToken(
        GetHostedCheckoutResponse $transaction,
        InfoInterface $payment
    ): ?PaymentTokenInterface {
        $cardPaymentMethodSpecificOutput = $transaction->getCreatedPaymentOutput()
            ->getPayment()
            ->getPaymentOutput()
            ->getCardPaymentMethodSpecificOutput();

        if (!$cardPaymentMethodSpecificOutput || !$cardPaymentMethodSpecificOutput->getCard()) {
            return null;
        }

        $payment->setAdditionalInformation(
            'card_number',
            mb_substr($cardPaymentMethodSpecificOutput->getCard()->getCardNumber(), -4)
        );
        $payment->setAdditionalInformation(
            'payment_product_id',
            $cardPaymentMethodSpecificOutput->getPaymentProductId()
        );

        if (!$payment->getAdditionalInformation('is_active_payment_token_enabler')) {
            return null;
        }

        $token = $cardPaymentMethodSpecificOutput->getToken();
        $card = $cardPaymentMethodSpecificOutput->getCard();
        if (empty($token) || empty($card->getExpiryDate())) {
            return null;
        }

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $expirationDate = $this->cardDate->getExpirationDateAt($cardPaymentMethodSpecificOutput);
        $paymentToken->setExpiresAt($expirationDate);
        $paymentToken->setTokenDetails($this->cardDate->convertDetailsToJSON([
            'type' => $this->worldlineConfig->mapCcType($cardPaymentMethodSpecificOutput->getPaymentProductId()),
            'maskedCC' => $card->getCardNumber(),
            'expirationDate' => $this->cardDate->getExpirationDate($cardPaymentMethodSpecificOutput)
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
