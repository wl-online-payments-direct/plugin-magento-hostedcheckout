<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Settings;

use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Ui\ConfigProvider;

/**
 * Test cases for configuration "title"
 */
class TitleTest extends TestCase
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
     * Steps:
     * 1) Payment enabled=yes
     * 2) Set title to Hosted Checkout
     * 3) Go to checkout
     * Expected result: Payment Method is available with title "Pay with Hosted Checkout"
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/title Pay with Hosted Checkout
     * @magentoDbIsolation enabled
     */
    public function testFirstInOrder(): void
    {
        $quote = $this->getQuote();
        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $hcPaymentMethod = $this->getHCPaymentMethod($paymentMethods);

        $this->assertInstanceOf(MethodInterface::class, $hcPaymentMethod);

        $this->assertEquals(
            'Pay with Hosted Checkout',
            $hcPaymentMethod->getConfigData('title')
        );
    }

    private function getHCPaymentMethod(array $paymentMethods): ?MethodInterface
    {
        $result = null;

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getCode() === ConfigProvider::HC_CODE) {
                $result = $paymentMethod;
                break;
            }
        }

        return $result;
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
