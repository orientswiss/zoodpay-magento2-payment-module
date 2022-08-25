<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class SalesOrderPaymentCapture implements \Magento\Framework\Event\ObserverInterface
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

        /** @var Payment $payment */
        $payment = $observer->getEvent()->getPayment();
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();

        try {

            if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {

                //  $this->_zLogger->info("Capture Event Occurred");
                $apiUrlEnding = $this->_zDataHelper->GetConfigData(zDataHelper::XML_API_Ver) . zDataHelper::API_CreateTransaction . "/" . $payment->getLastTransId();
                $curlResponse = $this->_zDataHelper->curlGet($apiUrlEnding, true);
                if ($curlResponse['statusCode'] == 200) {
                    switch ($curlResponse['response']['status']) {
                        case 'Paid' :
                        {

                            $invoice->capture();
                            $invoice->addComment('The Payment Captured');
                            $invoice->save();
                            break;

                        }

                    }


                } else {
                    $payment->setIsTransactionPending(true);
                    $payment->setAmountPaid(0);
                    $payment->cancel();
                    $payment->save();
                    $invoice->setState(Invoice::STATE_OPEN);
                    $invoice->
                    $invoice->addComment('The Payment Still did not Captured');
                    $invoice->save();
                }


            }
        } catch (LocalizedException $exception) {
            //  $this->_zLogger->critical($exception->getMessage());
        } catch (\Exception $e) {
            //  $this->_zLogger->critical($e->getMessage());
        }


    }
}
