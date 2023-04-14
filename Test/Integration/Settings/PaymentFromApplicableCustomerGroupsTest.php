<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Settings;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for configuration "Payment from Applicable Customer Groups"
 */
class PaymentFromApplicableCustomerGroupsTest extends TestCase
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
     * Test the selected specific customer groups setting
     *
     * Steps:
     * 1) Payment from Applicable Customer Groups=Specific Customer Groups
     * 2) In multiselect choose General
     * 3) Go to checkout as Logged Customer (General)
     * Expected result: Payment Method is available
     * 4) Change your Customer Group
     * Expected result: Payment Method is NOT available
     *
     * @dataProvider testPaymentFromApplicableCustomerGroupsDataProvider
     * @magentoDataFixture Magento/Customer/_files/customer_group.php
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/active 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/allow_specific_customer_group 1
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/customer_group 1
     * @magentoDbIsolation disabled
     */
    public function testPaymentFromApplicableCustomerGroups(int $customerGroup, int $expectedDelta): void
    {
        $quote = $this->getQuote(); // the quote has default customer group - General

        // count numbers of available payment methods
        $numberOfPaymentMethodsBeforeChangingCustomerGroup = count($this->methodList->getAvailableMethods($quote));

        // change customer group
        $quote->getCustomer()->setGroupId($customerGroup);

        // count numbers of available payment methods after the customer group has been changed
        $numberOfPaymentMethodsAfterChangingCustomerGroup = $this->methodList->getAvailableMethods($quote);

        $this->assertCount(
            $numberOfPaymentMethodsBeforeChangingCustomerGroup + $expectedDelta,
            $numberOfPaymentMethodsAfterChangingCustomerGroup
        );
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }

    public function testPaymentFromApplicableCustomerGroupsDataProvider(): array
    {
        return [
            [
                1,      // customer group id corresponding of the configuration
                0
            ],
            [
                55555,  // doesn't exist customer group id
                -1
            ]
        ];
    }
}
