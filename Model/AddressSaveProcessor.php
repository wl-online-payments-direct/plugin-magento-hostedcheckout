<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\CartInterface;

class AddressSaveProcessor
{
    private const TYPE_SHIPPING = 0;
    private const TYPE_BILLING = 1;
    private const TYPES = [
        self::TYPE_SHIPPING => 'Shipping',
        self::TYPE_BILLING => 'Billing'
    ];

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CartInterface
     */
    private $quote;

    /**
     * @var bool[]
     */
    private $hasDefaultAddress;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * This method is a decomposed copy of \Magento\Quote\Model\QuoteManagement::_prepareCustomerQuote
     * The purpose of the method is to save filled out shipping and/or billing addresses,
     * when Hosted Checkout process was cancelled
     *
     * @see \Magento\Quote\Model\QuoteManagement::_prepareCustomerQuote
     */
    public function saveAddress(CartInterface $quote): void
    {
        $this->quote = $quote;
        $customerId = $this->quote->getCustomerId();
        
        if ($customerId === null) {
            return;
        }
        
        $customer = $this->customerRepository->getById($customerId);
        $this->hasDefaultAddress = [
            self::TYPE_SHIPPING => (bool)$customer->getDefaultShipping(),
            self::TYPE_BILLING => (bool)$customer->getDefaultBilling()
        ];

        $billing = $this->quote->getBillingAddress();
        $shipping = $this->quote->isVirtual() ? null : $this->quote->getShippingAddress();

        if ($shipping && !$shipping->getSameAsBilling()) {
            $this->processAddressFor(self::TYPE_SHIPPING, $shipping);
        }

        $this->processAddressFor(self::TYPE_BILLING, $billing);

        if ($shipping && !$shipping->getCustomerId() && !$this->hasDefaultAddress[self::TYPE_BILLING]) {
            $shipping->setIsDefaultBilling(true);
        }
    }

    private function processAddressFor(int $type, QuoteAddressInterface $quoteAddress): void
    {
        if (!$quoteAddress->getCustomerId() || $quoteAddress->getSaveInAddressBook()) {
            $customerAddress = $this->getCustomerAddress($type, $quoteAddress);

            if ($customerAddress !== null) {
                if (!$this->hasDefaultAddress[$type]) {
                    $this->processAddressByType($type, $customerAddress);
                }

                $customerAddress->setCustomerId($this->quote->getCustomerId());
                $this->addressRepository->save($customerAddress);
                $this->quote->addCustomerAddress($customerAddress);
                $quoteAddress->setCustomerAddressData($customerAddress);
                $quoteAddress->setCustomerAddressId($customerAddress->getId());
            }
        }
    }

    private function processAddressByType(int $type, CustomerAddressInterface $customerAddress)
    {
        if ($type === self::TYPE_BILLING) {
            if (!$this->hasDefaultAddress[self::TYPE_SHIPPING]) {
                $customerAddress->setIsDefaultShipping(true);
            }

            $customerAddress->setIsDefaultBilling(true);
        } else { //$type === self::TYPE_SHIPPING
            $customerAddress->setIsDefaultShipping(true);
            $this->hasDefaultAddress[self::TYPE_SHIPPING] = true;

            if (!$this->hasDefaultAddress[self::TYPE_BILLING]
                && !$this->quote->getBillingAddress()->getSaveInAddressBook()
            ) {
                $customerAddress->setIsDefaultBilling(true);
                $this->hasDefaultAddress[self::TYPE_BILLING] = true;
            }
        }
    }

    private function getCustomerAddress(int $type, QuoteAddressInterface $quoteAddress): ?CustomerAddressInterface
    {
        if ($quoteAddress->getQuoteId()) {
            $customerAddress = $quoteAddress->exportCustomerAddress();
        } else {
            $getDefaultAddress = 'getDefault' . self::TYPES[$type];
            $defaultAddress = $this->customerRepository->getById($this->quote->getCustomerId())->$getDefaultAddress();

            if ($defaultAddress) {
                try {
                    $customerAddress = $this->addressRepository->getById($defaultAddress);
                    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
                } catch (LocalizedException $e) {
                    // no address
                }
            }
        }

        return $customerAddress ?? null;
    }
}
