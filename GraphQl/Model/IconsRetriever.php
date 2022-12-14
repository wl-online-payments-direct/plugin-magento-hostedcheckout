<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\GraphQl\Model;

use Worldline\PaymentCore\GraphQl\Model\PaymentIcons\IconsRetrieverInterface;
use Worldline\PaymentCore\Ui\PaymentIconsProvider;

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

    /**
     * @param string $code
     * @param string $originalCode
     * @param int $storeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIcons(string $code, string $originalCode, int $storeId): array
    {
        $icons = $this->iconProvider->getIcons($storeId);

        return $this->getIconsDetails($icons);
    }

    private function getIconsDetails(array $icons): array
    {
        $iconsDetails = [];
        foreach ($icons as $icon) {
            $iconsDetails[] = [
                IconsRetrieverInterface::ICON_TITLE => $icon['title'] ?? '',
                IconsRetrieverInterface::ICON_URL => $icon['url'] ?? '',
            ];
        }

        return $iconsDetails;
    }
}
