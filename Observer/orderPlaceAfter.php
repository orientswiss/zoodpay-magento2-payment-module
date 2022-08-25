<?php


namespace OrientSwiss\ZoodPay\Observer;


use Magento\Checkout\Exception;
use Magento\Customer\Model\Customer;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Shipping;
use Magento\QuoteGraphQl\Model\Resolver\BillingAddress;
use Magento\Sales\Model\Order;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger as LoggerInterface;
use Magento\Sales\Model\OrderFactory;
use OrientSwiss\ZoodPay\Model\PaymentMethod;

use Magento\Framework\Controller\Result\RedirectFactory;
use function Foo\Bar\formatFunction;

class orderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{


    protected $_zLogger;

    protected $_orderFactory;
    protected $_redirect;
    protected $_responseFactory;
    protected $_resultFactory;
    protected $_url;
    protected $_response;
    protected $_actionFactory;
    private $_orderRepository;
    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $_cookieManager;


    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $_jsonHelper;
    /**
     * @var SessionManagerInterface
     */
    private $_sessionManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;
    /**
     * @var \Magento\Framework\App\Response\HttpFactory
     */
    private $_httpFactory;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $_requestInterface;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $_customerFactory;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $_customer;
    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $_cookieMetadataFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;
    /**
     * @var Order\Payment\Transaction\Builder
     */
    private $_transactionBuilder;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;


    public function __construct(
        LoggerInterface                                        $logger,
        \Magento\Checkout\Model\Session                        $checkoutSession,
        \Magento\Customer\Model\Session                        $customerSession,
        OrderFactory                                           $orderFactory,
        \Magento\Framework\App\Response\RedirectInterface      $redirect,
        \Magento\Framework\App\ResponseFactory                 $responseFactory,
        \Magento\Framework\App\Response\HttpFactory            $httpFactory,
        \Magento\Framework\UrlInterface                        $url,
        \Magento\Framework\App\ResponseInterface               $response,
        \Magento\Framework\Controller\ResultFactory            $resultFactory,
        \Magento\Sales\Model\OrderRepository                   $orderRepository,
        \Magento\Framework\App\ActionFactory                   $actionFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface       $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Json\Helper\Data                    $jsonHelper,
        SessionManagerInterface                                $sessionManager,
        \Magento\Framework\ObjectManagerInterface              $objectManager,
        \Magento\Framework\App\RequestInterface                $requestInterface,
        \Magento\Customer\Model\CustomerFactory                $customerFactory,
        \Magento\Customer\Model\Customer                       $customers,
        zDataHelper                                            $zDataHelper,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder,
        \Magento\Framework\Message\ManagerInterface            $messageManager,
        RedirectFactory                                        $redirectFactory


    )

    {

        $this->_zLogger = $logger;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_redirect = $redirect;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->_response = $response;
        $this->_resultFactory = $resultFactory;
        $this->_orderRepository = $orderRepository;
        $this->_actionFactory = $actionFactory;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_jsonHelper = $jsonHelper;
        $this->_sessionManager = $sessionManager;
        $this->_objectManager = $objectManager;
        $this->_httpFactory = $httpFactory;
        $this->_requestInterface = $requestInterface;
        $this->_customerFactory = $customerFactory;
        $this->_customer = $customers;
        $this->_zDataHelper = $zDataHelper;
        $this->_transactionBuilder = $transactionBuilder;
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
    }


    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\PaymentException
     */


    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $incrementID = $this->_checkoutSession->getLastRealOrder()->getIncrementId();
        $orderModel = $this->_orderFactory->create();
        $default_postcode=array(
            "SA"=>"6330",
            "KZ"=>"010017",
            "UZ"=>"100012",
            "IQ"=>"10011",
            "JO"=>"11183",
            "KW"=>"60000",
            "LB"=>"10650"
        );
        /** @var \Magento\Sales\Model\Order $order -- Get the Order From Observer */

        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();

