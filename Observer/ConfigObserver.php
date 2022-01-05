<?php
/**
 * @category    OrientSwiss
 * @package     OrientSwiss_ZoodPay
 * @copyright Copyright © 2020 OrientSwiss ZoodPay. All rights reserved.
 * @author    mohammadali.namazi@zoodpay.com
 */


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Store\Model\ScopeInterface;
use \OrientSwiss\ZoodPay\Logger\Zlogger as Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;


class ConfigObserver implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @inheritDoc
     */
    /**
     * @var Logger
     */
    protected $_zLogger;


    /**
     * @var DataHelper
     */
    protected $_zDataHelper;


    /**
     * @var $_encrypted
     */
    protected $_encrypted;


    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;


    /**
     *  constructor.
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encrypted
     * @param zDataHelper $zDataHelper
     */


    public function __construct(
        Logger               $logger,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface   $encrypted,

        zDataHelper          $zDataHelper
    )
    {
        $this->_zLogger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_encrypted = $encrypted;

        $this->_zDataHelper = $zDataHelper;
    }

    public function execute(EventObserver $observer)
    {
        //  $this->_zLogger->info('Execute Observer');


        /**
         *
         *
         * Start To check the API HEALTH
         *
         *
         */


        $curlResponseHealthy = $this->_zDataHelper->curlGet(zDataHelper::API_HealthCheck, false);


        if ($curlResponseHealthy['statusCode'] == 200) {
            if (strpos($curlResponseHealthy['response'], 'OK') === true) {
                //  $this->_zLogger->info("Setting the ZoodPay API Healthy");
                $this->_zDataHelper->SetConfigData(zDataHelper::XML_API_HEALTH, __("HEALTHY_API"));

            }

        } else {
            //  $this->_zLogger->Notice("Setting the ZoodPay API Down");
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_API_HEALTH, __("API_DOWN"));
        }


        /**
         *
         *
         * Start To check the Get Configurations
         *
         *
         */


        $countryCode = $this->_zDataHelper->GetConfigData(zDataHelper::XML_Default_Country_Code);
        // Creating the array data for the API
        $fetchData = ["market_code" => "$countryCode"];


        // Post API with the data and retrieving the information
        $curlResponseFetchConfig = $this->_zDataHelper->curlPost($fetchData, zDataHelper::API_GetConfigurations);


        //Checking whether the response of the API Call was successful and store the information
        if ($curlResponseFetchConfig['statusCode'] == 200) {
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_Service_Configuration, $curlResponseFetchConfig['response']);
            //  $this->_zLogger->info('response : '. $curlResponseFetchConfig['response']);
        }


        if (($curlResponseHealthy['statusCode'] == 200) && ($curlResponseFetchConfig['statusCode'] == 200)) {
            //$this->_zDataHelper->SetConfigData(zDataHelper::XML_MERCHANT_ACTIVE, 1);
            //  $this->_zLogger->info('Merchant is Active to Operate');
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_MERCHANT_STATUS, __('ACTIVE_OPERATE'));

        } else {
            $this->_zDataHelper->SetConfigData(zDataHelper::XML_MERCHANT_ACTIVE, 0);


            if (($curlResponseHealthy['statusCode'] != 200) && ($curlResponseFetchConfig['statusCode'] != 200)) {
                $this->_zDataHelper->SetConfigData(zDataHelper::XML_MERCHANT_STATUS, __('CHECK_API_LINK_CREDENTIALS'));
            } else if ($curlResponseHealthy['statusCode'] != 200) {
                $this->_zDataHelper->SetConfigData(zDataHelper::XML_MERCHANT_STATUS, __('INVALID_API_LINK'));
            } else if ($curlResponseFetchConfig['statusCode'] != 200) {
                $this->_zDataHelper->SetConfigData(zDataHelper::XML_MERCHANT_STATUS, __('INVALID_API_CREDENTIALS'));
            }


            //  $this->_zLogger->Notice('Merchant is Deactivate');
        }

        $this->_zDataHelper->flushConfig();


    }


}
