<?php


namespace OrientSwiss\ZoodPay\Helper\Order;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\Order\Payment\Repository as PaymentResp;
use Magento\Sales\Model\Order as OrderModel;


class OrderTransactionHelperInterface
{


    /**
     * @var LoggerInterface
     */
    private $_zLogger;
    /**
     * @var TransactionRepositoryInterface
     */
    private $_transactionRepository;
    /**
     * @var TransactionInterface
     */
    private $_transactionInterface;
    /**
     * @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory
     */
    private $_transactions;
    /**
     * @var Repository
     */
    private $_orderPaymentTransactionResp;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $_filterBuilder;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection
     */
    private $_paymentCollection;
    /**
     * @var OrderModel
     */
    private $_orderModel;
    /**
     * @var PaymentResp
     */
    private $_paymentRepository;

    public function __construct(
        TransactionRepositoryInterface                                  $transactionRepository,
        TransactionInterface                                            $transactionInterface,
        \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions,
        LoggerInterface                                                 $logger,
        \Magento\Sales\Model\Order\Payment\Transaction\Repository       $orderPaymentTransactionResp,
        \Magento\Framework\Api\SearchCriteriaBuilder                    $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder                            $filterBuilder,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Collection     $paymentCollection,
        OrderModel                                                      $orderModel,
        PaymentResp                                                     $paymentRepository
    )
    {
        $this->_transactionRepository = $transactionRepository;
        $this->_zLogger = $logger;
        $this->_transactionInterface = $transactionInterface;
        $this->_transactions = $transactions;
        $this->_orderPaymentTransactionResp = $orderPaymentTransactionResp;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_paymentCollection = $paymentCollection;
        $this->_orderModel = $orderModel;
        $this->_paymentRepository = $paymentRepository;
    }


    /**
     * Loads a specified transaction by id
     *
     * @param  $txn_ID -- Transaction Number
     * @return TransactionInterface|null
     */
    public function getTransactionData($txn_ID)
    {
        $transactionData = null;
        try {

            /** @var  $transactionData -- Transaction Interface */
            $transactionData = $this->_transactionRepository->create();

            /** @var Filter[] $filter --- creating Filter Based on the provided the txn_id */
            $filter [] = $this->_filterBuilder->setField('txn_id')
                ->setValue($txn_ID)
                ->create();
            /** @var SearchCriteria $searchCriteria */
            $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filter)
                ->create();


            if (isset((array_keys(($this->_transactionRepository->getList($searchCriteria))->getItems()))[0])) {

                $transactionId = (array_keys(($this->_transactionRepository->getList($searchCriteria))->getItems()))[0];
                $transactionData = $this->_transactionRepository->get($transactionId);
            }


        } catch (NoSuchEntityException $exception) {
            //  $this->_zLogger->critical($exception->getMessage());
        }
        return $transactionData;
    }


    public function getOrderModel($orderID)
    {
        $orderData = null;
        try {
            $orderData = $this->_orderModel->loadByAttribute('entity_id', $orderID);

        } catch (NoSuchEntityException $exception) {
            //  $this->_zLogger->critical($exception->getMessage());
        }


        return $orderData;
    }

    public function getPaymentData($lastTransactionID)
    {
        $paymentData = null;


        try {

            /** @var  $transactionData -- Transaction Interface */
            $paymentData = $this->_paymentRepository->create();

            /** @var Filter[] $filter --- creating Filter Based on the provided the last_trans_id */
            $filter [] = $this->_filterBuilder->setField('last_trans_id')
                ->setValue($lastTransactionID)
                ->create();
            /** @var SearchCriteria $searchCriteria */
            $searchCriteria = $this->_searchCriteriaBuilder->addFilters($filter)
                ->create();


            if (isset((array_keys(($this->_paymentRepository->getList($searchCriteria))->getItems()))[0])) {

                $transactionId = (array_keys(($this->_paymentRepository->getList($searchCriteria))->getItems()))[0];
                $paymentData = $this->_paymentRepository->get($transactionId);
            }


        } catch (NoSuchEntityException $exception) {
            //  $this->_zLogger->critical($exception->getMessage());
        } catch (InputException $e) {
        }
        return $paymentData;


    }

}
