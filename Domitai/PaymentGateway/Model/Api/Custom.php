<?php
 
namespace Domitai\PaymentGateway\Model\Api;
 
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;

class Custom
{
    /* 
        ===== STATUS DE PAGO QUE SE UTILIZAN EN ESTE PLUGIN ======
        STATE_PENDING_PAYMENT:Pending Payment
        STATE_CANCELED:canceled
        STATE_COMPLETE:complete
        STATE_PROCESSING:processing
    */
    protected $logger;
    protected $orderRepository;
    protected $orderCollectionFactory;
 
    public function __construct(
        LoggerInterface $logger
    )
    {
      $this->logger = $logger;
    }
 
    /**
     * @inheritdoc
     */
 
    public function getPost($value)
    {
        $response = ['success' => false];
        try {
            // Your Code here
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($value);
            
            if(count($order->getData()) > 0){
                $orderInformation = $order->getData();
                if($orderInformation['status'] == 'processing'){
                    $testnet = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/domitaigateway/testnet');
                    $pointSale = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/domitaigateway/domitai_store');
                    $total = floatval($order->getGrandTotal());
                    $postFields = array(
                        "slug" => $pointSale,
                        "currency" => $testnet == 1?"MXNt":'MXN',
                        "amount" => $total,
                        "customer_data" => array(
                            "first_name" => $orderInformation['customer_firstname'],
                            "last_name" => $orderInformation['customer_lastname'],
                            "email" => $orderInformation['customer_email'],
                            "orderid" => $value
                        ),
                        "generateQR" => true);
                    $postFieldsString = json_encode($postFields);
        
                    $ch = curl_init();
                    curl_setopt($ch,CURLOPT_URL,"https://domitai.com/api/pos");
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFieldsString);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch,CURLOPT_HTTPHEADER,array(
                        "Content-Type: application/json"
                    ));
                    $responseR = curl_exec($ch);
                    curl_close($ch);
                    $todo = json_decode($responseR,true);
                    $qr = $todo['payload']['accepted'];
                    if($qr) $response = ['success' => true,"codes" => $qr,"status"=>"processing"];
                }else{
                    $response = ["success" => true,"status" => $orderInformation['status'],"codes" => array()];
                }
            }else $response = ['success' => false, 'message' => "correct petition","codes"  => array()];
        
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage(),"codes" => array(),"code" => 500];
            $this->logger->info($e->getMessage());
        }
        
        $returnArray = json_encode($response);
        print_r($returnArray); 
        exit();
    }

    public function getStatus($value)
    {
        $response = ['success' => false,"message" => "works"];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($value);
        try {
           if(count($order->getData()) > 0){
             $orderArray = $order->getData();
             $response = ['success' => false,"status" => $orderArray['status'],"code" => 200];
           }else{
            $response = ['success' => false,"message" => "Order not found","code"  => 404];
           }
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage(),"code" => 500];
            $this->logger->info($e->getMessage());
        }
        
        $returnArray = json_encode($response);
        print_r($returnArray); 
        exit();
    }

    public function setPayment(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $data = file_get_contents('php://input');
        $data = json_decode($data,true);
        if($data and $data['customer_data']['orderid'] ){
            $orderid = $data['customer_data']['orderid'];
            $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderid);
            $arrayOrder = $order->getData();
            if(count($arrayOrder) > 0){
                if($arrayOrder['status'] == "canceled" or $arrayOrder['status'] == "complete"){
                    $messageStatus = $order['status'] == "canceled"?'cancelada':'completada';
                    print_r(json_encode(array("message" =>"Esta orden esta ".$messageStatus,"code" => 200)));
                    exit();
                }else{
                    if($data['status'] == "payment_received") $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
                    elseif($data['status'] == "payment_confirmed") $order->setState(Order::STATE_COMPLETE)->setStatus(Order::STATE_COMPLETE);
                    else $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
                    $order->save();    
                    print(json_encode(array("message" => "Success","code" => 200)));
                    exit();
                }
            }else{
                print(json_encode(array("message" => "Order not found","code" => 404)));
                exit();
            }
        }else{
            print_r(json_encode(array("message" => "Error al guardar","code" => 403)));
        } 
        exit();
    }
}