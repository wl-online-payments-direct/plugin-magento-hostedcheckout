<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Test\Integration\Payment;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\HttpFactory as HttpRequestFactory;
use Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory as QuotePaymentCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\HostedCheckout\Controller\Returns\ReturnUrlFactory;
use Worldline\HostedCheckout\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test case about save new address after cancel transaction
 */
class SaveNewAddressAfterCancelTransactionTest extends TestCase
{
    /**
     * @var ReturnUrlFactory
     */
    private $returnUrlControllerFactory;

    /**
     * @var HttpRequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var QuotePaymentCollectionFactory
     */
    private $quotePaymentCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->returnUrlControllerFactory = $objectManager->get(ReturnUrlFactory::class);
        $this->httpRequestFactory = $objectManager->get(HttpRequestFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->addressRepository = $objectManager->get(AddressRepositoryInterface::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->quotePaymentCollectionFactory = $objectManager->get(QuotePaymentCollectionFactory::class);
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
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_hosted_checkout/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testSaveNewAddress(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);
        $this->updateQuote();

        $params = [
            'hostedCheckoutId' => '3254564315',
            'RETURNMAC' => '89b2ce0c-8b30-4463-8d43-3084932d9be2'
        ];

        $request = $this->httpRequestFactory->create();
        $returnUrlController = $this->returnUrlControllerFactory->create(['request' => $request]);

        $returnUrlController->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $result = $returnUrlController->execute();

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $urlProperty = $reflectedResult->getProperty('url');
        $urlProperty->setAccessible(true);
        $this->assertNotFalse(strpos($urlProperty->getValue($result), 'worldline/returns/reject'));

        // validate saved address
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $addresses = $this->addressRepository->getList($searchCriteria);
        $this->assertCount(2, $addresses->getItems());

        // validate clean quote
        $collection = $this->quotePaymentCollectionFactory->create();
        $collection->addFieldToFilter('additional_information', ['like' => '%' . '3254564315' . '%']);
        $collection->setOrder('payment_id');
        $collection->getSelect()->limit(1);
        $quotePayment = $collection->getFirstItem();

        $this->assertNull($quotePayment->getQuoteId());
    }

    private function updateQuote(): void
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setCustomerId(1);
        $quote->getPayment()->setMethod(ConfigProvider::HC_CODE);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564315_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation('customer_id', 1);
        $quote->getPayment()->setAdditionalInformation('return_id', '89b2ce0c-8b30-4463-8d43-3084932d9be2');
        $quote->collectTotals();
        $quote->save();
    }
}
