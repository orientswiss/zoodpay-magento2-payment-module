<?php

namespace OrientSwiss\ZoodPay\Block\System\Config\Payment;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class healthCheckButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'OrientSwiss_ZoodPay::system/config/payment/healthCheckButton.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for Health button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('zoodpay/system_config/healthCheckButton');
    }





    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'zoodpay_api_health_button',
                'label' => __('HEALTH_CHECK'),
            ]
        );

        return $button->toHtml();
    }
}


