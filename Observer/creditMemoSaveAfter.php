<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Controller\Order\Creditmemo;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class creditMemoSaveAfter implements \Magento\Framework\Event\ObserverInterface
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
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Comment\CollectionFactory $commentCollectionFactory

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
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {


//
//        /** @var Creditmemo $creditMemo */
//        $creditMemo = $observer->getEvent()->getCreditmemo();
//
//        /** @var Order $order */
//        $order = $creditMemo->getOrder();
//        /** @var Payment $payment */
//        $payment = $order->getPayment();
//        try {
//            if($payment->getMethodInstance()->getCode() == PaymentMethod::CODE ){
//               //  $this->_zLogger->info("CreditMemo Save Event Occurred");
//                $orderID = $order->getEntityId();
//                $checkCreditMemos = $order->hasCreditmemos();
//
//                $creditMemoCollection = $order->getCreditmemosCollection();
//
//                $creditMemoCollectionItems = $creditMemoCollection->getItems();
//                $comemntCollection = $this->_commentCollectionFactory->create();
//
//
//
//                $creditMemoData = $creditMemoCollection->getData();
//                $comemntCollection->setCreditmemoFilter($creditMemoData[0]['entity_id']);
//
//                foreach ($comemntCollection as $item) {
//                    $item->setComment('hello');
//                }
//
//                $filters[] = $this->_filterBuilder->setField('payment_id')
//                    ->setValue($order->getPayment()->getId())
//                    ->create();
//
//                $filters[] = $this->_filterBuilder->setField('order_id')
//                    ->setValue($order->getId())
//                    ->create();
//
//                $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filters)
//                    ->create();
//
//                $transactionList = $this->_transactionRepository->getList($searchCriteria);
//                $transactionData = $transactionList->getData();
//
//
//                $data = [
//                    "merchant_refund_reference"=> $creditMemoData[0]['entity_id'],
//                    "reason"=> $creditMemoData[0]['customer_note'],
//                    "refund_amount"=> $creditMemoData[0]['base_grand_total'],
//                    "request_id"=>$creditMemoData[0]['transaction_id'] ?? $transactionData[0]['txn_id'].'-refund' ,
//                    "transaction_id"=> $transactionData[0]['txn_id'],
//
//                ];
//
//                /** @var zDataHelper $curlResponse
//                 *      Sent Post with Payload to the Zoodpay API And receive back the response
//                 *      Do Not Delete
//                 */
//                $curlResponse = $this->_zDataHelper->curlPost($data,zDataHelper::API_RefundTransaction);
//
//                if(isset($curlResponse)) {
//                    if ($curlResponse['statusCode'] == 201) {
//
//                        $curlResponseJson = json_decode($curlResponse['response'], true);
//
//
//
//                    }
//                }
//
//
//
//               //  $this->_zLogger->info("imhere");
//            }
//        } catch (LocalizedException $exception) {
//           //  $this->_zLogger->critical($exception->getMessage());
//        }


    }
}
