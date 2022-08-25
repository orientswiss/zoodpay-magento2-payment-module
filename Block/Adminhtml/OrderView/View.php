<?php

namespace OrientSwiss\ZoodPay\Block\Adminhtml\OrderView;


use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as priceHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger;
use OrientSwiss\ZoodPay\Model\PaymentMethod;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;

/**
 * Order custom tab
 *
 */
class View extends \Magento\Backend\Block\Template
{
    protected $_template = 'view/view_order_info.phtml';
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $_orderRepository;
    /**
     * @var Zlogger
     */
    private $_zLogger;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;
    /**
     * @var priceHelper
     */
    private $_priceHelper;


    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Model\OrderRepository    $orderRepository,
        zDataHelper                             $zDataHelper,
        Zlogger                                 $zLogger,
        priceHelper                             $priceHelper,
        array                                   $data = []
    )
    {

        parent::__construct($context, $data);
        $this->_orderRepository = $orderRepository;

        $this->_zLogger = $zLogger;
        $this->_zDataHelper = $zDataHelper;
        $this->_priceHelper = $priceHelper;
    }

    public function getContent()
    {
        $order = null;
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->_orderRepository->get($orderId);
            $payment = $order->getPayment();

            if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {
                $totalAmount = $order->getGrandTotal();
                $additionalData = json_decode($payment->getAdditionalData(), true);

                $serviceSelected = $additionalData['selected_service']['service_code'];
                $fetchConfigResponse = $this->_zDataHelper->getZoodPayConfigurationArrayFormat();


                for ($i = 0, $iMax = count($fetchConfigResponse); $i < $iMax; $i++) {
                    //  echo $serviceSelected." == ".$fetchConfigResponse[$i]['service_code'];
                    if ($serviceSelected == $fetchConfigResponse[$i]['service_code']) {
                        $serviceName = $fetchConfigResponse[$i]['service_name'];
                        $serviceCode = $fetchConfigResponse[$i]['service_code'];
                        if (isset($fetchConfigResponse[$i]['instalments'])) {

                            $monthlyPayment = $totalAmount / $fetchConfigResponse[$i]['instalments'];
                            $monthlyPayment = $this->_priceHelper->currency($monthlyPayment, true, false); //Return thr Value with Currency Symbol
                            return [
                                "service_code" => $serviceCode,
                                "service_monthly_text" => $fetchConfigResponse[$i]['instalments'] . " " . __('MONTHLY') . " $serviceName of $monthlyPayment"
                            ];
                        } else {

                            return [
                                "service_code" => $serviceCode,
                                "service_monthly_text" => "$serviceName ($serviceCode)"
                            ];

                        }

                    }


                }

            }
        } catch (InputException $e) {
        } catch (NoSuchEntityException $e) {
        }


        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
