<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class MealvouchersProductTypes extends AbstractSource
{
    public const NO = 'No';
    public const FOOD_AND_DRINK = 'FoodAndDrink';
    public const HOME_AND_GARDEN = 'HomeAndGarden';
    public const GIFT_AND_FLOWERS = 'GiftAndFlowers';

    public const MERGED_PRODUCT_CODE = 'Merged item';

    public const MEALVOUCHERS_ATTRIBUTE_CODE = 'worldline_mealvouchers_product_type';

    /**
     * @return string[]
     */
    public function optionsMap()
    {
        return [
            self::FOOD_AND_DRINK => 'Food and drink',
            self::HOME_AND_GARDEN => 'Home and garden',
            self::GIFT_AND_FLOWERS => 'Gifts and flowers',
        ];
    }

    /**
     * @return array[]
     */
    public function getAllOptions(): array
    {
        return [
            ['value' => self::NO, 'label' => 'No'],
            ['value' => self::FOOD_AND_DRINK, 'label' => 'Food and drink'],
            ['value' => self::HOME_AND_GARDEN, 'label' => 'Home and garden'],
            ['value' => self::GIFT_AND_FLOWERS, 'label' => 'Gift and flowers']
        ];
    }
}
