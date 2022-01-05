<?php
namespace OrientSwiss\ZoodPay\Model\Config\Source;

/**
 * Used in creating options for getting product type value
 *
 */
class displayInfoCheckbox
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'checkbox', 'label'=>__('CHECKBOX_MSG')]];
    }
}
