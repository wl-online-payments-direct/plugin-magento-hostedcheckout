<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Payment;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test case about log cart items
 */
class LogCartItemsTest extends TestCase
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
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/cart_lines 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/enable_group_cards 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize_capture
     */
    public function testLogCartItems(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);

        $request = $this->createRequestBuilder->build($quote);

        $this->assertNull($request->getOrder()->getShoppingCart());
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
        $quote->save();

        return $quote;
    }
}
