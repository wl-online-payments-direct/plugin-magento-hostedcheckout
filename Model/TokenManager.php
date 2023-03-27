<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Worldline\HostedCheckout\Api\TokenManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;

class TokenManager implements TokenManagerInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var Json
     */
    private $json;

    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        Json $json
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->json = $json;
    }

    public function getToken(CartInterface $quote): ?PaymentTokenInterface
    {
        $payment = $quote->getPayment();
        if (!$publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH)) {
            return null;
        }

        return $this->paymentTokenManagement->getByPublicHash($publicHash, (int) $quote->getCustomerId());
    }

    public function isSepaToken(PaymentTokenInterface $token): bool
    {
        $details = $this->json->unserialize($token->getTokenDetails());
        $paymentProductId = $details['payment_product_id'] ?? null;
        if (!$details) {
            return false;
        }

        return $paymentProductId === PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID;
    }
}
