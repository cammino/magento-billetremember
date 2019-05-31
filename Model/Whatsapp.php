<?php
require __DIR__ . '/../lib/twilio/Twilio/autoload.php';
use Twilio\Rest\Client;

class Cammino_Billetremember_Model_Whatsapp
{
    public function sendMessage($payments) {

        header("Content-type: text/html; charset=utf-8");
        
        $helper = Mage::helper("billetremember");

        $sid = $helper->getTwilioAccountSid();
        $token = $helper->getTwilioAuthToken();
        $twilioWhatsapp = $helper->getTwilioWhatsappNumber();

        if($sid && $token && $twilioWhatsapp):
            $twilio = new Client($sid, $token);
            
            foreach($payments as $payment):

                try {
                    $orderId = $payment->getParentId();
                    $order = Mage::getModel("sales/order")->load($orderId);
                    $billetUrl = Mage::getModel("billetremember/job")->getBilletUrl($payment);
                    
                    $customerId = $order->getCustomerId();
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    
                    $customerName = $customer->getFirstname();
                    $cellphone = $customer->getPrimaryBillingAddress()->getTelephone();
                    $cellphone = $helper->formatWhatsappNumber($cellphone);
                    
                    $message = $helper->renderWhatsappBody($customerName, $billetUrl);
                    
                    $sent = $twilio->messages->create(
                        "whatsapp:" . $cellphone,
                        array(
                            "from" => "whatsapp:" . $twilioWhatsapp,
                            "body" => $message
                        )
                    );

                    if($sent->sid) {
                        $addata = unserialize($payment->getData("additional_data"));
                        $addata["billetremember"] = true;
                        $payment->setAdditionalData(serialize($addata))->save();
                    }
                    
                } catch (Exception $e) {
                    return false;
                }

            endforeach;
        endif;
    }
}