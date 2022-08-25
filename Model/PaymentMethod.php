<?php

namespace OrientSwiss\ZoodPay\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Payment\Model\Method\AbstractMethod as AbstractMethodAlias;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger;
use u2flib_server\Error;

/**
 * Pay In Store payment method model
 */
class PaymentMethod extends AbstractMethodAlias
{
    /**
     * Payment code
     *
     * @var string
     */
    const CODE = 'zoodpayment';
    protected $_code = self::CODE;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canAuthorize = true;

    protected $_zLogger = null;
    protected $_merchant_token = null;
    protected $_api_url = null;
    protected $_supportedCurrencyCodes = ["USD", "KWD", "KZT", "IQD", "JOD", "SAR", "UZS","LBP"];
    protected $_customerSession;
    /**
     * @var zDataHelper
     */
    protected $_zDataHelper;
    protected $resultRedirect;
    private $_orderRepository;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $_resultRedirectFactory;
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $_cookieManager;
    private $_cookieMetadataFactory;

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory            $customAttributeFactory,
        \Magento\Payment\Helper\Data                            $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig,
        Logger                                                  $logger,
        \Magento\Store\Model\StoreManagerInterface              $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        \OrientSwiss\ZoodPay\Logger\Zlogger                     $zLogger,
        \Magento\Sales\Api\OrderRepositoryInterface             $orderRepository,
        \Magento\Customer\Model\Session                         $customerSession,
        zDataHelper                                             $zDataHelper,
        \Magento\Framework\Controller\ResultFactory             $ResultFactory,
        \Magento\Framework\Controller\Result\RedirectFactory    $resultRedirectFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface        $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory  $cookieMetadataFactory,
        array                                                   $data = [],
        DirectoryHelper                                         $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        $this->_zLogger = $zLogger;
        $this->_orderRepository = $orderRepository;
        $this->_customerSession = $customerSession;
        $this->_zDataHelper = $zDataHelper;
        $this->resultRedirect = $ResultFactory;
        $this->_storeManager = $storeManager;
        $this->_resultRedirectFactory = $resultRedirectFactory;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param  $amount
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_customerSession->setCallBackURL("");
        //  $this->_zLogger->info('Able to authorize');
        // $storeScope = ScopeInterface::SCOPE_STORE;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();

        $lpayment = $order->getPayment();

        return $this->_placeOrder($payment);
    }

    /**
     * Availability for currency
     *
     * @param  $currencyCode
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!(in_array($currencyCode, $this->_supportedCurrencyCodes))) {

            //  $this->_zLogger->notice('Not Available to Operate Due to Chosen Currency: '.$this->_storeManager->getStore()->getCurrentCurrencyCode() );
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Framework\DataObject $payment
     */
    private function _placeOrder(\Magento\Framework\DataObject $payment)
    {

        //  $this->_zLogger->info('inside Place an Order');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();

        $this->_customerSession->setEntityID($order->getRealOrderId());

        try {
            $orderState = Order::STATE_NEW;
            $order->setState($orderState);
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $order->save();
        } catch (\Exception $e) {
            //  $this->_zLogger->error( $e->getMessage());
        }
    }
}
