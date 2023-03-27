<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Controller\Returns;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Worldline\HostedCheckout\Model\ReturnRequestProcessor;
use Worldline\PaymentCore\Model\Order\RejectOrderException;
use Worldline\PaymentCore\Model\OrderState;
use Magento\Quote\Api\CartRepositoryInterface;

class ReturnUrlForVault extends Action implements HttpGetActionInterface
{
    private const SUCCESS_URL = 'checkout/onepage/success';
    private const WAITING_URL = 'worldline/returns/waiting';
    private const FAIL_URL = 'worldline/returns/failed';
    private const REJECT_URL = 'worldline/returns/reject';

    /**
     * @var ReturnRequestProcessor
     */
    private $returnRequestProcessor;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    public function __construct(
        Context $context,
        ReturnRequestProcessor $returnRequestProcessor,
        Session $checkoutSession,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context);
        $this->returnRequestProcessor = $returnRequestProcessor;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    public function execute(): ResultInterface
    {
        try {
            $quoteId = $this->checkoutSession->getWlQuoteRecoveryId();
            $quote = $this->cartRepository->get($quoteId);
            $payment = $quote->getPayment();

            $hostedCheckoutId = (string) (int) $payment->getAdditionalInformation('payment_id');
            $returnId = (string) $payment->getAdditionalInformation('return_id');

            /** @var OrderState $orderState */
            $orderState = $this->returnRequestProcessor->processRequest($hostedCheckoutId, $returnId);
            if ($orderState->getState() === ReturnRequestProcessor::WAITING_STATE) {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                    ->setPath(self::WAITING_URL, ['incrementId' => $orderState->getIncrementId()]);
            }

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::SUCCESS_URL);
        } catch (RejectOrderException $exception) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::REJECT_URL);
        } catch (LocalizedException $exception) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::FAIL_URL);
        }
    }
}
