<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Settings;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item\Updater;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for configurations "Minimum Order Total" and "Maximum Order Total"
 */
class MinMaxOrderTotalTest extends TestCase
{
    /**
     * @var Updater
     */
    private $itemUpdater;

    /**
     * @var Quote
     */
    private $quoteResource;

    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->itemUpdater = $objectManager->get(Updater::class);
        $this->quoteResource = $objectManager->get(Quote::class);
        $this->methodList = $objectManager->get(MethodList::class);
        $this->productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
    }

    /**
     * Test the selected minimum and maximum settings
     *
     * Steps:
     * 1) Minimum Order Total = 5
     * 2) Maximum Order Total = 20
     * 3) Go to checkout with 12 order total
     * Expected result: Payment Method is available
     * 4) Change your order total on 24
     * Expected result: Payment Method is NOT available
     *
     * @dataProvider testMinMaxOrderTotalDataProvider
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/allowspecific 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/min_order_total 5
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/max_order_total 20
     * @magentoDbIsolation disabled
     */
    public function testMinMaxOrderTotal(int $itemQty, int $expectedDelta): void
    {
        $quote = $this->getQuote(); // the quote has default order total value = 10

        // count numbers of available payment methods
        $numberOfPaymentMethodsBeforeChangingTotal = count($this->methodList->getAvailableMethods($quote));

        // change order total
        $quoteItem = $quote->getItemByProduct($this->productRepository->get('simple'));
        $this->itemUpdater->update($quoteItem, ['qty' => $itemQty, 'custom_price' => 12]);
        $quote->collectTotals();
        $this->quoteResource->save($quote);

        // count numbers of available payment methods after the order total has been changed
        $numberOfPaymentMethodsAfterChangingTotal = $this->methodList->getAvailableMethods($quote);

        $this->assertCount(
            $numberOfPaymentMethodsBeforeChangingTotal - $expectedDelta,
            $numberOfPaymentMethodsAfterChangingTotal
        );
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }

    public function testMinMaxOrderTotalDataProvider(): array
    {
        return [
            [
                1,
                0
            ],
            [
                2,
                1
            ]
        ];
    }
}
