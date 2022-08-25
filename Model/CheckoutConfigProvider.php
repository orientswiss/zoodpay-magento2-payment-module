<?php


namespace OrientSwiss\ZoodPay\Model;


use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Sales\Model\OrderFactory;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use Magento\Framework\Pricing\Helper\Data as priceHelper;
use function MongoDB\BSON\toJSON;

class CheckoutConfigProvider implements ConfigProviderInterface
{


    protected $_zLogger;
    protected $_checkoutSession;
    /**
     * @var zDataHelper
     */
    protected $_zDataHelper;
    protected $_priceHelper;
    protected $_customerSession;
    protected $_orderFactory;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $_url;


    public function __construct(
        LoggerInterface                 $zLogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        OrderFactory                    $orderFactory,
        zDataHelper                     $zDataHelper,
        priceHelper                     $priceHelper,
        \Magento\Framework\UrlInterface $url
    )

    {
        $this->_zLogger = $zLogger;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_zDataHelper = $zDataHelper;
        $this->_priceHelper = $priceHelper;

        $this->_url = $url;
    }

    public function getConfig()
    {
        $config = [];
        if ($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ACTIVE)) {

            //   Implement getConfig() method.

            //  $this->_zLogger->info('inside Get config');
            //  $this->_zLogger->info( $this->_customerSession->getCallBackURL());

            $config = array_merge_recursive($config, [
                'payment' => [
                    \OrientSwiss\ZoodPay\Model\PaymentMethod::CODE => [
                        'zoodpayAvailableService' => $this->getzoodpayAvailableService(),

                        'zoodpayTCURL' => $this->_zDataHelper->GetConfigData(zDataHelper::XML_TC_URL),
                        'zoodpayCallBackURL' => $this->_url->getBaseUrl() . 'zoodpay/Checkout/redirectPage/'


                    ],
                ],
            ]);


        } else {
            $config = array_merge_recursive($config, [
                'payment' => [
                    \OrientSwiss\ZoodPay\Model\PaymentMethod::CODE => [
                        'zoodpayAvailableService' => "",
                        'zoodpayTCURL' => "",
                        'zoodpayCallBackURL' => $this->_url->getBaseUrl() . 'zoodpay/Checkout/redirectPage/'
                    ],
                ],
            ]);
        }
        return $config;


    }

    /**
     * @inheritDoc
     */

    public function getzoodpayAvailableService()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Framework\Locale\Resolver');
        $locale = $store->getLocale();
        // Implement Availability based on the Min Maximum from Config.
        $quote = $this->_checkoutSession->getQuote();

//        $quoteData= $quote->getData();
//            $oo = $quote->getTotals() ;
        $quoteTotal = $quote->getBaseGrandTotal();


//            $sh = $quote->getShippingAddress();
//           $tt=  $sh->getShippingAmount();
//           $yy= $sh->getTotals();
//           $zz = $sh->getGrandTotal();
//           $ll = $sh->getCollectShippingRates();
//           $pp = $sh->getBaseShippingAmount();
        $fetchConfigResponse = $this->_zDataHelper->getZoodPayConfigurationArrayFormat();
        $availableServiceResult = array();
        $k = 0;
        for ($i = 0, $iMax = count($fetchConfigResponse); $i < $iMax; $i++) {

            if (($quoteTotal >= $fetchConfigResponse[$i]['min_limit']) && ($quoteTotal <= $fetchConfigResponse[$i]['max_limit'])) {
                $serviceName = $fetchConfigResponse[$i]['service_name'];
                $serviceCode = $fetchConfigResponse[$i]['service_code'];
                if (isset($fetchConfigResponse[$i]['instalments'])) {
                    //  $block->getViewFileUrl('OrientSwiss_ZoodPay::images/zoodpay_'.$serviceCode.$locale.'.png')
                    $monthlyPayment = $quoteTotal / $fetchConfigResponse[$i]['instalments'];
                    $monthlyPayment = $this->_priceHelper->currency($monthlyPayment, true, false); //Return thr Value with Currency Symbol
                    $availableServiceResult[$k] = [
                        'zoodpayLan' => $locale,
                        "service_code" => $serviceCode,
                        "service_monthly_text" => $fetchConfigResponse[$i]['instalments'] . " Monthly $serviceName of $monthlyPayment With ZoodPay ($serviceCode)",
                        "service_type" => $serviceName,
                        "service_installment_bool" => true,
                        "service_installment" => $fetchConfigResponse[$i]['instalments'],
                        "service_terms_pop" => "popup$k",
                        "service_of" => __('OF'),
                        "service_terms_open" => "{

                         var modal = document.getElementById('popup$k');
    modal.style.display = 'block';


                        }",
                        "service_terms_close" => "{

                         var modal = document.getElementById('popup$k');
    modal.style.display = 'none';


                        }",

                        "service_description" => htmlspecialchars_decode($fetchConfigResponse[$i]['description'])
                    ];
                    $k++;
                } else {

                    $availableServiceResult[$k] = [
                        "service_code" => $serviceCode,
                        "service_installment_bool" => false,
                        "service_monthly_text" => "$serviceName",
                        "service_type" => $serviceName,
                        "service_terms_pop" => "popup$k",
                        'zoodpayLan' => $locale,
                        "service_terms_open" => "{

                         var modal = document.getElementById('popup$k');
    modal.style.display = 'block';


                        }",
                        "service_terms_close" => "{

                         var modal = document.getElementById('popup$k');
    modal.style.display = 'none';


                        }",
                        "service_description" => htmlspecialchars_decode($fetchConfigResponse[$i]['description'])
                    ];
                    $k++;

                }


            }


        }


        $cc = count($availableServiceResult);


        return $availableServiceResult;
    }


}
