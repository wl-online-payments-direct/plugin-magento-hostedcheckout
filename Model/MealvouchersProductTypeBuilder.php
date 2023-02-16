<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model;

use Magento\Quote\Api\Data\CartInterface;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;

class MealvouchersProductTypeBuilder
{
    public function shapeMealvouchersProductType(CartInterface $quote): void
    {
        if ($this->isMixedProductTypesInQuote($quote)) {
            $this->formatDataForMixedQuote($quote);
            return;
        }

        $this->formatDataForUniformQuote($quote);
    }

    private function isMixedProductTypesInQuote(CartInterface $quote): bool
    {
        $firstProductType = null;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $productType = $item->getProduct()->getData(MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE);
            if (!$productType) {
                continue;
            }

            if (!$firstProductType && $productType !== MealvouchersProductTypes::NO) {
                $firstProductType = $productType;
                continue;
            }

            if ($firstProductType && $firstProductType !== $productType) {
                return true;
            }
        }

        return false;
    }

    private function formatDataForMixedQuote(CartInterface $quote): void
    {
        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $item->setData(
                MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE,
                MealvouchersProductTypes::FOOD_AND_DRINK
            );
        }

        $quote->setData(
            MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE,
            MealvouchersProductTypes::FOOD_AND_DRINK
        );
    }

    private function formatDataForUniformQuote(CartInterface $quote): void
    {
        $commonValue = MealvouchersProductTypes::NO;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $commonValue = $value = $item->getProduct()->getData(MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE)
                ?? MealvouchersProductTypes::NO;
            $item->setData(MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE, $value);
        }

        $quote->setData(MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE, $commonValue);
    }
}
