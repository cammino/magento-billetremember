<?php
class Cammino_Billetremember_Model_Job
{
    public function notify() {
        $payments = $this->getBilletOrders();

        foreach($payments as $payment) {
            $sent = $this->sendEmail($payment);
            if($sent){
                $addata = unserialize($payment->getData("additional_data"));
                $addata['billetremember'] = true;
                $payment->setAdditionalData(serialize($addata))->save();
            }
        }
    }

    private function getBilletOrders() {
        $payments = Mage::getModel('sales/order_payment')->getCollection();

        $hours = $this->getHours();

        $payments->getSelect()
            ->where('pagarme_boleto_url IS NOT NULL')
            ->where('DATE(pagarme_boleto_expiration_date) >= DATE(NOW())')
            ->where('DATE_ADD(pagarme_boleto_expiration_date, INTERVAL -'.$hours.' HOUR) < NOW()')
            ->where('((additional_data IS NULL) OR (additional_data NOT LIKE \'%billetremember%\'))');
        
        $payments->load();

        return $payments;
    }

    private function sendEmail($payment) {
        $orderId = $payment->getParentId();
        $order = $order = Mage::getModel("sales/order")->load($orderId);
        $storeId = $order->getStore()->getId();

        $customerName = $order->getCustomerFirstname();
        $customerEmail = $order->getCustomerEmail();
        $billetUrl = $this->getBilletUrl($payment);

        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        
        try{
            $emailInfo->addTo($customerEmail, $customerName);
            $mailer->addEmailInfo($emailInfo);
            $mailer->setSender(Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, $storeId));
            $mailer->setStoreId($storeId);
            $mailer->setTemplateId("billetremember_email");
            $mailer->setTemplateParams(array(
                'customerName' => $customerName,
                'billetUrl'   => $billetUrl
            ));

            $sent = $mailer->send();
            Mage::log($sent, null, 'billetremember.log');
            return true;

        }catch(Exception $e) {
            Mage::log($sent, null, 'billetremember.log');
            Mage::log($e->getMessage(), null, 'billetremember.log');
            return false;
        }
    }

    private function getHours() {
        return 24;
    }

    private function getBilletUrl($payment) {
        return $payment->getPagarmeBoletoUrl();
    }

}