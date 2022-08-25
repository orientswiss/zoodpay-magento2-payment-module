<?php

/**
 * Description:
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2022-06-14, Tue, 10:38
 * File: ApiUrl
 * Path: Model/Config/Source/ApiUrl.php
 * Line: 10
 */

namespace OrientSwiss\ZoodPay\Model\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class ApiUrl implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'https://sandbox-api.zoodpay.com/', 'label' => __('Sandbox')], ['value' => 'https://api.zoodpay.com/', 'label' => __('Live')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('No'), 1 => __('Yes')];
    }
}
