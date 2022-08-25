<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;


class preDispatchCheckoutObserver implements \Magento\Framework\Event\ObserverInterface
{


    protected $_zLogger;
    private $_checkoutSession;

    public function __construct(

        LoggerInterface $zLogger
    )
    {
        $this->_zLogger = $zLogger;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->get('Magento\Checkout\Model\Session\Proxy');
        $lastRealOrder = $checkoutSession->getLastRealOrder();


        if ($lastRealOrder->getPayment()) {

            if ($lastRealOrder->getPayment()->getMethodInstance()->getCode() == PaymentMethod::CODE) {
                //  $this->_zLogger->notice("User pressed back button from the ZoodPay Payment Gateway");
                if ($lastRealOrder->getData('state') === 'pending_payment' && $lastRealOrder->getData('status') === 'pending_payment') {
                    $checkoutSession->restoreQuote();
                }

            }

        }
        return true;

    }
}
