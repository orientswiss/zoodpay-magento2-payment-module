<?php
namespace OrientSwiss\ZoodPay\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

use Magento\Customer\Model\Customer;

class UpgradeData implements UpgradeDataInterface {


    public function __construct(

    ) {

    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        //if (version_compare($context->getVersion(), "1.0.1", "<"))
        {
            $setup->startSetup();
            //We will Magento Internal Status As for Now


            $setup->endSetup();
        }
    }

}
