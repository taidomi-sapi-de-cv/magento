<?php 
/**
 * ExtendedRma Helper
 */
namespace Domitai\PaymentGateway\Helper;
 
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
 
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
 
    ) {
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        parent::__construct($context);
    }
    /*
    * create order programmatically
    */
    public function createOrder($orderInfo) {
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderInfo['email']);// load customet by email address
        if(!$customer->getId()){
            //For guest customer create new cusotmer
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($orderInfo['address']['firstname'])
                    ->setLastname($orderInfo['address']['lastname'])
                    ->setEmail($orderInfo['email'])
                    ->setPassword($orderInfo['email']);
            $customer->save();
        }
        $quote=$this->quote->create(); //Create object of quote
        $quote->setStore($store); //set store for our quote
        /* for registered customer */
        $customer= $this->customerRepository->getById($customer->getId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); //Assign quote to customer
 
        //add items in quote
        foreach($orderInfo['items'] as $item){
            $product=$this->productRepository->getById($item['product_id']);
            if(!empty($item['super_attribute']) ) {
                /* for configurable product */
                $buyRequest = new \Magento\Framework\DataObject($item);
                $quote->addProduct($product,$buyRequest);
            } else {
                /* for simple product */
                $quote->addProduct($product,intval($item['qty']));
            }
        }
 
        //Set Billing and shipping Address to quote
        $quote->getBillingAddress()->addData($orderInfo['address']);
        $quote->getShippingAddress()->addData($orderInfo['address']);
 
        // set shipping method
        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('flatrate_flatrate'); //shipping method, please verify flat rate shipping must be enable
        $quote->setPaymentMethod('checkmo'); //payment method, please verify checkmo must be enable from admin
        $quote->setInventoryProcessed(false); //decrease item stock equal to qty
        $quote->save(); //quote save 
        // Set Sales Order Payment, We have taken check/money order
        $quote->getPayment()->importData(['method' => 'checkmo']);
 
        // Collect Quote Totals & Save
        $quote->collectTotals()->save();
        // Create Order From Quote Object
        $order = $this->quoteManagement->submit($quote);
        /* for send order email to customer email id */
        $this->orderSender->send($order);
        /* get order real id from order */
        $orderId = $order->getIncrementId();
        if($orderId){
            $result['success']= $orderId;
        }else{
            $result=['error'=>true,'msg'=>'Error occurs for Order placed'];
        }
        return $result;
    }
}