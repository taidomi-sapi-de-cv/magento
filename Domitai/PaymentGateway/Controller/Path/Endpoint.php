<?php 

namespace Domitai\PaymentGateway\Controller\Path;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Action;

class Endpoint extends Action{
    public function execute(){
        
        print_r(json_encode(array("response" => array("code" => 200))));
    }
}