<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Settings;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Ui\ConfigProvider;

/**
 * Test cases for configuration "Payment from Applicable Currencies"
 */
class PaymentFromApplicableCurrenciesTest extends TestCase
{
    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->methodList = $objectManager->get(MethodList::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
    }

    /**
     * Test the selected specific currencies setting
     *
     * Steps:
     * 1) Payment from Applicable Currencies=Specific Currencies
     * 2) In multiselect choose EUR
     * 3) Go to checkout with EUR currency
     * Expected result: Payment Method is available
     * 4) Change your currency on USD
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/allow_specific_currency 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/currency EUR
     *
     * @magentoDbIsolation enabled
     */
    public function testPaymentFromApplicableCurrencies(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertTrue(in_array(ConfigProvider::HC_CODE, $paymentMethodCodes));
    }

    /**
     * Test the selected specific currencies setting
     *
     * Steps:
     * 1) Payment from Applicable Currencies=Specific Currencies
     * 2) In multiselect choose EUR
     * 3) Go to checkout with EUR currency
     * Expected result: Payment Method is available
     * 4) Change your currency on USD
     * Expected result: Payment Method is NOT available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default USD
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/allow_specific_currency 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/currency EUR
     *
     * @magentoDbIsolation enabled
     */
    public function testPaymentFromApplicableCurrencies2(): void
    {
        $quote = $this->getQuote();
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertFalse(in_array(ConfigProvider::HC_CODE, $paymentMethodCodes));
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
