<?php


namespace OrientSwiss\ZoodPay\Controller\Checkout;


use Magento\Framework\App\ResponseInterface;

class redirectPage extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $_resultPageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**      *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->_customerSession = $customerSession;
    }
    /**
     * Blog Index, shows a list of recent blog posts.
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {

        $resultPage = $this->_resultPageFactory->create();
        $callBackUrl = $this->_customerSession->getCallBackURL();
        $this->_customerSession->setCallBackURL($this->_url->getBaseUrl());
        return $this->_redirect->redirect($this->getResponse(),$callBackUrl);
    }

}
