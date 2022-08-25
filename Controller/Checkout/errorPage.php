<?php


namespace OrientSwiss\ZoodPay\Controller\Checkout;


use Magento\Framework\App\ResponseInterface;

class errorPage extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $_resultPageFactory;


    /**      *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context      $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {

        parent::__construct($context);

        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * Blog Index, shows a list of recent blog posts.
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();

        $resultPage->getConfig()->getTitle()->prepend(__('ERROR_PAGE'));

        return $resultPage;


    }


}
