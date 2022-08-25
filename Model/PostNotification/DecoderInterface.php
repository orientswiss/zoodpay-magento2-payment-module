<?php
namespace OrientSwiss\ZoodPay\Model\PostNotification;


interface DecoderInterface
{
    /**
     * Decodes the $data of the x-www-form-urlencoded format into a PHP  (array, string literal, etc.)
     *
     * @param string $data
     * @return mixed
     */
    public function decode($data);
}
