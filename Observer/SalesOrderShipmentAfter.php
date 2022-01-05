<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class SalesOrderShipmentAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var LoggerInterface
     */
    private $_zLogger;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;

    public function __construct(
        LoggerInterface $zLogger,
        zDataHelper     $zDataHelper)
    {
        $this->_zLogger = $zLogger;
        $this->_zDataHelper = $zDataHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        // There is no delivery Status in Magento


        //  $this->_zLogger->info("Shipment Save Event Occurred");

        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();


        $orderId = $order->getIncrementId();

        if ($order->getPayment()->getMethodInstance()->getCode() == PaymentMethod::CODE) {
            if ($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ACTIVE)) {

                if ($order->getState() == Order::STATE_COMPLETE) {
                    //Your code after completer state goes to here
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
}
