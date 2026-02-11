<?php

namespace Worldline\HostedCheckout\Config\Source\ThreeDS;

use Magento\Framework\Data\OptionSourceInterface;

class ExemptionType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'none',
                'label' => __('Preference: No challenge request')
            ],
            [
                'value' => 'low-value',
                'label' => __('Exemption: Low-value')
            ],
            [
                'value' => 'transaction-risk-analysis',
                'label' => __('Exemption: Transaction Risk Analysis (acquirer)')
            ]
        ];
    }
}
