<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;
use Worldline\HostedCheckout\Ui\ConfigProvider;

/**
 * Test case for configurations "Group Cards"
 */
class GroupCardsTest extends TestCase
{
    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var CreateHostedCheckoutRequestBuilder
     */
    private $createRequestBuilder;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
        $this->createRequestBuilder = $objectManager->get(CreateHostedCheckoutRequestBuilder::class);
    }

    /**
     * Test the selected "Group Cards" setting
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/cart_lines 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/enable_group_cards 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize_capture
     * @magentoDbIsolation disabled
     */
    public function testGroupCards(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);

        $request = $this->createRequestBuilder->build($quote);

        $this->assertTrue(
            $request->getHostedCheckoutSpecificInput()->getCardPaymentMethodSpecificInput()->getGroupCards()
        );
    }

    /**
     * Test the unselected "Group Cards" setting
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/cart_lines 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/enable_group_cards 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize_capture
     * @magentoDbIsolation disabled
     */
    public function testUnsetGroupCards(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);

        $request = $this->createRequestBuilder->build($quote);

        $this->assertFalse(
            $request->getHostedCheckoutSpecificInput()->getCardPaymentMethodSpecificInput()->getGroupCards()
        );
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
