<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class orderSaveAfter implements \Magento\Framework\Event\ObserverInterface
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


        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getIncrementId();
        $OrderAllStatus = $order->getAllStatusHistory();
        $orderHistoryCollection = $order->getStatusHistoryCollection();


        if ($order->getPayment()->getMethodInstance()->getCode() == PaymentMethod::CODE) {


            if ($order->getState() == Order::STATE_COMPLETE) {
                // Your code after completer state goes to here
                //  $this->_zLogger->critical("The status for Order $orderId set as Complete ");
                $apiUrlEnding = "/transactions/" . $order->getPayment()->getLastTransId() . $this->_zDataHelper::API_Delivery;
                $data = [
                    "delivered_at" => $this->_date->date(),
                    "final_capture_amount" => $order->getGrandTotal(),
                ];
                $curlResponse = $this->_zDataHelper->curlPUT($data, $apiUrlEnding);
                // ACK request accepted by ZoodPay
                if (isset($curlResponse)) {

                    //  $this->_zLogger->notice($curlResponse['response']);
                    if ($curlResponse['statusCode'] == 200) {

                        $curlResponseArray = json_decode($curlResponse['response'], true);
                        $delivered_at = $curlResponseArray['delivered_at'];
                        $final_capture_amount = $curlResponseArray['final_capture_amount'];
                        $original_amount = $curlResponseArray['original_amount'];
                        $status = $curlResponseArray['status'];
                        $transaction_id = $curlResponseArray['transaction_id'];
                        //  $this->_zLogger->notice("The Complete Status Send to the ZoodPay API for Transaction ID $transaction_id and Status in ZoodPay Server is $status" );
                    }
                }


            }


        }

    }

}
