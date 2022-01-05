<?php

namespace OrientSwiss\ZoodPay\Block\System\Config\Payment;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class fetchConfigButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'OrientSwiss_ZoodPay::system/config/payment/fetchConfigButton.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array   $data = []
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
     * Return ajax url for Health button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('zoodpay/system_config/fetchConfigButton');

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
                'id' => 'fetch_config_button',
                'label' => __('FETCH_CONFIGURATION'),
            ]
        );

        return $button->toHtml();
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
}


