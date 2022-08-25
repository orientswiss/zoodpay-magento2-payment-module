<?php


namespace OrientSwiss\ZoodPay\Controller\Payment;


use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Helper\Order\OrderTransactionHelperInterface;
use OrientSwiss\ZoodPay\Logger\Zlogger;
use OrientSwiss\ZoodPay\Model\PaymentMethod;
use \Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Success extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpPostActionInterface
{


    protected $resultJsonFactory;
    protected $request;
    protected $_customerSession;
    protected $_zLogger = null;
    private $params = ['amount', 'created_at', 'status', 'transaction_id', 'merchant_order_reference', 'signature'];
    /**
     * @var OrderTransactionHelperInterface
     */
    private $_orderTransactionHelper;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $_orderRepository;
    /**
     * @var $_zDataHelper
     */
    private $_zDataHelper;
    /**
     * @var Order\Payment\Transaction\Builder
     */
    private $_transactionBuilder;
    /**
     * @var InvoiceService
     */
    private $_invoiceService;
    /**
     * @var InvoiceSender
     */
    private $_invoiceSender;
    /**
     * @var Transaction
     */
    private $_transactionDB;
    /**
     * @var Http
     */
    private $httpRequest;
    /**
     * @var Transaction
     */
    private $transactionDB;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var string
     */
    private $pdata;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    private $webrequest;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $_coreSession;

    public function __construct(
        \Magento\Framework\App\Action\Context                  $context,
        JsonFactory                                            $resultJsonFactory,
        RequestInterface                                       $request,
        \Magento\Customer\Model\Session                        $customerSession,
        Zlogger                                                $zLogger,
        OrderTransactionHelperInterface                        $orderTransactionHelper,
        \Magento\Sales\Model\OrderRepository                   $orderRepository,
        zDataHelper                                            $zDataHelper,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder,
        InvoiceService                                         $invoiceService,
        InvoiceSender                                          $invoiceSender,
        \Magento\Framework\Session\SessionManagerInterface     $coreSession,
        Transaction                                            $transactionDB,
        Http                                                   $httpRequest,
        \Magento\Framework\Webapi\Rest\Request                 $webrequest
    )

    {
        parent::__construct(
            $context
        );
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->_customerSession = $customerSession;
        $this->_zLogger = $zLogger;
        $this->_orderTransactionHelper = $orderTransactionHelper;
        $this->_orderRepository = $orderRepository;
        $this->_zDataHelper = $zDataHelper;
        $this->_transactionBuilder = $transactionBuilder;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transactionDB = $transactionDB;
        $this->httpRequest = $httpRequest;
        $this->webrequest = $webrequest;
        $this->_coreSession = $coreSession;


    }

    public function execute()
    {

        $data = $this->webrequest->getBodyParams();


        $resultJsonFactory = $this->resultJsonFactory->create();
        $this->_coreSession->start();
        $pageMessage = '';


        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $currencyCode = $storeManager->getStore()->getCurrentCurrencyCode();
        $url = $this->_url->getBaseUrl();
        // Amount is total amount
        /*
         *
         *
         */


        try {


            if (!empty($data)) {


                /** @var  $transactionAmount */

                $transactionAmount = $data['amount'];

                /** @var  $createdAt */

                $createdAt = $data['created_at'];

                /** @var string $transactionStatus */

                $transactionStatus = $data['status'];


                /** @var  $transactionID */

                $transactionID = $data['transaction_id'];


                /** @var int $merchantRefrenceNumber */

                $merchantRefrenceNumber = $data['merchant_order_reference'];


                /** @var string $signature */

                $signature = $data['signature'];


                if (isset($transactionAmount, $createdAt, $transactionStatus, $transactionID, $merchantRefrenceNumber, $signature)) {


                    /** @var TransactionInterface $transactionData -- Not used  Due to Change of Logic */
                    // $transactionData = $this->_orderTransactionHelper->getTransactionData($transactionID);
                    /** @var TransactionInterface $paymentData */
                    $paymentData = $this->_orderTransactionHelper->getPaymentData($transactionID);


                    if (isset($paymentData)) {

                        $orderId = $paymentData->getParentId();

                        /** @var Order $order -- Get the Order Model From OrderID */
                        //  $order = $this->_orderTransactionHelper->getOrderModel($orderId);
                        $order = $this->_orderRepository->get($orderId);
                        /** @var Order\Payment $payment -- Get The Payment */
                        $payment = $order->getPayment();
                        $orderIncID = $order->getIncrementId();
                        $merchant_key = $this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ID);
                        $market_code = $this->_zDataHelper->GetConfigData(zDataHelper::XML_Default_Country_Code);
                        $salt = $this->_zDataHelper->decrypt($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_Salt));
                        $amount = $order->getGrandTotal();

                        $merchant_reference_no = $order->getIncrementId();
                        $additionalData = json_decode($payment->getAdditionalData(), true);

                        $paymentSelectedService = $additionalData['selected_service']['service_code'];
                        $paymentSelectedServiceType = $additionalData['selected_service']['service_type'];


                        if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {

                            $localString = implode("|", array($market_code, $currencyCode, $transactionAmount, $merchant_reference_no, $merchant_key, $transactionID, $salt));
                            $localSignature = hash('sha512', $localString);

                            if ($localSignature === $signature) {

                                $orderStatus = $order->getStatus();


                                switch ($orderStatus) {
                                    case Order::STATE_PROCESSING :
                                    {
                                        $pageMessage = __("PAYMENT_NOT_ACCEPTED") . " " . $orderIncID;

                                        // //  $this->_zLogger->critical('Trying to tamper The order: ' .$orderId);
                                        $url = ($this->_url->getRedirectUrl($this->_url->getBaseUrl() . 'zoodpay/Checkout/errorPage/'));
                                        $this->_coreSession->setPageMessage($pageMessage);
                                        $this->_redirect->redirect($this->getResponse(), ($url));
                                    }

                                    case Order::STATE_HOLDED :
                                    {
                                        $pageMessage = __("PAYMENT_NOT_ACCEPTED") . " " . $orderIncID;

                                        // //  $this->_zLogger->critical('Trying to tamper The order: ' .$orderId);
                                        $url = ($this->_url->getRedirectUrl($this->_url->getBaseUrl() . 'zoodpay/Checkout/errorPage/'));
                                        $this->_coreSession->setPageMessage($pageMessage);
                                        $this->_redirect->redirect($this->getResponse(), ($url));
                                    }
                                    default :
                                        break;
                                }


                                switch ($data['status']) {
                                    case "Paid" :
                                    {

                                        /*
                                        *
                                        * Set the Transaction ID in the DataBase
                                        *
                                        */

                                        if ((!($order->getStatus() != Order::STATE_CLOSED) || ($order->getStatus() != Order::STATE_CANCELED) || ($order->getStatus() != Order::STATE_HOLDED))) {
                                            $payment->setLastTransId($transactionID);
                                            $payment->setTransactionId($transactionID);


                                            $trans = $this->_transactionBuilder;
                                            $transaction = $trans->setPayment($payment)
                                                ->setOrder($order)
                                                ->setTransactionId($transactionID)
                                                ->setFailSafe(true)
                                                //build method creates the transaction and returns the object
                                                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

                                            $payment->addTransactionCommentsToOrder(
                                                $transaction,
                                                "Paid "
                                            );


                                            $payment->setBaseAmountPaid($order->getBaseGrandTotal());
                                            $payment->setParentTransactionId(null);
                                            $payment->save();


                                            $payment->setParentTransactionId(null);


                                            if ($order->canInvoice()) {
                                                $invoice = $order->prepareInvoice();
                                                $invoice->register();
                                                /*
                                               * $invoice->capture() means that payment is Done and SubTotal due = 0
                                               *
                                               */
                                                $invoice->capture();

                                                $invoice->addComment('The Payment Captured');
//                                        $payment->capture($invoice);
                                                $invoice->save();
                                            } else {
                                                $_invoices = $order->getInvoiceCollection();
                                                $this->_objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);
                                                if ($_invoices) {

                                                    $inv_count = $_invoices->count();

                                                    if ($inv_count > 1) {
                                                        foreach ($_invoices as $invoice) {
                                                            $invoice->delete();
                                                        }
                                                        $invoice = $order->prepareInvoice();
                                                        $invoice->register();
                                                        /*
                                                       * $invoice->capture() means that payment is Done and SubTotal due = 0
                                                       *
                                                       */
                                                        $invoice->capture();
                                                        $invoice->addComment('The Payment Captured');
//                                        $payment->capture($invoice);
                                                        $invoice->save();
                                                    } else if ($inv_count == 0) {
                                                        $invoice = $order->prepareInvoice();
                                                        $invoice->register();
                                                        /*
                                                       * $invoice->capture() means that payment is Done and SubTotal due = 0
                                                       *
                                                       */
                                                        $invoice->capture();
                                                        $invoice->addComment('The Payment Captured');
//                                        $payment->capture($invoice);
                                                        $invoice->save();
                                                    }

                                                }

                                            }


                                            //     // //  $this->_zLogger->info("transaction ID: ". $transaction->getTxnId(). $transaction->getCreatedAt());

                                            $order->setStatus(Order::STATE_PROCESSING);
                                            $order->setState(Order::STATE_PROCESSING);
                                            //

                                            try {
                                                $this->_orderRepository->save($order);
                                            } catch (\Exception $e) {
                                                // //  $this->_zLogger->error($e);
                                                $this->messageManager->addExceptionMessage($e, $e->getMessage());
                                            }

                                            // //  $this->_zLogger->notice("SUCCESS Status for Order: ".$orderId);
                                        }

                                        $pageMessage = __("PAYMENT_ACCEPTED");
                                        // //  $this->_zLogger->notice($pageMessage);
                                        $url = ($this->_url->getRedirectUrl($this->_url->getBaseUrl() . 'zoodpay/Checkout/successPage/'));


                                        break;
                                    }


                                }

                            }

                        } else {
                            //  $this->_zLogger->notice("trying to tamper the Order: ".$orderId );
                        }


                    }
                }

            }


        } catch (\Exception $exception) {
            //  $this->_zLogger->critical($exception->getMessage());
        }


        $this->_coreSession->setPageMessage($pageMessage);
        $this->_redirect->redirect($this->getResponse(), ($url));
    }


    public function validateForCsrf(RequestInterface $request): ?bool
    {

        // TODO: Implement validateForCsrf() method.
        return true;
    }


    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        // TODO: Implement createCsrfValidationException() method.
        return null;
    }
}

