<?php

namespace Worldline\HostedCheckout\Config\Source\ThreeDS;

use Magento\Framework\Data\OptionSourceInterface;

class ExemptionType implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'low-value',
                'label' => __('Low-value (default)')
            ],
            [
                'value' => 'transaction-risk-analysis',
                'label' => __('Transaction Risk Analysis')
            ]
        ];
    }
}
