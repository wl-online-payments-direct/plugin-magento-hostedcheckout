<?php

declare(strict_types=1);

namespace Worldline\HostedCheckout\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Worldline\HostedCheckout\Model\ReturnRequestProcessor;
use Worldline\PaymentCore\Model\Order\PendingOrderException;

class ReturnUrl extends Action
{
    private const SUCCESS_URL = 'checkout/onepage/success';
    private const FAIL_URL = 'wl_hostedcheckout/returns/failed';

    /**
     * @var ReturnRequestProcessor
     */
    private $returnRequestProcessor;

    public function __construct(
        Context $context,
        ReturnRequestProcessor $returnRequestProcessor
    ) {
        parent::__construct($context);
        $this->returnRequestProcessor = $returnRequestProcessor;
    }

    public function execute(): ResultInterface
    {
        try {
            $hostedCheckoutId = (string) $this->getRequest()->getParam('hostedCheckoutId');
            $returnId = (string) $this->getRequest()->getParam('RETURNMAC');

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            sleep(2); // wait for the webhook

            $this->returnRequestProcessor->processRequest($hostedCheckoutId, $returnId);

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::SUCCESS_URL);
        } catch (PendingOrderException $exception) {
            $this->messageManager->addSuccessMessage($exception->getMessage());
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::SUCCESS_URL);
        } catch (LocalizedException $exception) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::FAIL_URL);
        }
    }
}
