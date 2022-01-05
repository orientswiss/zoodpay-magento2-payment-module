<?php

namespace OrientSwiss\ZoodPay\Observer;


use Magento\Framework\Event\Observer;

use Magento\Framework\Exception\LocalizedException;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

class creditMemoOrderPaymentRefund implements \Magento\Framework\Event\ObserverInterface
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

        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();

        /** @var Order $order */
        $order = $creditMemo->getOrder();
        /** @var Payment $payment */
        $payment = $order->getPayment();
        try {
            //
            if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {
                //  $this->_zLogger->info("CreditMemo Sale Order Payment Event Occurred");
                $orderID = $order->getEntityId();
                $checkCreditMemos = $order->hasCreditmemos();


                $creditMemoCollection = $order->getCreditmemosCollection();
                $creditMemoCollectionItems = $creditMemoCollection->getItems();
                $comemntCollection = $this->_commentCollectionFactory->create();

                $additionalData = json_decode($payment->getAdditionalData(), true);


                $creditMemoData = $creditMemo->getData();

                $creditMemoItem = $creditMemo->getItems();

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

                if (isset($additionalData['refund']['status'])) {


                    if ($additionalData['refund']['status'] != "Approved" || $additionalData['refund']['status'] != "Declined") {

                        $apiUrlEnding = $this->_zDataHelper->GetConfigData(zDataHelper::XML_API_Ver) . zDataHelper::API_RefundTransaction . "/" . $additionalData['refund']['refund_id'];


                        /** @var zDataHelper $curlResponse
                         *      Sent Post with Payload to the Zoodpay API And receive back the response
                         *      Do Not Delete
                         */
                        $curlResponse = $this->_zDataHelper->curlGet($apiUrlEnding, true);

                        if (isset($curlResponse)) {
                            if ($curlResponse['statusCode'] == 200) {

                                $curlResponseJson = json_decode($curlResponse['response'], true);

                                switch ($curlResponseJson['refund']['status']) {

                                    case 'Approved' :
                                    {
                                        $temp = array_pop($additionalData);
                                        $additionalData["refund"] = ["refund_id" => $curlResponseJson['refund_id'], "status" => $curlResponseJson['refund']['status']];

                                        $payment->setAdditionalData(json_encode($additionalData));

                                        $creditMemo->addComment("The Amount refunded.");

                                        $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);
                                        $creditMemo->setData('do_transaction', 0);

                                        $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_REFUNDED);

                                        $creditMemo->setGrandTotal($curlResponseJson['refund']['refund_amount']);
                                        $creditMemo->setSubtotal($curlResponseJson['refund']['refund_amount']);
                                        $creditMemo->setBaseGrandTotal($curlResponseJson['refund']['refund_amount']);
                                        $creditMemo->setBaseSubtotalInclTax($curlResponseJson['refund']['refund_amount']);
                                        $creditMemo->setSubtotalInclTax($curlResponseJson['refund']['refund_amount']);

                                        $order->setState(Order::STATE_CLOSED);
                                        $order->setStatus(Order::STATE_CLOSED);
                                        $order->setTotalRefunded($curlResponseJson['refund']['refund_amount']);
                                        break;

                                    }
                                    case 'Initiated' :
                                    {

                                        if (isset($additionalData["refund"]["refund_id"])) {
                                            $creditMemo->addComment("The decision is still under process.");
                                        } else {
                                            $additionalData["refund"] = ["refund_id" => $curlResponseJson['refund_id'], "status" => $curlResponseJson['refund']['status']];
                                            $payment->setAdditionalData(json_encode($additionalData));
                                            $creditMemo->addComment("The request have been sent to ZoodPay, And the Decision will be updated Automatically");
                                        }


                                        $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                                        $creditMemo->setData('do_transaction', 0);

                                        $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                                        $creditMemo->setAdjustment($creditMemo->getGrandTotal());
                                        $creditMemo->setBaseAdjustment($creditMemo->getGrandTotal());
                                        $creditMemo->setGrandTotal(0);
                                        $creditMemo->setSubtotal(0);
                                        $creditMemo->setBaseGrandTotal(0);
                                        $creditMemo->setBaseSubtotalInclTax(0);
                                        $creditMemo->setSubtotalInclTax(0);

                                        $order->setState(Order::STATE_HOLDED);
                                        $order->setStatus(Order::STATE_HOLDED);
                                        $order->setTotalRefunded(0);
                                        break;

                                    }
                                    case 'Declined' :
                                    {

                                        $temp = array_pop($additionalData);
                                        $additionalData["refund"] = ["refund_id" => $curlResponseJson['refund_id'], "status" => $curlResponseJson['refund']['status']];

                                        $payment->setAdditionalData(json_encode($additionalData));

                                        $creditMemo->addComment("Your request has been declined.");

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

                                        $order->setState(Order::STATE_CLOSED);
                                        $order->setStatus(Order::STATE_CLOSED);
                                        $order->setTotalRefunded(0);
                                        break;

                                    }
                                }


                            }
                        } else {
                            $creditMemo->addComment("There was problem while processing your request, Kindly Contact ZoodPay");

                            $creditMemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                            $creditMemo->setData('do_transaction', 0);

                            $creditMemo->setCreditmemoStatus(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                            $creditMemo->setAdjustment($creditMemo->getGrandTotal());
                            $creditMemo->setBaseAdjustment($creditMemo->getGrandTotal());
                            $creditMemo->setGrandTotal(0);
                            $creditMemo->setSubtotal(0);
                            $creditMemo->setBaseGrandTotal(0);
                            $creditMemo->setBaseSubtotalInclTax(0);
                            $creditMemo->setSubtotalInclTax(0);

                            $order->setState(Order::STATE_CLOSED);
                            $order->setStatus(Order::STATE_CLOSED);
                            $order->setTotalRefunded(0);

                        }
                    }


                }


                $order->save();
                $payment->save();

            }
        } catch (LocalizedException $exception) {
            //  $this->_zLogger->critical($exception->getMessage());
        } catch (\Exception $e) {
            //  $this->_zLogger->critical($e->getMessage());
        }
    }
}
