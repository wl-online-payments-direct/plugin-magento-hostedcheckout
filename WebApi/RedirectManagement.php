<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\WebApi;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Worldline\HostedCheckout\Api\RedirectManagementInterface;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutService;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;
use Worldline\PaymentCore\Api\QuoteRestorationInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedirectManagement implements RedirectManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CreateHostedCheckoutService
     */
    private $createRequest;

    /**
     * @var CreateHostedCheckoutRequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DataAssignerInterface[]
     */
    private $dataAssignerPool;

    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var QuoteRestorationInterface
     */
    private $quoteRestoration;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CreateHostedCheckoutService $createRequest,
        CreateHostedCheckoutRequestBuilder $createRequestBuilder,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RequestInterface $request,
        PaymentInformationManagementInterface $paymentInformationManagement,
        QuoteRestorationInterface $quoteRestoration,
        array $dataAssignerPool = []
    ) {
        $this->cartRepository = $cartRepository;
        $this->createRequest = $createRequest;
        $this->createRequestBuilder = $createRequestBuilder;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->request = $request;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->quoteRestoration = $quoteRestoration;
        $this->dataAssignerPool = $dataAssignerPool;
    }

    /**
     * Get redirect url
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function processRedirectRequest(
        int $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string {
        $quote = $this->cartRepository->get($cartId);

        return $this->process($quote, $paymentMethod, $billingAddress);
    }

    /**
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param string $email
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function processGuestRedirectRequest(
        string $cartId,
        PaymentInterface $paymentMethod,
        string $email,
        AddressInterface $billingAddress = null
    ): string {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->get($quoteIdMask->getQuoteId());
        $quote->setCustomerEmail($email);

        // compatibility with magento 2.3.7
        $quote->setCustomerIsGuest(true);

        return $this->process($quote, $paymentMethod, $billingAddress);
    }

    private function process(
        CartInterface $quote,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string {
        $this->paymentInformationManagement->savePaymentInformation($quote->getId(), $paymentMethod, $billingAddress);
        $payment = $quote->getPayment();

        $additionalData = $paymentMethod->getAdditionalData();
        $additionalData = array_merge((array)$paymentMethod->getAdditionalInformation(), (array)$additionalData);
        $additionalData['agent'] = $this->request->getHeader('accept');
        $additionalData['user-agent'] = $this->request->getHeader('user-agent');

        foreach ($this->dataAssignerPool as $dataAssigner) {
            $dataAssigner->assign($quote->getPayment(), $additionalData);
        }

        $quote->reserveOrderId();

        $this->setToken($quote, $paymentMethod);

        $request = $this->createRequestBuilder->build($quote);
        $response = $this->createRequest->execute($request, (int)$quote->getStoreId());
        $payment->setAdditionalInformation('return_id', $response->getRETURNMAC());
        $payment->setAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID, $response->getHostedCheckoutId());
        $quote->setIsActive(false);
        $this->quoteRestoration->preserveQuoteId((int)$quote->getId());
        $this->cartRepository->save($quote);

        return $response->getRedirectUrl();
    }

    private function setToken(CartInterface $quote, PaymentInterface $paymentMethod): void
    {
        $payment = $quote->getPayment();
        $publicToken = $paymentMethod->getAdditionalData()['public_hash'] ?? false;
        if ($publicToken) {
            $payment->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $publicToken);
            $payment->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $quote->getCustomerId());
        }
    }
}
