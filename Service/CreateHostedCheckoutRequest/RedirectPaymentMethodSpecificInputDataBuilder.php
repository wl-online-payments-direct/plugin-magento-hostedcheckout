<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Service\CreateHostedCheckoutRequest;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5408SpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5403SpecificInputFactory;
use Worldline\HostedCheckout\Gateway\Config\Config;
use Worldline\RedirectPayment\WebApi\RedirectManagement;
use Worldline\PaymentCore\Api\Data\PaymentProductsDetailsInterface;

class RedirectPaymentMethodSpecificInputDataBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectPaymentMethodSpecificInputFactory;

    /**
     * @var RedirectPaymentProduct5408SpecificInputFactory
     */
    private $paymentProduct5408SIFactory;

    /**
     * @var RedirectPaymentProduct5403SpecificInputFactory
     */
    private $paymentProduct5403SIFactory;

    public function __construct(
        Config                                         $config,
        RedirectPaymentMethodSpecificInputFactory      $redirectPaymentMethodSpecificInputFactory,
        RedirectPaymentProduct5408SpecificInputFactory $paymentProduct5408SIFactory,
        RedirectPaymentProduct5403SpecificInputFactory $paymentProduct5403SIFactory
    )
    {
        $this->config = $config;
        $this->redirectPaymentMethodSpecificInputFactory = $redirectPaymentMethodSpecificInputFactory;
        $this->paymentProduct5408SIFactory = $paymentProduct5408SIFactory;
        $this->paymentProduct5403SIFactory = $paymentProduct5403SIFactory;
    }

    public function build(CartInterface $quote): RedirectPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        /** @var RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput */
        $redirectPaymentMethodSpecificInput = $this->redirectPaymentMethodSpecificInputFactory->create();
        $paymentProductId = $quote->getPayment()->getAdditionalInformation(RedirectManagement::PAYMENT_PRODUCT_ID);
        $authMode = $this->config->getAuthorizationMode($storeId);
        if ($paymentProductId &&
            (
                $paymentProductId === PaymentProductsDetailsInterface::CHEQUE_VACANCES_CONNECT_PRODUCT_ID
                || $paymentProductId === PaymentProductsDetailsInterface::MEALVOUCHERS_PRODUCT_ID)) {
            $redirectPaymentMethodSpecificInput->setRequiresApproval(false);
        } else {
            $redirectPaymentMethodSpecificInput->setRequiresApproval($authMode !== Config::AUTHORIZATION_MODE_SALE);
        }
        $redirectPaymentMethodSpecificInput->setPaymentOption($this->config->getOneyPaymentOption($storeId));

        $paymentProduct5408SI = $this->paymentProduct5408SIFactory->create();
        $paymentProduct5408SI->setInstantPaymentOnly($this->config->getBankTransferMode($storeId));
        $redirectPaymentMethodSpecificInput->setPaymentProduct5408SpecificInput($paymentProduct5408SI);

        $paymentProduct5403SI = $this->paymentProduct5403SIFactory->create();
        $paymentProduct5403SI->setCompleteRemainingPaymentAmount(true);
        $redirectPaymentMethodSpecificInput->setPaymentProduct5403SpecificInput($paymentProduct5403SI);

        return $redirectPaymentMethodSpecificInput;
    }
}