        if ($payment->getMethodInstance()->getCode() == PaymentMethod::CODE) {

           // $this->_zLogger->info('inside After Place an order Increment id: ' . $order->getIncrementId());
            try {
              //  $this->_zLogger->info("API URL:" . $this->_customerSession->getCallBackURL());

//

                /**  Customer  $customerObj -- Customer Object from Customer ID */
                $customerObj = $this->_customer->load($order->getId());
                //$this->_zLogger->info("customerID:" . $order->getId());
                /** @var BillingAddress $billingAddress */

                $billingAddress = $order->getBillingAddress();

                /** @var Shipping $shippingAddress */
                $shippingAddress = $order->getShippingAddress();

                /** @var Quote $quoteObj */
                $quoteObj = $this->_checkoutSession->getQuote();

                /** @var  $selectedService -- Get Selected Service from Cache */
                $selectedServiceJson = $this->_cookieManager->getCookie('selected_service');
                $selectedServiceArray = json_decode($selectedServiceJson, true);
                $selectedService = $selectedServiceArray['service_code'];
                $selectedServiceType = $selectedServiceArray['service_type'];
                $additionalData = json_encode(['selected_service' => ['service_code' => $selectedService, 'service_type' => $selectedServiceType]]);
                /** @var Cart $cartItems -- Get All Item in Cart */
                $cartItems = $this->_checkoutSession->getQuote()->getAllVisibleItems();


                $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                $currencyCode = $storeManager->getStore()->getCurrentCurrencyCode();


                $totalDiscountAmount = 0;
                $totalShippingAmount = $quoteObj->getShippingAddress()->getShippingAmount();
                $totalTaxAmount = 0;


                foreach ($cartItems as $item) {
                    $totalDiscountAmount = $totalDiscountAmount + $item->getDiscountAmount();
                    $totalTaxAmount = $totalTaxAmount + $item->getTaxAmount();
                }


                /** @var  $Customer_Data --- Details for creating transaction */
                $Customer_Data = [
                    'customer_dob' => $customerObj->getDataByKey('dob'),
                    'customer_email' => $order->getCustomerEmail(),
                    'customer_phone' => $billingAddress->getTelephone(),
                    'first_name' => $order->getCustomerFirstname(),
                    'last_name' => $order->getCustomerFirstname()

                ];
                if(trim($shippingAddress->getPostcode()) != ''){
                    $shiping_postcode = $shippingAddress->getPostcode();
                }
                else {
                    $shiping_postcode = $default_postcode[$shippingAddress->getCountryId()];
                }

                if(trim($billingAddress->getPostcode()) != ''){
                    $billing_postcode = $billingAddress->getPostcode();
                }
                else {
                    $billing_postcode = $default_postcode[$$billingAddress->getCountryId()];
                }

                /** @var  $billing_Data -- Details for creating transaction */
                $billing_Data = [

                    'address_line1' => implode(',', array($billingAddress->getStreetFull(), $billingAddress->getRegion(), $billingAddress->getCity(), $billingAddress->getCountry())),
                    'address_line2' => 'null',
                    'city' => $billingAddress->getCity(),
                    'country_code' => $billingAddress->getCountryId(),
                    'name' => $billingAddress->getName(),
                    'phone_number' => $billingAddress->getTelephone(),
                    'state' => $billingAddress->getRegion(),
                    'zipcode' => $billing_postcode
                ];

                /** @var  $Shipping_Data -- Details for creating transaction */

                $Shipping_Data = [

                    'address_line1' => implode(',', array($shippingAddress->getStreetFull(), $shippingAddress->getRegion(), $shippingAddress->getCity(), $shippingAddress->getCountry())),
                    'address_line2' => 'null',
                    'city' => $shippingAddress->getCity(),
                    'country_code' => $shippingAddress->getCountryId(),
                    'name' => $shippingAddress->getName(),
                    'phone_number' => $shippingAddress->getTelephone(),
                    'state' => $shippingAddress->getRegion(),
                    'zipcode' => $shiping_postcode
                ];

                $shippingService = [

                    "name" => $quoteObj->getShippingAddress()->getShippingMethod(),
                    "priority" => "null",
                    "shipped_at" => "null",
                    "tracking" => "null"
                ];


                $merchant_key = $this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_ID);
                $merchant_reference_no = $order->getIncrementId();
                // $amount = number_format($quoteObj->getGrandTotal(), 2);
                $amount = $quoteObj->getGrandTotal();
                $market_code = $this->_zDataHelper->GetConfigData(zDataHelper::XML_Default_Country_Code);
                $salt = $this->_zDataHelper->decrypt($this->_zDataHelper->GetConfigData(zDataHelper::XML_MERCHANT_Salt));

                $orderString = implode("|", array($merchant_key, $merchant_reference_no, $amount, $currencyCode, $market_code, $salt));


                /** @var  $orderSignature -- Create Signature Based on ZoodPay API DOC */

                $orderSignature = hash('sha512', $orderString);


                /** @var  $order_Data -- Details for creating transaction */

                $order_Data = [

                    'amount' => $quoteObj->getGrandTotal(),
                    'currency' => $currencyCode,
                    'discount_amount' => $totalDiscountAmount,
                    'lang' => $this->_zDataHelper->getCurrentLocale(),
                    'market_code' => $this->_zDataHelper->GetConfigData(zDataHelper::XML_Default_Country_Code),
                    'merchant_reference_no' => $order->getIncrementId(),
                    'service_code' => $selectedService,
                    'shipping_amount' => $totalShippingAmount,
                    'signature' => $orderSignature,
                    'tax_amount' => $totalTaxAmount,

                ];


                /** @var  $orderItemData -- Details for creating transaction */
                $orderItemData = [];

                foreach ($cartItems as $item) {

                    $orderItemData[] =

                        [
                            'categories' => [[$item->getProductType()]],


                            'currency_code' => $currencyCode,
                            'discount_amount' => $item->getDiscountAmount(),
                            'name' => $item->getName(),
                            'price' => $item->getPrice(),
                            'quantity' => $item->getQty(),
                            'sku' => $item->getSku(),
                            'tax_amount' => $item->getTaxAmount()

                        ];
                }

                $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";

                /** @var  $callBacksUrl -- Details for CallBackUrl */
                $callBacksUrl  =[
                    "error_url" => $base_url."zoodpay/payment/Decline",
                    "success_url" => $base_url."zoodpay/payment/Success",
                    "ipn_url" => $base_url."zoodpay/payment/IPN",
                    "refund_url" => $base_url."zoodpay/payment/Refund",

                ];

                $data = [
                    "billing" => $billing_Data,
                    "customer" => $Customer_Data,
                    "items" => $orderItemData,
                    "order" => $order_Data,
                    "shipping" => $Shipping_Data,
                    "shipping_service" => $shippingService,
                    "callbacks" => $callBacksUrl
                ];

                /** @var TYPE_NAME $curlResponse
                 *      Sent Post with Payload to the Zoodpay API And receive back the response
                 *      Do Not Delete
                 */
                $curlResponse = $this->_zDataHelper->curlPost($data, zDataHelper::API_CreateTransaction);


                if (isset($curlResponse)) {
                    if ($curlResponse['statusCode'] == 201) {

                        $curlResponseJson = json_decode($curlResponse['response'], true);
                        $this->_customerSession->setCurlResponseJson($curlResponseJson);
                        $localString = implode("|", array($market_code, $currencyCode, $amount, $merchant_reference_no, $merchant_key, $curlResponseJson['transaction_id'], $salt));
                        $localSignature = hash('sha512', $localString);

                        if ($localSignature == $curlResponseJson['signature']) {

                            /*
                             *
                             * Setting the Call Back URL
                             *
                             */
                            $callBackUrl = $curlResponseJson['payment_url'];

                            $this->_customerSession->setCallBackURL($callBackUrl);


                            /**
                             *
                             * Changing the status of the order to Pending Payment
                             */
                            //  $this->_zLogger->info('Order Status ' . $order->getStatus());
                            $orderState = Order::STATE_PENDING_PAYMENT;
                            $order->setState($orderState);
                            $order->setStatus(Order::STATE_PENDING_PAYMENT);

                            $order->save();
                            $this->_orderRepository->save($order);
                            //  $this->_zLogger->info('Order Status ' . $order->getStatus() . ' order Id '.$order->getEntityId());

                            $payment = $order->getPayment();
                            $payment->setLastTransId($curlResponseJson['transaction_id']);
//                            $additionalData = json_encode(['selected_service' => ['service_code' => $selectedService, 'service_type' => $selectedServiceType]]);
                            $additionalData = json_encode(['selected_service' => ['service_code' => $selectedService, 'service_type' => $selectedServiceType], 'refund' => []]);

                            $payment->setAdditionalData($additionalData);

                            $trans = $this->_transactionBuilder;
                            $transaction = $trans->setPayment($payment)
                                ->setOrder($order)
                                ->setTransactionId($curlResponseJson['transaction_id'])
                                ->setFailSafe(true)
                                //build method creates the transaction and returns the object
                                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

                            $payment->addTransactionCommentsToOrder(
                                $transaction,
                                "Created "
                            );


                            $payment->setParentTransactionId(null);
                            $payment->save();
                            $order->save();


                        } else {

                            $message = "There is problem with Signature of your order";
                            throw new \Magento\Framework\Exception\PaymentException(__($message));

                            /**
                             *
                             * Changing the status of the order to Fraud
                             */


                        }


                    } else {

                        $message = "Something Went Wrong Contact Administrator";
                        if ($curlResponse['statusCode'] == 400) {
                            $errorResponse = json_decode($curlResponse['response'], true);
                            $message = $errorResponse['message'] . "<br>";
                            if (isset($errorResponse['details'])) {
                                for ($i = 0, $iMax = count($errorResponse['details']); $i < $iMax; $i++) {
                                    $message .= $errorResponse['details'][$i]['field'] . ": " . $errorResponse['details'][$i]['error'] . "<br>";
                                }
                            }


                        }

                        $order->cancel();
                        $order->save();
                        $this->_checkoutSession->restoreQuote();
                        $registry = $objectManager->get('Magento\Framework\Registry');
                        $registry->register('isSecureArea', 'true');
                        $order->delete();
                        $registry->unregister('isSecureArea');


                        throw new \Magento\Framework\Exception\PaymentException(__($message));

                        // throw new Exception(__('Not Valid Information Provided'));
                    }

                } else {
                    $order->cancel();
                    $order->save();
                    $this->_checkoutSession->restoreQuote();
                    $registry = $objectManager->get('Magento\Framework\Registry');
                    $registry->register('isSecureArea', 'true');
                    $order->delete();
                    $registry->unregister('isSecureArea');

                    $message = "Something Went Wrong Contact Administrator";
                    throw new \Magento\Framework\Exception\PaymentException(__($message));
                }


            } catch (Exception $e) {

                //  $this->_zLogger->error($e->getMessage());
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {


                throw new \Magento\Framework\Exception\PaymentException(__($e->getMessage()));
            }


            //  $this->_zLogger->info( $this->_cookieManager->getCookie('zoodpay_callback'));


        }

    }


}
