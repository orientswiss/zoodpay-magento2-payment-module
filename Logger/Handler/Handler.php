<?php

/**
 * @category    OrientSwiss
 * @package     OrientSwiss_ZoodPay
 * @copyright Copyright © 2020 OrientSwiss ZoodPay. All rights reserved.
 * @author    mohammadali.namazi@zoodpay.com
 */


namespace OrientSwiss\ZoodPay\Logger\Handler;


class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;


    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
                                                      $filePath = null,
                                                      $fileName = null
    )
    {
        $fileName = '/var/log/zoodpay-' . date('Y-m-d') . '.log';
        parent::__construct($filesystem, $filePath, $fileName);
    }
}
