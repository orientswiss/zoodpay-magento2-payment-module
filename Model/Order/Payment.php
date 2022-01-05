<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OrientSwiss\ZoodPay\Model\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Info;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Sales\Api\CreditmemoManagementInterface as CreditmemoManager;

/**
 * Order payment information
 *
 * @api
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Payment extends Order\Payment
{
    public function refund($creditmemo)
    {
        $invoice = $creditmemo->getInvoice();

        if ($invoice) {
            $baseAmountToRefund = $this->formatAmount($creditmemo->getBaseGrandTotal());
            $gateway = $this->getMethodInstance();
            if ($gateway->canRefund()) {
                $this->setCreditmemo($creditmemo);
                if ($creditmemo->getDoTransaction()) {

                    $this->_eventManager->dispatch(
                        'sales_order_payment_refund',
                        ['payment' => $this, 'creditmemo' => $creditmemo]
                    );
                }

            }


        }
        else {


            $baseAmountToRefund = $this->formatAmount($creditmemo->getBaseGrandTotal());
            $this->setTransactionId(
                $this->transactionManager->generateTransactionId($this, Transaction::TYPE_REFUND)
            );

            $isOnline = false;
            $gateway = $this->getMethodInstance();
            $invoice = null;
            if ($gateway->canRefund()) {
                $this->setCreditmemo($creditmemo);
                if ($creditmemo->getDoTransaction()) {
                    $invoice = $creditmemo->getInvoice();
                    if ($invoice) {
                        $isOnline = true;
                        $captureTxn = $this->transactionRepository->getByTransactionId(
                            $invoice->getTransactionId(),
                            $this->getId(),
                            $this->getOrder()->getId()
                        );
                        if ($captureTxn) {
                            $this->setTransactionIdsForRefund($captureTxn);
                        }
                        $this->setShouldCloseParentTransaction(true);
                        // TODO: implement multiple refunds per capture
                        try {
                            $gateway->setStore(
                                $this->getOrder()->getStoreId()
                            );
                            $this->setRefundTransactionId($invoice->getTransactionId());
                            $gateway->refund($this, $baseAmountToRefund);

                            $creditmemo->setTransactionId($this->getLastTransId());
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            if (!$captureTxn) {
                                throw new \Magento\Framework\Exception\LocalizedException(
                                    __('If the invoice was created offline, try creating an offline credit memo.'),
                                    $e
                                );
                            }
                            throw $e;
                        }
                    }
                } elseif ($gateway->isOffline()) {
                    $gateway->setStore(
                        $this->getOrder()->getStoreId()
                    );
                    $gateway->refund($this, $baseAmountToRefund);
                }
            }

            // update self totals from creditmemo
            $this->_updateTotals(
                [
                    'amount_refunded' => $creditmemo->getGrandTotal(),
                    'base_amount_refunded' => $baseAmountToRefund,
                    'base_amount_refunded_online' => $isOnline ? $baseAmountToRefund : null,
                    'shipping_refunded' => $creditmemo->getShippingAmount(),
                    'base_shipping_refunded' => $creditmemo->getBaseShippingAmount(),
                ]
            );

            // update transactions and order state
            $transaction = $this->addTransaction(
                Transaction::TYPE_REFUND,
                $creditmemo,
                $isOnline
            );
            if ($invoice) {
                $message = __('We refunded %1 online.', $this->formatPrice($baseAmountToRefund));
            } else {
                $message = $this->hasMessage() ? $this->getMessage() : __(
                    'We refunded %1 offline.',
                    $this->formatPrice($baseAmountToRefund)
                );
            }
            $message = $message = $this->prependMessage($message);
            $message = $this->_appendTransactionToMessage($transaction, $message);
            $orderState = $this->getOrderStateResolver()->getStateForOrder($this->getOrder());
            $statuses = $this->getOrder()->getConfig()->getStateStatuses($orderState, false);
            $status = in_array($this->getOrder()->getStatus(), $statuses, true)
                ? $this->getOrder()->getStatus()
                : $this->getOrder()->getConfig()->getStateDefaultStatus($orderState);
            $this->getOrder()
                ->addStatusHistoryComment(
                    $message,
                    $status
                )->setIsCustomerNotified($creditmemo->getOrder()->getCustomerNoteNotify());
            $this->_eventManager->dispatch(
                'sales_order_payment_refund',
                ['payment' => $this, 'creditmemo' => $creditmemo]
            );
        }
        return $this;
    }
}
