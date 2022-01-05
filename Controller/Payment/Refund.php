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
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Helper\Order\OrderTransactionHelperInterface;
use OrientSwiss\ZoodPay\Logger\Zlogger;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class Refund extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{


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
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $_filterBuilder;
    /**
     * @var Order\Payment\Repository
     */
    private $_paymentRepository;
    /**
     * @var Order
     */
    private $_orderModel;
    /**
     * @var CreditmemoInterface
     */
    private $_creditmemoInterface;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection
     */
    private $_creditmemoCollection;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;
    /**
     * @var Order\Payment\Transaction\Repository
     */
    private $_transactionRepository;
    /**
     * @var Order\Payment\Transaction\Builder
     */
    private $_transactionBuilder;

    public function __construct(
        \Magento\Framework\App\Action\Context                          $context,
        JsonFactory                                                    $resultJsonFactory,
        RequestInterface                                               $request,
        Zlogger                                                        $zLogger,
        zDataHelper                                                    $zDataHelper,
        \Magento\Framework\Session\SessionManagerInterface             $coreSession,
        OrderTransactionHelperInterface                                $orderTransactionHelper,
//        \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder,
        \Magento\Sales\Model\OrderRepository                           $orderRepository,
        \Magento\Framework\Webapi\Rest\Request                         $webRequest,
        Http                                                           $httpRequest,
        \Magento\Framework\Api\SearchCriteriaBuilder                   $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder                           $filterBuilder,
        \Magento\Sales\Model\Order                                     $orderModel,
        \Magento\Sales\Model\Order\Payment\Repository                  $paymentRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository      $transactionRepository,
        CreditmemoInterface                                            $creditmemoInterface,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection $creditmemoCollection
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct(
            $context
        );

        $this->_coreSession = $coreSession;
        $this->request = $request;
        $this->_zLogger = $zLogger;
        $this->_zDataHelper = $zDataHelper;
        $this->_orderTransactionHelper = $orderTransactionHelper;
        $this->_orderRepository = $orderRepository;
        $this->webrequest = $webRequest;
        $this->httpRequest = $httpRequest;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_paymentRepository = $paymentRepository;
        $this->_orderRepository = $orderRepository;
        $this->_orderModel = $orderModel;
        $this->_orderTransactionHelper = $orderTransactionHelper;
        $this->_creditmemoInterface = $creditmemoInterface;
        $this->_creditmemoCollection = $creditmemoCollection;
        $this->_transactionRepository = $transactionRepository;
//        $this->_transactionBuilder = $transactionBuilder;

    }

    public function execute()
    {
        $this->_coreSession->start();
        $pageMessage = '';
        $result = $this->resultJsonFactory->create();
        $data = $this->webrequest->getBodyParams();
//        $data = $this->httpRequest->getContent();
        if (!empty($data)) {

            $transactionID = $data['refund']['merchant_refund_reference'];


            try {


                /** @var TransactionInterface $paymentData */
                $paymentData = $this->_orderTransactionHelper->getPaymentData($transactionID);

                if (isset($paymentData)) {
                    $orderId = $paymentData->getParentId();


                    // $order = $this->_orderRepository->get($orderId);
                    $order = $this->_orderTransactionHelper->getOrderModel($orderId);
                    /** @var Order\Payment $payment */
                    $payment = $order->getPayment();
                    if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {


                        $additionalData = json_decode($payment->getAdditionalData(), true);

//
//                        if(isset($additionalData['refund']['status']))
//                        {
//                            if(($additionalData['refund']['status'] != "Done" ||  $additionalData['refund']['status'] != "Processed") && $additionalData['refund']['status'] != "Declined"  ) {

                        $merchant_key = $this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ID);

                        $salt = $this->_zDataHelper->decrypt($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_Salt));

                        $merchant_refund_reference = $data['refund']['merchant_refund_reference'];
                        $refund_amount = $data['refund']['refund_amount'];
                        $refund_status = $data['refund']['status'];
                        $refund_id = $data['refund']['refund_id'];


                        $localString = implode("|", array($merchant_refund_reference, $refund_amount, $refund_status, $merchant_key, $refund_id, $salt));
                        $localSignature = hash('sha512', $localString);//exit;

                        if ($localSignature == $data['signature']) {


                            $creditMemoCollection = null;
                            $creditMemoCollection = $order->getCreditmemosCollection();


                            $filters[] = $this->_filterBuilder->setField('payment_id')
                                ->setValue($order->getPayment()->getId())
                                ->create();

                            $filters[] = $this->_filterBuilder->setField('order_id')
                                ->setValue($order->getId())
                                ->create();

                            $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filters)
                                ->create();

                            $transactionList = $this->_transactionRepository->getList($searchCriteria);
                            $transactionData = $transactionList->getData();


                            foreach ($creditMemoCollection as $creditMemo) {

                                /** @var CreditmemoInterface $creditMemo */


                                if ($creditMemo->getCreditmemoStatus() == \Magento\Sales\Model\Order\Creditmemo::STATE_OPEN) {
                                    if ($data['refund']['merchant_refund_reference'] == $creditMemo->getTransactionId()) {


//                                    $apiUrlEnding = $this->_zDataHelper->GetConfigData(zDataHelper::XML_API_Ver).zDataHelper::API_RefundTransaction."/". $additionalData['refund']['refund_id'] ;
//
//                                    /** @var zDataHelper $curlResponse
//                                     *      Sent Post with Payload to the Zoodpay API And receive back the response
//                                     *      Do Not Delete
//                                     */
//                                    $curlResponse =  $this->_zDataHelper->curlGet($apiUrlEnding,true);


                                        switch ($data['refund']['status']) {


                                            case 'Approved':
                                            {
//                                                    $temp = array_pop($additionalData);
//
//                                                    $additionalData["refund"] = ["refund_id" => $data['refund_id'], "status" => $data['refund']['status']];


//                                                    $payment->setAdditionalData(json_encode($additionalData));
                                                $refundTemp = $additionalData['refund'];
                                                $refundStatus = null;
                                                $orderTemp = null;
                                                foreach ($refundTemp as $key => $value) {

                                                    if ($value['refund_tr'] == $merchant_refund_reference) {
                                                        $refundTemp[$key]['refund_status'] = 1;
                                                        $orderTemp = $value['0'];
                                                        $refundStatus = $refundTemp[$key]['os'];
                                                    }
                                                }

                                                $totalOnlineRefunded = 0;
                                                $shippingAmountRefunded = 0;
                                                foreach ($refundTemp as $key => $value) {

                                                    if ($refundTemp[$key]['refund_status'] == 1) {

                                                        $totalOnlineRefunded += (floatval($refundTemp[$key]['refund_amount']));
                                                        $shippingAmountRefunded += (floatval($refundTemp[$key]['sa']));
                                                    }
                                                }

                                                $total_refunded = $totalOnlineRefunded + $order->getTotalOfflineRefunded();


                                                $additionalData['refund'] = $refundTemp;
                                                $payment->setAdditionalData(json_encode($additionalData));


                                                //  $total_refunded=  $order->getTotalRefunded() + (float) $data['refund']['refund_amount'];
                                                $order = $creditMemo->getOrder();
                                                $orderItems = $order->getData('items');

                                                foreach ($orderItems as $key => $value) {


                                                    $dataArray = $value->getData();
                                                    $temp = $value;

                                                    foreach ($orderTemp as $key2 => $value2) {

                                                        if ($key == $key2) {
                                                            $dataArray['qty_refunded'] = $value2;
                                                        }
                                                    }


                                                    $value->setData($dataArray);
                                                    $orderItems[$key] = $value;

                                                }

                                                $order->setData('items', $orderItems);


                                                $creditMemo->addComment("The Amount refunded.");

                                                $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
                                                $creditMemo->setData('do_transaction', 0);

                                                $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);


                                                $order->setState($refundStatus['oa']);
                                                $order->setStatus($refundStatus['ou']);
                                                $order->setTotalRefunded($total_refunded);
                                                $order->setTotalOnlineRefunded($totalOnlineRefunded);
                                                $order->setBaseShippingRefunded($shippingAmountRefunded);
                                                $order->setShippingRefunded($shippingAmountRefunded);
                                                $order->setBaseTotalOnlineRefunded($totalOnlineRefunded);
                                                $order->setBaseTotalRefunded($total_refunded);
                                                $order->addCommentToStatusHistory(__('The Requested amount Have Been Approved for Transaction ID.' . $merchant_refund_reference));


                                                $pageMessage = "Refund Approved";
                                                break;
                                            }


                                            case 'Initiated' :
                                            {
                                                $creditMemo->addComment("The decision is still under process.");
                                                $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                                                $creditMemo->setData('do_transaction', 0);

                                                $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);

                                                $pageMessage = " Refund Initiated";
                                                break;

                                            }
                                            case 'Declined' :
                                            {


                                                $refundTemp = $additionalData['refund'];


                                                $totalOnlineRefunded = 0;
                                                $shippingAmountRefunded = 0;
                                                foreach ($refundTemp as $key => $value) {

                                                    if ($refundTemp[$key]['refund_status'] == 1) {

                                                        $totalOnlineRefunded += (floatval($refundTemp[$key]['refund_amount']));
                                                        $shippingAmountRefunded += (floatval($refundTemp[$key]['sa']));
                                                    }
                                                }
                                                $total_refunded = $totalOnlineRefunded + $order->getTotalOfflineRefunded();
                                                $refundStatus = null;
                                                foreach ($refundTemp as $key => $value) {

                                                    if ($value['refund_tr'] == $merchant_refund_reference) {
                                                        $refundStatus = $refundTemp[$key]['os'];
                                                    }
                                                }


                                                $creditMemo->addComment("Your request has been declined." . $data['refund']['declined_reason']);

                                                $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
                                                $creditMemo->setData('do_transaction', 0);

                                                $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
                                                $creditMemo->setAdjustment($creditMemo->getGrandTotal());
                                                $creditMemo->setBaseAdjustment($creditMemo->getGrandTotal());


                                                $order = $creditMemo->getOrder();
                                                $orderItems = $order->getData('items');

                                                foreach ($orderItems as $key => $value) {


                                                    $dataArray = $value->getData();


                                                    $dataArray['amount_refunded'] = $dataArray['qty_refunded'] * $dataArray['price'];
                                                    $dataArray['base_amount_refunded'] = $dataArray['qty_refunded'] * $dataArray['base_price'];


                                                    $value->setData($dataArray);
                                                    $orderItems[$key] = $value;

                                                }

                                                $order->setData('items', $orderItems);


                                                $order->setState($refundStatus['oa']);
                                                $order->setStatus($refundStatus['ou']);


                                                $order->setTotalRefunded($total_refunded);
                                                $order->setTotalOnlineRefunded($totalOnlineRefunded);
                                                $order->setBaseShippingRefunded($shippingAmountRefunded);
                                                $order->setShippingRefunded($shippingAmountRefunded);
                                                $order->setBaseTotalOnlineRefunded($totalOnlineRefunded);
                                                $order->setBaseTotalRefunded($total_refunded);
                                                $order->addCommentToStatusHistory(__('The Requested amount Have Been Declined for Transaction ID.' . $merchant_refund_reference));


                                                $creditMemoItemInterface = $creditMemo->getItems();


                                                foreach ($creditMemo as $key => $value) {
                                                    $creditMemo[$key];
                                                }


                                                foreach ($creditMemoItemInterface as $key => $value) {
                                                    $data = $value->getData();

                                                    $creditMemoItemInterface[$key]->setQty(0);

                                                }
                                                $creditMemo->setItems($creditMemoItemInterface);


                                                $pageMessage = 'Refund Declined';
                                                break;


                                            }
                                        }


                                        $creditMemo->save();
                                        $creditMemo->clearInstance();
                                        $creditMemo->cleanModelCache();
                                        $order->save();
                                        $payment->save();
                                        break;


                                    }
                                }


                            }

                        }
                    }
//                    $order->save();
//                    $payment->save();


                }


            } catch (\Exception $exception) {
                // //  $this->_zLogger->critical($exception->getMessage());
            }
        }

        return $result->setData($pageMessage);
    }


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
