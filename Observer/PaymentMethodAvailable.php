<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Checkout\Exception;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\OrderFactory;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class PaymentMethodAvailable implements \Magento\Framework\Event\ObserverInterface
{


    protected $_zLogger;
    protected $_checkoutSession;
    /**
     * @var zDataHelper
     */
    protected $_zDataHelper;


    public function __construct(
        LoggerInterface                 $zLogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        OrderFactory                    $orderFactory,
        zDataHelper                     $zDataHelper


    )

    {

        $this->_zLogger = $zLogger;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_zDataHelper = $zDataHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        //  Implement Availability based on the Min Maximum from Config.


        if ($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ACTIVE)) {


            $quote = $this->_checkoutSession->getQuote();
            $checkoutdata = $this->_checkoutSession->getData();
            $quoteTotal = $quote->getGrandTotal();


            //Value Without Shipping
            //  $quoteTotal = $quote->getSubtotal();


            $fetchConfigResponse = $this->_zDataHelper->getZoodPayConfigurationArrayFormat();
            $availability = false;
            if ($observer->getEvent()->getMethodInstance()->getCode() == PaymentMethod::CODE) {
                $checkResult = $observer->getEvent()->getResult();

                for ($i = 0, $iMax = count($fetchConfigResponse); $i < $iMax; $i++) {

                    if (($quoteTotal >= $fetchConfigResponse[$i]['min_limit']) && ($quoteTotal <= $fetchConfigResponse[$i]['max_limit'])) {

                        $availability = true;

                    }
                }

                if ($availability) {
                    //  $this->_zLogger->info('Order Value ('.$quoteTotal.') is within the range of ZoodPay Price Range ');
                }
                //else //  $this->_zLogger->notice('Order Value ('.$quoteTotal.') is not within the range of ZoodPay Price Range ');
                $checkResult->setData('is_available', $availability); //this is disabling the payment method at checkout page


            }
        }
    }
}
