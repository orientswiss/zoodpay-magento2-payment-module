<?php

namespace OrientSwiss\ZoodPay\Webapi\Rest\Request\Deserializer;

use Magento\Framework\App\State;
use Magento\Framework\Phrase;

class wwwFormUrlencodedX implements \Magento\Framework\Webapi\Rest\Request\DeserializerInterface
{

    /** @var \OrientSwiss\ZoodPay\Model\PostNotification\Decoder */
    protected $_decoder;

    /**
     * @var State
     */
    protected $_appState;

    /**
     * @param \OrientSwiss\ZoodPay\Model\PostNotification\Decoder $decoder
     * @param \Magento\Framework\App\State $stateApp
     */
    public function __construct(\OrientSwiss\ZoodPay\Model\PostNotification\Decoder $decoder, State $stateApp)
    {
        $this->_decoder = $decoder;
        $this->_appState = $stateApp;
    }

    /**
     * Parse Request body into array of params.
     *
     * @param string $encodedContent content from request.
     * @return array|null Return NULL in case of invalid Data.
     * @throws \InvalidArgumentException
     * @throws \Magento\Framework\Webapi\Exception If error was encountered.
     */
    public function deserialize($encodedContent)
    {
        if (!is_string($encodedContent)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" type is invalid. Need to Provide String.', gettype($encodedContent))
            );
        }
        try {

            $decodedContent = $this->_decoder->decode($encodedContent);
        } catch (\Zend_Json_Exception $e) {
            if ($this->_appState->getMode() !== State::MODE_DEVELOPER) {
                throw new \Magento\Framework\Webapi\Exception(new Phrase('Error in Decoding the content.'));
            } else {
                throw new \Magento\Framework\Webapi\Exception(
                    new Phrase(
                        'Error in Decoding the Content : %1%2%3%4',
                        [PHP_EOL, $e->getMessage(), PHP_EOL, $e->getTraceAsString()]
                    )
                );
            }
        }
        return $decodedContent;
    }
}
