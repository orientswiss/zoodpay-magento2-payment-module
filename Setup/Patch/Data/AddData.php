<?php

/**
 * Description:
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2022-06-23, Thu, 10:21
 * File: InstallData
 * Path: app/code/OrientSwiss/ZoodPay/Setup/Patch/Data/AddData.php
 * Line: 12
 */

namespace OrientSwiss\ZoodPay\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class AddData
 *
 * @package OrientSwiss\ZoodPay\Setup\Patch\Data
 */

class AddData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $setup = $this->moduleDataSetup;

        $this->moduleDataSetup->endSetup();
    }
    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */

    public static function getVersion()
    {
        return '1.0.4';
    }

    /**
     *
     * {@inheritdoc}
     */

    public function getAliases()
    {
        return [];
    }
}
