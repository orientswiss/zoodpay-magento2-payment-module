<?php


namespace OrientSwiss\ZoodPay\Block;

use Magento\Framework\App\State;
use Magento\Framework\View\Element\Template;
use OrientSwiss\ZoodPay\Helper\Data as zDataHelper;
use OrientSwiss\ZoodPay\Logger\Zlogger;

class SuccessPage extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;
    /**
     * @var Zlogger
     */
    private $_zLogger;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $_coreSession;
    /**
     * @var \Magento\Cms\Api\PageRepositoryInterface
     */
    private $pageRepository;
    /**
     * @var \Magento\Cms\Model\Page
     */
    private $_page;
    /**
     * @var zDataHelper
     */
    private $_zDataHelper;

    public function __construct(
        Template\Context                                   $context,
        \Magento\Customer\Model\Session                    $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\App\RequestInterface            $request,
        \Magento\Cms\Api\PageRepositoryInterface           $pageRepository,
        \Magento\Cms\Model\Page                            $page,
        Zlogger                                            $zLogger,
        zDataHelper                                        $zDataHelper,
        array                                              $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_customerSession = $customerSession;
        $this->_zLogger = $zLogger;
        $this->_zDataHelper = $zDataHelper;
        $this->_coreSession = $coreSession;
        $this->_request = $request; //only if not in a controller
        $this->pageRepository = $pageRepository;
        $this->_page = $page;
        $this->addData(array('cache_lifetime' => null));
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getCacheLifetime()
    {
        return null;
    }


    /**
     * @return \Magento\Framework\App\CacheInterface
     */
    public function getCache(): \Magento\Framework\App\CacheInterface
    {
        $this->_cache->clean();

        return $this->_cache;
    }

    public function getTheContent()
    {
        $this->_coreSession->start();
        $message = '';

        $this->_zDataHelper->flushPage();

        $message = $this->_coreSession->getPageMessage();
        if (is_null($message)) {
            $message = __("PROCESSING_ERROR");
        }

        // //  $this->_zLogger->info($message);


        return $message;
    }


}
