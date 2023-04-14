<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Payment;

use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Infrastructure\ActiveVault\FakePaymentToken;

/**
 * Test case about place order with sepa payment
 */
class PlaceOrderWithSepaTest extends TestCase
{
    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var CreateHostedCheckoutRequestBuilder
     */
    private $createRequestBuilder;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->createRequestBuilder = $objectManager->get(CreateHostedCheckoutRequestBuilder::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout_vault/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testPlaceOrder(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);

        Bootstrap::getObjectManager()->get(FakePaymentToken::class)->createVaultSepaToken(ConfigProvider::HC_CODE);

        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);

        $request = $this->createRequestBuilder->build($quote);
        $sepaDirectDebitPaymentMethodSI = $request->getSepaDirectDebitPaymentMethodSpecificInput();

        $this->assertEquals(
            PaymentProductsDetailsInterface::SEPA_DIRECT_DEBIT_PRODUCT_ID,
            $sepaDirectDebitPaymentMethodSI->getPaymentProductId()
        );

        $this->assertEquals(
            'exampleMandateReference',
            $sepaDirectDebitPaymentMethodSI->getPaymentProduct771SpecificInput()->getExistingUniqueMandateReference()
        );
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setCustomerId(1);
        $quote->getPayment()->setMethod(ConfigProvider::HC_VAULT_CODE);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation('public_hash', 'fakePublicHash');
        $quote->getPayment()->setAdditionalInformation('customer_id', 1);
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
