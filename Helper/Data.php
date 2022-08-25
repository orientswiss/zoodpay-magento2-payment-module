<?php


/**
 * Description:
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2022-06-15, Wed, 12:34
 * File: Data
 * Path: Helper/Data.php
 * Line: 11
 */

namespace OrientSwiss\ZoodPay\Helper;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\ScopeInterface;
use OrientSwiss\ZoodPay\Controller\Adminhtml\System\Config\fetchConfigButton;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class Data extends AbstractHelper
{

    /**
     * XML Path for
     *  XML_MERCHANT_ID
     *  XML_MERCHANT_KEY
     * XML_MERCHANT_ACTIVE
     * XML_MERCHANT_STATUS
     *
     */

    const XML_MERCHANT_ID = 'payment/zoodpayment/merchant_id';
    const XML_MERCHANT_KEY = 'payment/zoodpayment/merchant_key';
    const XML_MERCHANT_Salt = 'payment/zoodpayment/merchant_salt';
    const XML_MERCHANT_ACTIVE = 'payment/zoodpayment/active';
    const XML_MERCHANT_STATUS = 'payment/zoodpayment/merchant_status';
    const XML_MERCHANT_TOKEN = 'payment/zoodpayment/merchant_token';
    const XML_API_URL = 'payment/zoodpayment/zoodpay_api_url';
    const XML_API_Ver = 'payment/zoodpayment/zoodpay_api_ver';
    const XML_API_HEALTH = 'payment/zoodpayment/zoodpay_api_health';
    const XML_Service_Configuration = 'payment/zoodpayment/zoodpay_service_configuration';
    const XML_Display_Info_ProductPage = 'payment/zoodpayment/zoodpay_display_info_checkbox';
    const XML_TC_URL = 'payment/zoodpayment/zoodpay_tc_url';
    const XML_Default_Country_Code = 'general/country/default';
    const XML_Default_Currency = 'currency/options/default';
    const XML_GATEWAY_TITLE = 'payment/zoodpayment/gateway_title';
    const API_CreateTransaction = '/transactions';
    const API_RefundTransaction = '/refunds';
    const API_GetConfigurations = '/configuration';
    const API_Delivery = "/delivery";
    const API_HealthCheck = 'healthcheck';
    const XML_API_HEALTH_hidden = 'payment/zoodpayment/zoodpay_api_health_hidden';

    public $_ApiUrl;
    protected $_cacheTypeList;
    protected $_cacheFrontendPool;
    protected $_configWriter;
    protected $_scopeConfig;
    protected $_zLogger;
    /**
     * @var $_encrypted
     */
    protected $_encrypted;
    private $_TokenValue;
    private $_localeResolver;

    /**
     * Data constructor.
     * @param Context $context
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param LoggerInterface $zLogger
     * @param Resolver $localeResolver
     */
    public function __construct(
        Context              $context,
        TypeListInterface    $cacheTypeList,
        Pool                 $cacheFrontendPool,
        ScopeConfigInterface $scopeConfig,
        WriterInterface      $configWriter,
        LoggerInterface      $zLogger,
        Resolver             $localeResolver,
        EncryptorInterface   $encrypted,
        PriceHelper          $priceHelper
    ) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->_zLogger = $zLogger;
        $this->_localeResolver = $localeResolver;
        $this->_encrypted = $encrypted;
        $this->_priceHelper = $priceHelper;
    }

    /**
     * @param Version $subject
     */
    public function flushCache(Version $subject)
    {
        $types = ['config', 'layout', 'block_html', 'collections', 'reflection', 'db_ddl', 'eav', 'config_integration', 'config_integration_api', 'full_page', 'translate', 'config_webservice'];
        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Clear Config, config_webservice, Full_Page,
     */
    public function flushConfig()
    {
        $_types = [

            'config',
            'config_webservice',
            'full_page'
        ];

        foreach ($_types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Clear Config, config_webservice, Full_Page,
     */
    public function flushPage()
    {
        $_types = [
            'layout',
            'full_page',
            'block_html'
        ];

        foreach ($_types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }
        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * @param $path = 'extension_name/general/data'
     * @param $value = '1'
     */
    public function SetConfigData($path, $value)
    {
        $this->_configWriter->save($path, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    /**
     * @return mixed
     */
    public function getTokenValue()
    {
        return $this->_TokenValue;
    }

    /**
     * @param mixed $TokenValue
     */
    public function setTokenValue($TokenValue)
    {
        $this->_TokenValue = $TokenValue;
    }

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        return $this->_ApiUrl;
    }

    /**
     * @param mixed $ApiUrl
     */
    public function setApiUrl($ApiUrl)
    {
        $this->_ApiUrl = $ApiUrl;
    }

    /**
     * @return mixed -- Array with the Configuration that stored
     */
    public function getZoodPayConfigurationArrayFormat()
    {
        $fetchConfigResponse = $this->GetConfigData(self::XML_Service_Configuration);
        $fetchConfigResponse = json_decode($fetchConfigResponse, true);
        return $fetchConfigResponse['configuration'];
    }

    /**
     * @param $Xml_Path *the XML Path reference to the desired configuration
     * @return *Value stored in the Config
     */
    public function GetConfigData($Xml_Path)
    {
        $storeScope = ScopeInterface::SCOPE_STORE;

        return $this->_scopeConfig->getValue($Xml_Path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $storeScope);
    }

    /**
     * @return * Current Language Local|string
     */
    public function getCurrentLocale()
    {
        $currentLocaleCode = $this->_localeResolver->getLocale(); // fr_CA

        return strstr($currentLocaleCode, '_', true);
    }

    public function encrypt($value)
    {
        return $this->_encrypted->encrypt($value);
    }

    public function decrypt($value)
    {
        return $this->_encrypted->decrypt($value);
    }

    /**
     * @param $data -- array of data or data that need to be sent
     * @param $api_end -- ending of url choose from the constant Values
     * @return array -- return array ($status_code,$curl_response )
     */
    public function curlPost($data, $api_end)
    {
        $merchantID = $this->GetConfigData(self::XML_MERCHANT_ID);
        $merchantKey = $this->GetConfigData(self::XML_MERCHANT_KEY);
        $data_string = json_encode($data);

        $apiUrl = $this->GetConfigData(self::XML_API_URL) . $this->GetConfigData(self::XML_API_Ver) . $api_end;
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$merchantID:$merchantKey");
        $curl_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ["statusCode" => $status_code,
            "response" => $curl_response];
    }

    public function curlGet($api_end, $Auth_Req)
    {
        $apiUrl = $this->GetConfigData(self::XML_API_URL) . $api_end;
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        if ($Auth_Req) {
            $merchantID = $this->GetConfigData(self::XML_MERCHANT_ID);
            $merchantKey = $this->GetConfigData(self::XML_MERCHANT_KEY);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$merchantID:$merchantKey");
        }

        $curl_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ["statusCode" => $status_code,
            "response" => $curl_response];
    }

    /**
     * @param $data -- array of data or data that need to be sent
     * @param $api_end -- ending of url choose from the constant Values
     * @return array -- return array ($status_code,$curl_response )
     */
    public function curlPUT($data, $api_end)
    {
        $merchantID = $this->GetConfigData(self::XML_MERCHANT_ID);
        $merchantKey = $this->GetConfigData(self::XML_MERCHANT_KEY);
        $data_string = json_encode($data);

        $apiUrl = $this->GetConfigData(self::XML_API_URL) . $this->GetConfigData(self::XML_API_Ver) . $api_end;

        $del_headers = ['Accept: application/json', 'Content-Length: ' . strlen($data_string), 'Authorization: Basic ' . base64_encode($merchantID . ':' . $merchantKey), 'Content-Type: application/json'];

        $D_ch = curl_init();
        curl_setopt($D_ch, CURLOPT_POST, 1);
        curl_setopt($D_ch, CURLOPT_URL, $apiUrl);
        curl_setopt($D_ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($D_ch, CURLOPT_HEADER, 0);
        curl_setopt($D_ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($D_ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($D_ch, CURLOPT_HTTPHEADER, $del_headers);
        curl_setopt($D_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($D_ch, CURLOPT_CUSTOMREQUEST, "PUT");
        $status_code = curl_getinfo($D_ch, CURLINFO_HTTP_CODE);
        $curl_response = curl_exec($D_ch);
        curl_close($D_ch);

        return ["statusCode" => $status_code,
            "response" => $curl_response];
    }
    public function setServiceInfo($response)
    {
        $fetchConfigResponse = json_decode($response, true, 512);
        $i=0;
        $availableServiceResult = __('ACTIVE_OPERATE') . "\r\n";
        $availableServiceResult .=  "================================ \r\n";
        $availableServiceResult .= __('AVAILABLE_SERVICE') . ": \r\n";
        $availableServiceResult .= "\r\n";
        foreach ($fetchConfigResponse['configuration'] as $iValue) {
            $availableServiceResult .= $i. ". " .$iValue['service_code'] . " ".__('LIMIT')." -> " .$this->_priceHelper->currency($iValue['min_limit'], true, false)  . " -- " .   $this->_priceHelper->currency($iValue['max_limit'], true, false) . "\r\n";
            $i++;
        }
        $this->SetConfigData(self::XML_MERCHANT_STATUS, $availableServiceResult);

    }
}
