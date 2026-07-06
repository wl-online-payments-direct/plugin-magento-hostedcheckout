<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Config\Source\DirectDebit;

use Magento\Framework\Data\OptionSourceInterface;

class SignatureType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'SMS',
                'label' => __('SMS'),
            ],
            [
                'value' => 'UNSIGNED',
                'label' => __('UNSIGNED'),
            ],
            [
                'value' => 'TICK_BOX',
                'label' => __('TICK_BOX'),
            ],
            [
                'value' => 'AIS',
                'label' => __('AIS'),
            ]
        ];
    }
}
