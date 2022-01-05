<?php


namespace OrientSwiss\ZoodPay\Block;

use Magento\Framework\View\Element\Template;
use OrientSwiss\ZoodPay\Logger\Zlogger;

class ErrorPage extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;
    /**
     * @var Zlogger
     */
    private $_zLogger;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $_coreSession;

    public function __construct(
        Template\Context                                   $context,
        \Magento\Customer\Model\Session                    $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        Zlogger                                            $zLogger,
        array                                              $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_customerSession = $customerSession;
        $this->_zLogger = $zLogger;
        $this->_coreSession = $coreSession;
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getTheContent()
    {
        $this->_coreSession->start();
        $message = '';

        $message = $this->_coreSession->getPageMessage();
        if (is_null($message)) {
            $message = __("PROCESSING_ERROR");
        }


        // //  $this->_zLogger->info($message);

        return $message;
    }
}
