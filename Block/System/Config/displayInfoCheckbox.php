<?php
/**
 *
 *
 * @category
 * @package
 * @author
 * @copyright
 * @license
 */

namespace OrientSwiss\ZoodPay\Block\System\Config;

class displayInfoCheckbox extends \Magento\Config\Block\System\Config\Form\Field
{
    const CONFIG_PATH = 'payment/zoodpayment/zoodpay_display_info_checkbox';

    protected $_template = 'OrientSwiss_ZoodPay::system/config/displayInfoCheckbox.phtml';

    protected $_values = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array                                   $data = []
    )
    {
        parent::__construct($context, $data);
    }

    public function getValues()
    {
        $values = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        foreach ($objectManager->create('OrientSwiss\ZoodPay\Model\Config\Source\displayInfoCheckbox')->toOptionArray() as $value) {
            $values[$value['value']] = __($value['label']);
        }

        return $values;
    }

    /**
     *
     * @param  $name
     * @return boolean
     */
    public function getIsChecked($name)
    {
        return in_array($name, $this->getCheckedValues());
    }

    /**
     *
     *get the checked value from config
     */
    public function getCheckedValues()
    {
        if (is_null($this->_values)) {
            $data = $this->getConfigData();
            if (isset($data[self::CONFIG_PATH])) {
                $data = $data[self::CONFIG_PATH];
            } else {
                $data = '';
            }
            $this->_values = explode(',', $data);
        }

        return $this->_values;
    }

    /**
     * Retrieve element HTML markup.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }
}
