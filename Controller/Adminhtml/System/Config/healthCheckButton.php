<?php


namespace OrientSwiss\ZoodPay\Controller\Adminhtml\System\Config;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use \Magento\Catalog\Model\Product\Visibility;

use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use Magento\Framework\Controller\ResultFactory;


class healthCheckButton extends \Magento\Backend\App\Action
{


    var $api_status = 0;
    protected $_zLogger;
    /**
     * @var zDataHelper
     */
    protected $_zDataHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @inheritDoc
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                  $context,
        \Psr\Log\LoggerInterface                             $logger,
        LoggerInterface                                      $zLogger,
        zDataHelper                                          $zDataHelper,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
    )
    {

        $this->_zLogger = $zLogger;
        $this->_logger = $logger;
        $this->_zDataHelper = $zDataHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }

    public function execute()
    {

        $curlResponse = $this->_zDataHelper->curlGet(zDataHelper::API_HealthCheck, false);

        if ($curlResponse['statusCode'] == 200) {

            // if (strpos($curlResponse['response'],'OK') === true)
            if (strpos($curlResponse['response'],'OK') === true)  {

                // //  $this->_zLogger->info("Setting the ZoodPay API Healthy");
                $this->_zDataHelper->SetConfigData(zDataHelper::XML_API_HEALTH, __("HEALTHY_API"));
                $this->_zDataHelper->SetConfigData(zDataHelper::XML_API_HEALTH_hidden, '1');
                $this->api_status = 1;
            }

        } else {
            // //  $this->_zLogger->Notice("Setting the ZoodPay API Down");
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_API_HEALTH, __("API_DOWN"));
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_API_HEALTH_hidden, '1');
        }

        $this->_zDataHelper->SetConfigData(zDataHelper::XML_GATEWAY_TITLE, __("BUY_NOW_PAY_LATER"));
        $this->_zDataHelper->flushConfig();


    }
}
