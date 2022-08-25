<?php


namespace OrientSwiss\ZoodPay\Controller\Payment;


use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Helper\Order\OrderTransactionHelperInterface;
use OrientSwiss\ZoodPay\Logger\Zlogger;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class Decline extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{


//    private $params = ['amount','created_at', 'status', 'transaction_id','merchant_order_reference','signature', 'errorMessage'];
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $_coreSession;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Zlogger
     */
    private $_zLogger;
    /**
     * @var OrderTransactionHelperInterface
     */
    private $_orderTransactionHelper;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $_orderRepository;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    private $webrequest;
    /**
     * @var Http
     */
    private $httpRequest;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context              $context,
        JsonFactory                                        $resultJsonFactory,
        RequestInterface                                   $request,
        Zlogger                                            $zLogger,
        zDataHelper                                        $zDataHelper,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        OrderTransactionHelperInterface                    $orderTransactionHelper,
        \Magento\Sales\Model\OrderRepository               $orderRepository,
        \Magento\Framework\Webapi\Rest\Request             $webRequest,

        Http                                               $httpRequest
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct(
            $context
        );

        $this->_coreSession = $coreSession;
        $this->request = $request;
        $this->_zLogger = $zLogger;
        $this->_orderTransactionHelper = $orderTransactionHelper;
        $this->_orderRepository = $orderRepository;
        $this->webrequest = $webRequest;
        $this->httpRequest = $httpRequest;
        $this->_zDataHelper = $zDataHelper;
    }

    public function execute()
    {
        $this->_coreSession->start();
        $pageMessage = '';
        $result = $this->resultJsonFactory->create();
        $data = $this->webrequest->getBodyParams();
        if (!empty($data)) {


            $transactionID = $data['transaction_id'];

            if (isset($data['errorMessage']))
                $pageMessage = $data['errorMessage'];
            else
                $data['errorMessage'] = '';


            try {


                /** @var TransactionInterface $paymentData */
                $paymentData = $this->_orderTransactionHelper->getPaymentData($transactionID);
                if (isset($paymentData)) {
                    $orderId = $paymentData->getParentId();


                    $order = $this->_orderRepository->get($orderId);
                    /** @var Order\Payment $payment */
                    $payment = $order->getPayment();
                    if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {


                        $merchant_key = $this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ID);
                        $market_code = $this->_zDataHelper->GetConfigData(zDataHelper::XML_Default_Country_Code);
                        $salt = $this->_zDataHelper->decrypt($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_Salt));
                        $amount = $order->getGrandTotal();
                        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                        $currencyCode = $storeManager->getStore()->getCurrentCurrencyCode();
                        $merchant_reference_no = $order->getIncrementId();
                        $transactionAmount = $data['amount'];

                        $localString = implode("|", array($market_code, $currencyCode, $transactionAmount, $merchant_reference_no, $merchant_key, $transactionID, $salt));
                        $localSignature = hash('sha512', $localString);

                        if ($localSignature == $data['signature']) {
                            switch ($data['status']) {

                                case "Cancelled":
                                case "Failed" :
                                {
                                    $order->setStatus(Order::STATE_CANCELED);
                                    $order->setState(Order::STATE_CANCELED);

                                    try {
                                        $this->_orderRepository->save($order);
                                    } catch (\Exception $e) {
                                        // //  $this->_zLogger->error($e);
                                        $this->messageManager->addExceptionMessage($e, $e->getMessage());
                                    }


                                    // //  $this->_zLogger->notice($pageMessage);
                                    // $pageMessage = $data['errorMessage'];
                                    $pageMessage = __("PLACING_ORDER_ERROR");

                                    break;
                                }
                                case "Inactive" :{

                                    $order->setStatus(Order::STATE_PENDING_PAYMENT);
                                    $order->setState(Order::STATE_PENDING_PAYMENT);

                                    try {
                                        $this->_orderRepository->save($order);
                                    } catch (\Exception $e) {
                                        // //  $this->_zLogger->error($e);
                                        $this->messageManager->addExceptionMessage($e, $e->getMessage());
                                    }
                                    $pageMessage = __("PLACING_ORDER_ERROR");
                                    break;

                                }

                            }

                        }


                    }

                }


            } catch (\Exception $exception) {
                // //  $this->_zLogger->critical($exception->getMessage());
            }
        }
        $url = ($this->_url->getRedirectUrl($this->_url->getBaseUrl() . 'zoodpay/Checkout/errorPage/'));
        $this->_coreSession->setPageMessage($pageMessage);
        $this->_redirect->redirect($this->getResponse(), ($url));
        //  return $result->setData($data);
    }
//    private function getPostParams()
//    {
//        foreach ($this->params as $k)
//        {
//            $this->params[$k] =  $this->request->getParam($k);
//        }
//    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        // TODO: Implement createCsrfValidationException() method.
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // TODO: Implement validateForCsrf() method.
        return true;
    }
}
