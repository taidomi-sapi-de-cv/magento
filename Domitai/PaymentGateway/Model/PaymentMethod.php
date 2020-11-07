<?php
 namespace Domitai\PaymentGateway\Model;
  
 /**
  * Pay In Store payment method model
  */
 class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
 {
     /**
      * Payment code
      *
      * @var string
      */
     protected $_code = 'domitaigateway';
     protected $_isOffline = true;

    protected $_canAuthorize = true;
    protected $_canCapture = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }

    public function validate(){
        parent::validate();
        
        // This returns Mage_Sales_Model_Quote_Payment, or the Mage_Sales_Model_Order_Payment
        $info = $this->getInfoInstance();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencysymbol = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $currency = $currencysymbol->getStore()->getCurrentCurrencyCode();
        $pointSale = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/domitaigateway/domitai_store');
        
        if($currency != "MXN") throw new \Exception("error_currency");
        if($pointSale == "") throw new \Exception(__('error_store'));
        
        $no = $info->getCheckNo();
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            //check if payment has been authorized
            if(is_null($payment->getParentTransactionId())) {
                $this->authorize($payment, $amount);
            }
            
            //build array of payment data for API request.
            $request = [
                'capture_amount' => $amount,
                //any other fields, api key, etc.
            ];

            //make API request to credit card processor.
            $response = $this->makeCaptureRequest($request);

            //todo handle response
            $data = file_get_contents('php://input');
            $data = json_decode($data,true);    
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $saleInformation = array("id" => $payment->getOrder()->getIncrementId());    
            $objectManager->get('Domitai\PaymentGateway\Cookie\Cookie')->set(json_encode($saleInformation), 3600);
            $payment->setIsTransactionClosed(1);
        } catch (\Exception $e) {
            echo "error";
            $this->debug($payment->getData(), $e->getMessage());
        }

        return $this;
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {

            ///build array of payment data for API request.
            $request = [
                'cc_type' => $payment->getCcType(),
                'cc_exp_month' => $payment->getCcExpMonth(),
                'cc_exp_year' => $payment->getCcExpYear(),
                'cc_number' => $payment->getCcNumberEnc(),
                'amount' => $amount
            ];

            //check if payment has been authorized
            $response = $this->makeAuthRequest($request);
        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }

        if(isset($response['transactionID'])) {
            // Successful auth request.
            // Set the transaction id on the payment so the capture request knows auth has happened.
            $payment->setTransactionId($response['transactionID']);
            $payment->setParentTransactionId($response['transactionID']);
        }

        //processing is not done yet.
        $payment->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Set the payment action to authorize_and_capture
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        
        return self::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Test method to handle an API call for authorization request.
     *
     * @param $request
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeAuthRequest($request)
    {
        $response = ['transactionId' => 123]; //todo implement API call for auth request.
        if(!$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed auth request.'));
        }

        return $response;
    }

    /**
     * Test method to handle an API call for capture request.
     *
     * @param $request
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeCaptureRequest($request)
    {
        $response = ['success']; //todo implement API call for capture request.

        if(!$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed capture request.'));
        }

        return $response;
    }
 }