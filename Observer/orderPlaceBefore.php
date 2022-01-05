<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Checkout\Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Sales\Model\Order;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use Magento\Framework\App\RequestInterface;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Model\PaymentMethod;


class orderPlaceBefore implements \Magento\Framework\Event\ObserverInterface
{


    /**
     * @var zDataHelper
     */
    protected $_zDataHelper;
    /**
     * @var LoggerInterface
     */
    private $_zLogger;

    public function __construct(

        LoggerInterface $logger,

        zDataHelper     $zDataHelper


    )
    {

        $this->_zLogger = $logger;
        $this->_zDataHelper = $zDataHelper;


    }

    public function execute(Observer $observer)
    {
        /** @var  $order -- Get the Order From Observer */
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {
            //  $this->_zLogger->info('inside Before Place an Order');
        }

    }

}
