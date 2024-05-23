<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\WebApi\RedirectManagement;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use OnlinePayments\Sdk\Domain\CreatePaymentRequestFactory;
use Worldline\HostedCheckout\Api\TokenManagerInterface;
use Worldline\HostedCheckout\Gateway\Request\PaymentDataBuilder;
use Worldline\HostedCheckout\Service\HostedCheckout\CreateHostedCheckoutRequestBuilder;
use Worldline\PaymentCore\Api\Data\QuotePaymentInterface;
use Worldline\PaymentCore\Api\Service\Payment\CreatePaymentServiceInterface;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;

class CreateVaultPaymentDataAssigner implements DataAssignerInterface
{
    /**
     * @var CreatePaymentServiceInterface
     */
    private $createPaymentService;

    /**
     * @var CreateHostedCheckoutRequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var CreatePaymentRequestFactory
     */
    private $createPaymentRequestFactory;

    public function __construct(
        CreatePaymentServiceInterface $createPaymentService,
        CreateHostedCheckoutRequestBuilder $createRequestBuilder,
        TokenManagerInterface $tokenManager,
        CreatePaymentRequestFactory $createPaymentRequestFactory
    ) {
        $this->createPaymentService = $createPaymentService;
        $this->createRequestBuilder = $createRequestBuilder;
        $this->tokenManager = $tokenManager;
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
    }

    /**
     * Assign return and payment id and identify redirect url
     *
     * @param PaymentInterface $payment
     * @param QuotePaymentInterface $wlQuotePayment
     * @param array $additionalInformation
     * @return void
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function assign(
        PaymentInterface $payment,
        QuotePaymentInterface $wlQuotePayment,
        array $additionalInformation
    ): void {
        $quote = $payment->getQuote();
        $token = $this->tokenManager->getToken($quote);

        if (!$token || !$this->tokenManager->isSepaToken($token)) {
            return;
        }

        $requestForHostedCheckout = $this->createRequestBuilder->build($quote);
        $createPaymentRequest = $this->createPaymentRequestFactory->create();
        $createPaymentRequest->fromJson($requestForHostedCheckout->toJson());

        $storedPayIds = $payment->getAdditionalInformation('payment_ids') ?? [];

        $response = $this->createPaymentService->execute($createPaymentRequest, (int)$quote->getStoreId());
        $paymentId = $response->getPayment()->getId();
        $payment->setAdditionalInformation('payment_ids', array_merge($storedPayIds, [$paymentId]));
        $payment->setAdditionalInformation('return_id', $paymentId);
        $payment->setAdditionalInformation(PaymentDataBuilder::HOSTED_CHECKOUT_ID, $paymentId);
        $wlQuotePayment->setPaymentIdentifier($paymentId);
    }
}
