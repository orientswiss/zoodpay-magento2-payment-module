<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Checkout\Exception;
use Magento\Framework\Event\Observer;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Exception\CouldNotRefundException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;
use Magento\Sales\Model\Order\Item as OrderItem;

class creditMemoSaveBefore implements \Magento\Framework\Event\ObserverInterface
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
     * @var Payment\Transaction\Repository
     */
    private $_transactionRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $_filterBuilder;
    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    private $_creditmemoService;
    /**
     * @var Order\CreditmemoFactory
     */
    private $_creditmemoFactory;
    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $_creditmemoRepoInterface;
    /**
     * @var \Magento\Sales\Api\Data\CreditmemoItemInterface
     */
    private $_creditmemoItemInterface;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Comment\CollectionFactory
     */
    private $_commentCollectionFactory;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $_request;
    /**
     * @var Payment\Transaction\Builder
     */
    private $_transactionBuilder;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    public function __construct(
        LoggerInterface                                                               $zLogger,
        zDataHelper                                                                   $zDataHelper,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository                     $transactionRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder                                  $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder                                          $filterBuilder,
        \Magento\Sales\Model\Service\CreditmemoService                                $creditmemoService,
        \Magento\Sales\Model\Order\CreditmemoFactory                                  $creditmemoFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface                              $creditmemoRepoInterface,
        \Magento\Sales\Api\Data\CreditmemoItemInterface                               $creditmemoItemInterface,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Comment\CollectionFactory $commentCollectionFactory,
        \Magento\Framework\App\RequestInterface                                       $request,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder                        $transactionBuilder,
        \Magento\Framework\Message\ManagerInterface                                   $messageManager

    )
    {
        $this->_zLogger = $zLogger;
        $this->_zDataHelper = $zDataHelper;
        $this->_transactionRepository = $transactionRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_creditmemoService = $creditmemoService;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_creditmemoRepoInterface = $creditmemoRepoInterface;
        $this->_creditmemoItemInterface = $creditmemoItemInterface;
        $this->_commentCollectionFactory = $commentCollectionFactory;
        $this->_request = $request;
        $this->_transactionBuilder = $transactionBuilder;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws CouldNotSaveException
     */
    public function execute(Observer $observer)
    {


        try {


            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $creditmemoRequest = $this->_request->getParams();
            $date = $objectManager->get('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');


            if (isset($creditmemoRequest['creditmemo']['do_offline'])) {
                if ($creditmemoRequest['creditmemo']['do_offline'] != "1") {
                    /** @var CreditmemoInterface $creditMemo */
                    $creditMemo = $observer->getEvent()->getCreditmemo();

                    /** @var Order $order */
                    $order = $creditMemo->getOrder();
                    /** @var Payment $payment */
                    $payment = $order->getPayment();


                    //
                    if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {
                        //  $this->_zLogger->info("CreditMemo Save Event Occurred");
                        $orderID = $order->getEntityId();
                        $checkCreditMemos = $order->hasCreditmemos();

                        $paymentdata = $payment->getData();

                        unset($paymentdata['created_transaction']);
                        $payment->setData($paymentdata);


                        $creditMemoCollection = $order->getCreditmemosCollection();


                        $creditMemoCollectionItems = $creditMemoCollection->getItems();
                        $comemntCollection = $this->_commentCollectionFactory->create();

                        $additionalData = json_decode($payment->getAdditionalData(), true);


                        $creditMemoData = $creditMemo->getData();


                        $creditMemoItemInterface = $creditMemo->getItems();


                        $qty_refunded = [];
                        if (isset($additionalData["refund"][0]["refund_tr"])) {
                            $ppp = 0;

                            foreach ($additionalData["refund"] as $keyR => $valueR) {
                                $mmm = $valueR;
                                if ($valueR['refund_status'] == 1) {
                                    $qty_refunded[$keyR] = $valueR[0];

                                }

                            }
                        }


                        /** @var OrderItem $orderItem */
                        $orderItem = $objectManager->create(OrderItem::class);
                        $orderItem->setOrder($order);

                        $oo = $orderItem->getOrderId();

                        $orderItem->setQtyRefunded(0);

                        $orderItems = $order->getData('items');
                        $orderTemp = null;
                        foreach ($orderItems as $key => $value) {
                            $data = $value->getData();
                            $temp = $value;
                            $orderTemp[$key] = $data['qty_refunded'];
                            $data['qty_refunded'] = 0;
                            foreach ($qty_refunded as $keyRR => $valueRR) {
                                foreach ($valueRR as $keyM => $valueM) {


                                    if ($keyM == $key) {
                                        $data['qty_refunded'] += $valueM;

                                    }

                                }


                            }
                            //  $data['qty_refunded'] = 0;
                            $value->setData($data);
                            $orderItems[$key] = $value;

                        }

                        $order->setData('items', $orderItems);
                        $order->save();


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


                        /** @var TransactionInterface $transactionItems */
                        $transactionItems = $transactionList->getItems();


                        $defaultTransaction = $transactionData[0]['txn_id'] . '-refund';


                        $addRefund = $date->date()->format('is');
                        $merchant_refund_reference = $transactionData[0]['txn_id'] . $addRefund . '-refund';

                        $creditMemo->setTransactionId($merchant_refund_reference);


                        $data = [
                            "merchant_refund_reference" => $merchant_refund_reference,
                            "reason" => (empty($creditMemo->getCustomerNote())) ? "No reason added" : $creditMemo->getCustomerNote(),
                            "refund_amount" => $creditMemo->getBaseGrandTotal(),
                            "request_id" => $merchant_refund_reference,
                            "transaction_id" => $transactionData[0]['txn_id'],

                        ];


                        /** @var zDataHelper $curlResponse
                         *      Sent Post with Payload to the Zoodpay API And receive back the response
                         *      Do Not Delete
                         */
                        $curlResponse = $this->_zDataHelper->curlPost($data, zDataHelper::API_RefundTransaction);


                        $transactionList->removeAllItems();

                        if (isset($curlResponse)) {
                            if ($curlResponse['statusCode'] == 201) {

                                $curlResponseJson = json_decode($curlResponse['response'], true);

                                switch ($curlResponseJson['refund']['status']) {

                                    case 'Approved' :
                                    {


                                        $creditMemo->addComment("The Amount refunded.");

                                        $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
                                        $creditMemo->setData('do_transaction', 0);
                                        $creditMemo->setData('state', 1);

                                        $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);


                                        $order->setTotalRefunded($data['refund']['refund_amount']);
//                                        $order->setState(Order::STATE_HOLDED);
//                                        $order->setStatus(Order::STATE_HOLDED);
                                        $order->setTotalRefunded($curlResponseJson['refund']['refund_amount']);
                                        $order->addCommentToStatusHistory(__('The Requested amount Have Been Approved for Transaction ID.' . $merchant_refund_reference));


                                        break;
                                    }
                                    case 'Initiated' :
                                    {


                                        $creditMemo->setComments(null);


                                        if (isset($additionalData["refund"]["refund_tr"])) {
                                            $creditMemo->addComment("The decision is still under process.");
                                        } else {
                                            array_push($additionalData["refund"], ["refund_tr" => $curlResponseJson['refund']['merchant_refund_reference'], "refund_status" => 0, "refund_amount" => $creditMemo->getBaseGrandTotal(), 'sa' => $creditMemo->getShippingAmount(), 'os' => ['oa' => $order->getState(), 'ou' => $order->getStatus()], $orderTemp]);
                                            $payment->setAdditionalData(json_encode($additionalData));
                                            $creditMemo->addComment("The request have been sent to ZoodPay, And the Decision will be updated Automatically");
                                        }


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


                                        $creditMemo->setData('do_transaction', 0);

                                        $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                                        $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);


                                        $creditMemo->setTransactionId($merchant_refund_reference);


                                        $order->setState(Order::STATE_PAYMENT_REVIEW);
                                        $order->setStatus(Order::STATE_PAYMENT_REVIEW);
                                        $order->setTotalRefunded($total_refunded);
                                        $order->setTotalOnlineRefunded($totalOnlineRefunded);
                                        $order->setBaseShippingRefunded($shippingAmountRefunded);
                                        $order->setShippingRefunded($shippingAmountRefunded);
                                        $order->setBaseTotalOnlineRefunded($totalOnlineRefunded);
                                        $order->setBaseTotalRefunded($total_refunded);


//                                         $this->_transactionBuilder->reset();
                                        $trans = $this->_transactionBuilder;

                                        //$trans->reset();


                                        $transaction = $trans->setPayment($payment)
                                            ->setOrder($order)
                                            ->setTransactionId($merchant_refund_reference)
                                            ->setFailSafe(true)
                                            //build method creates the transaction and returns the object
                                            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND);


                                        $payment->addTransactionCommentsToOrder(
                                            $transaction,
                                            "Requested refund "
                                        );

                                        $payment->setParentTransactionId($curlResponseJson['refund']['transaction_id']);
                                        $payment->setIsTransactionPending(true);


                                        break;

                                    }
                                    case 'Declined' :
                                    {


                                        $creditMemo->addComment(' - The reason is : ' . $curlResponseJson['refund']['declined_reason']);

                                        $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
                                        $creditMemo->setData('do_transaction', 0);

                                        $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_CANCELED);
                                        $creditMemo->setAdjustment($creditMemo->getGrandTotal());
                                        $creditMemo->setBaseAdjustment($creditMemo->getGrandTotal());
                                        $creditMemo->setGrandTotal(0);
                                        $creditMemo->setSubtotal(0);
                                        $creditMemo->setBaseGrandTotal(0);
                                        $creditMemo->setBaseSubtotalInclTax(0);
                                        $creditMemo->setSubtotalInclTax(0);


                                        $creditMemoItemInterface = $creditMemo->getItems();


                                        foreach ($creditMemo as $key => $value) {
                                            $creditMemo[$key];
                                        }


                                        foreach ($creditMemoItemInterface as $key => $value) {
                                            $data = $value->getData();

                                            $creditMemoItemInterface[$key]->setQty(0);

                                        }
                                        $creditMemo->setItems($creditMemoItemInterface);


                                        $order->setTotalRefunded(0);
                                        $order->addCommentToStatusHistory(__('The Requested amount Have Been Declined for Transaction ID.' . $merchant_refund_reference));

                                        break;
                                    }
                                }


                            } else {


                                if ($curlResponse['statusCode'] == 400) {
                                    $errorResponse = json_decode($curlResponse['response'], true);
                                    $message = $errorResponse['message'] . "<br>";
                                    if (isset($errorResponse['details'])) {
                                        for ($i = 0, $iMax = count($errorResponse['details']); $i < $iMax; $i++) {
                                            $message .= $errorResponse['details'][$i]['field'] . ": " . $errorResponse['details'][$i]['error'] . "<br>";
                                        }
                                    }


                                }

                                throw new \Magento\Framework\Exception\CouldNotDeleteException(__('There was Problem in Your request : ' . $message));


                            }
                        } else {
                            throw new \Magento\Framework\Exception\CouldNotDeleteException(__("There was problem while processing your request, Kindly Contact ZoodPay"));


                        }


                        $order->Save();
                        $payment->save();

                    }
                }


            } else {
                $creditMemo = $observer->getEvent()->getCreditmemo();
                /** @var Order $order */
                $order = $creditMemo->getOrder();
                /** @var Payment $payment */
                $payment = $order->getPayment();


                if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {

                    $order->save();
                    $payment->save();
                }


            }


        } catch (Exception $e) {

            //  $this->_zLogger->error($e->getMessage());
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }


    }
}
