<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\Authorization;
use Worldline\PaymentCore\Model\Webhook\ResourceModel\Webhook as WebhookResource;
use Worldline\PaymentCore\Model\Webhook\WebhookFactory;

/**
 * Test cases for configuration "Log Webhooks"
 */
class LogWebhooksTest extends TestCase
{
    /**
     * @var WebhookFactory
     */
    private $webhookEntityFactory;

    /**
     * @var WebhookResource
     */
    private $webhookResource;

    /**
     * @var  WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->webhookEntityFactory = $objectManager->get(WebhookFactory::class);
        $this->webhookResource = $objectManager->get(WebhookResource::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize_capture
     * @magentoConfigFixture default/worldline_debug/webhooks/active 1
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testWebhookData(): void
    {
        $quote = $this->getQuote();

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(Authorization::getData($quote->getReservedOrderId()));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        $webhookEntity = $this->webhookEntityFactory->create();
        $this->webhookResource->load($webhookEntity, $quote->getReservedOrderId(), 'increment_id');
        $this->assertEquals('payment.pending_capture', $webhookEntity->getType());
        $this->assertEquals(5, $webhookEntity->getStatusCode());
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
