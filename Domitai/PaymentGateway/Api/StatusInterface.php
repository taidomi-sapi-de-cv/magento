<?php 
namespace Domitai\PaymentGateway\Api;
 
interface StatusInterface
{
    /**
     * GET for Post api
     * @param string $value
     * @return string
     */
 
    public function getStatus($value);
}