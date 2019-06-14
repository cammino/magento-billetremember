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
                    
                    // try to get cellphone
                    $cellphone = $customer->getPrimaryBillingAddress()->getFax();
                    if (strlen($cellphone) < 8) {
                        // try to get telephone
                        $cellphone = $customer->getPrimaryBillingAddress()->getTelephone();
                        if(strlen($cellphone < 8)) {
                            $cellphone = false;
                        }
                    }

                    if($cellphone != false) {
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

                            if($helper->logIsActive()) {
                                $helper->log("Whatsapp enviado, cliente: " . $customerId . ", pedido: " . $orderId);
                                $helper->log("Mensagem: " . $message);
                            }

                        } else { 
                            $helper->log("Whatsapp não pode ser enviado, cliente: " . $customerId . ", pedido: " . $orderId);
                            $helper->log($sent);
                        }
                    } else {
                        $helper->log("Telefone inválido para enviar whatsapp para o cliente: " . $customerId . ", para o pedido: " . $orderId);
                    }
                    
                } catch (Exception $e) {
                    $helper->log($e->getMessage());
                    return false;
                }

            endforeach;
        endif;
    }
}