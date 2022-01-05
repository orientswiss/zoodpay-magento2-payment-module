<?php


namespace OrientSwiss\ZoodPay\Controller\Adminhtml\System\Config;


use Laminas\Json\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use \Magento\Catalog\Model\Product\Visibility;


use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use phpDocumentor\Reflection\Types\Object_;


class fetchConfigButton extends \Magento\Backend\App\Action
{

    /**
     * @var LoggerInterface
     */
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
     * fetchConfigButton constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param LoggerInterface $zLogger
     * @param zDataHelper $zDataHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Psr\Log\LoggerInterface            $logger,
        LoggerInterface                     $zLogger,
        zDataHelper                         $zDataHelper

    )
    {

        $this->_zLogger = $zLogger;
        $this->_logger = $logger;
        $this->_zDataHelper = $zDataHelper;

        parent::__construct($context);
    }

    public function execute()
    {


        // //  $this->_zLogger->info("Fetch Config Started");

        //Getting the Default Country Code for API
        $countryCode = $this->_zDataHelper->GetConfigData(zDataHelper::XML_Default_Country_Code);
        // Creating the array data for the API
        $fetchData = ["market_code" => "$countryCode"];


        // Post API with the data and retrieving the information
        $curlResponse = $this->_zDataHelper->curlPost($fetchData, zDataHelper::API_GetConfigurations);


        //Checking whether the response of the API Call was successful and store the information
        if ($curlResponse['statusCode'] == 200) {
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_Service_Configuration, $curlResponse['response']);
            // //  $this->_zLogger->info('response : '. $curlResponse['response']);
        }


    }
}

