<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Model\Ui;

use Worldline\PaymentCore\Model\Ui\PaymentIconsProvider as GeneralIconsProvider;

class PaymentIconsProvider
{
    /**
     * @var GeneralIconsProvider
     */
    private $generalIconsProvider;

    public function __construct(GeneralIconsProvider $generalIconsProvider)
    {
        $this->generalIconsProvider = $generalIconsProvider;
    }

    public function getFilteredIcons(?array $typesFilter = [], ?int $storeId = null): array
    {
        if (empty($typesFilter)) {
            return $this->generalIconsProvider->getIcons($storeId);
        }

        $icons = [];
        foreach ($this->generalIconsProvider->getIcons($storeId) as $icon) {
            if (in_array($icon['method'], $typesFilter)) {
                $icons[] = $icon;
            }
        }

        return $icons;
    }
}
