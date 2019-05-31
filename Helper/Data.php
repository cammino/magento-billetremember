<?php
class Cammino_Billetremember_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function moduleIsActive() {
        return (bool) Mage::getStoreConfig('sales_email/billetremember/active');
    }
    
    public function notifyByEmail() {
        return (bool) Mage::getStoreConfig('sales_email/billetremember/notify_by_email');
    }
    
    public function notifyByWhatsapp() {
        return (bool) Mage::getStoreConfig('sales_email/billetremember/notify_by_whatsapp');
    }

    public function logsIsActive() {
        return (bool) Mage::getStoreConfig('sales_email/billetremember/active_log');
    }

    public function getHours() {
        $hours = intval(Mage::getStoreConfig('sales_email/billetremember/hours'));
        return $hours < 1 ? 24 : $hours;
    }

    public function renderEmailSubject($customerName) {
        $subject = Mage::getStoreConfig('sales_email/billetremember/email_subject');
        $subject = $this->renderStoreNameVar($subject);
        $subject = $this->renderCustomerNameVar($subject, $customerName);
        return $subject;
    }
    
    public function renderEmailBody($customerName) {
        $body = Mage::getStoreConfig('sales_email/billetremember/email_body');
        $body = $this->renderStoreNameVar($body);
        $body = $this->renderCustomerNameVar($body, $customerName);
        return $body;
    }

    public function renderStoreNameVar($string) {
        if (strpos($string, "{{nome_da_loja}}") !== false) {
            $storeName = Mage::getStoreConfig('general/store_information/name');
            return str_replace("{{nome_da_loja}}", $storeName ,$string);
        } return $string;
    }
    
    public function renderCustomerNameVar($string, $customerName) {
        if (strpos($string, "{{nome_do_cliente}}") !== false) {
            return str_replace("{{nome_do_cliente}}", $customerName ,$string);
        } return $string;
    }

}
