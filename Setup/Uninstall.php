<?php
declare(strict_types=1);

namespace Worldline\HostedCheckout\Setup;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Worldline\HostedCheckout\Model\Config\Source\MealvouchersProductTypes;

class Uninstall implements UninstallInterface
{
    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(
        ConfigResource $configResource,
        CollectionFactory $configCollectionFactory,
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->configResource = $configResource;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param ModuleContextInterface $context
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstall(SchemaSetupInterface $installer, ModuleContextInterface $context): void
    {
        $installer->startSetup();

        $collection = $this->configCollectionFactory->create()
            ->addFieldToFilter(
                'path',
                [
                    ['like' => 'payment/worldline_hosted_checkout/%'],
                    ['like' => 'payment/worldline_hosted_checkout_vault/%']
                ]
            );

        foreach ($collection->getItems() as $config) {
            $this->configResource->delete($config);
        }

        $this->removeMealvouchersProductType();

        $installer->endSetup();
    }

    private function removeMealvouchersProductType(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            MealvouchersProductTypes::MEALVOUCHERS_ATTRIBUTE_CODE
        );
    }
}
