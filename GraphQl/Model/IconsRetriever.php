<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\GraphQl\Model;

use Worldline\HostedCheckout\Model\Ui\PaymentIconsProvider;
use Worldline\PaymentCore\GraphQl\Model\PaymentIcons\IconsRetrieverInterface;

class IconsRetriever implements IconsRetrieverInterface
{
    /**
     * @var PaymentIconsProvider
     */
    private $iconProvider;

    public function __construct(PaymentIconsProvider $iconProvider)
    {
        $this->iconProvider = $iconProvider;
    }

    public function getIcons(string $code, string $originalCode, int $storeId): array
    {
        $icons = $this->iconProvider->getFilteredIcons([], $storeId);

        return $this->getIconsDetails($icons);
    }

    /**
     * @param array $icons
     * @return array
     */
    private function getIconsDetails(array $icons): array
    {
        $iconsDetails = [];
        foreach ($icons as $icon) {
            $iconsDetails[] = [
                IconsRetrieverInterface::ICON_TITLE => $icon['title'],
                IconsRetrieverInterface::ICON_URL => $icon['url']
            ];
        }

        return $iconsDetails;
    }
}
