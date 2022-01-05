<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class salesOrderPaymentRefund implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var LoggerInterface
     */
    private $_zLogger;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $_date;

    /**
     * orderSaveAfter constructor.
     * @param LoggerInterface $zLogger
     * @param zDataHelper $zDataHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        LoggerInterface                             $zLogger,
        zDataHelper                                 $zDataHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        $this->_zLogger = $zLogger;
        $this->_zDataHelper = $zDataHelper;
        $this->_date = $date;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {

//        $payment = $observer->getData('payment');
//        $paymentdata = $payment->getData();
//        unset($paymentdata['created_transaction']);
//        $paymentdata['refund_transaction_id'] = null;
//        $paymentdata['should_close_parent_transaction'] = null;
//        $paymentdata['transaction_id'] = null;
//        $paymentdata['last_trans_id'] = $paymentdata['parent_transaction_id'];
//
//
//
//        $payment->setData($paymentdata);
//        $payment->save();

        $creditmemo = $observer->getData('creditmemo');


    }


}
