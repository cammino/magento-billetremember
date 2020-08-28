<?php
require __DIR__ . '/../lib/twilio/Twilio/autoload.php';

class Cammino_Billetremember_Model_Job
{
    public function notify() {

        $helper = Mage::helper("billetremember");
        
        $moduleIsActive     = $helper->moduleIsActive();
        $notifyByEmail      = $helper->notifyByEmail();
        $notifyByWhatsapp   = $helper->notifyByWhatsapp();
        $notifyBySMS        = $helper->notifyBySMS();
        
        if($moduleIsActive && ($notifyByEmail || $notifyByWhatsapp || $notifyBySMS)):

            $payments = $this->getBilletOrders();

            if($notifyByEmail):
                Mage::getModel("billetremember/email")->sendEmail($payments);
            endif;

            if($notifyByWhatsapp):
                Mage::getModel("billetremember/whatsapp")->sendMessage($payments);
            endif;
            
            if($notifyBySMS):
                Mage::getModel("billetremember/sms")->sendMessage($payments);
            endif;

        endif;
    }

    public function getBilletOrders() {
        $payments = Mage::getModel( 'sales/order_payment' )->getCollection();
        $hours = Mage::helper("billetremember")->getHours();

        if ((bool)Mage::getStoreConfig('payment/pagarme_boleto/active')) {
            $payments->getSelect()
                ->where('amount_paid IS NULL')
                ->where('pagarme_boleto_url IS NOT NULL')
                ->where('DATE(pagarme_boleto_expiration_date) >= DATE(NOW())')
                ->where('DATE_ADD(pagarme_boleto_expiration_date, INTERVAL -'.$hours.' HOUR) < NOW()')
                ->where('((additional_data IS NULL) OR (additional_data NOT LIKE \'%billetremember%\'))');
        } else if ((bool)Mage::getStoreConfig('payment/mercadopago_customticket/active')) {
            $expiration = Mage::getStoreConfig('payment/mercadopago_customticket/date_of_expiration');
            $payments->getSelect()
                ->joinInner(array('order' => Mage::getSingleton('core/resource')->getTableName('sales/order')),
                    'order.entity_id = main_table.parent_id',
                    array())
                ->where('main_table.amount_paid IS NULL')
                ->where('main_table.method = \'mercadopago_customticket\'')
                ->where('DATE_ADD(order.created_at, INTERVAL '.$expiration.' DAY) >= DATE(NOW())')
                ->where('DATE_ADD(DATE_ADD(order.created_at, INTERVAL '.$expiration.' DAY), INTERVAL -'.$hours.' HOUR) < NOW()')
                ->where('((main_table.additional_data IS NULL) OR (main_table.additional_data NOT LIKE \'%billetremember%\'))');
        }

        echo $payments->getSelect()->__toString();

        $payments->load();
        return $payments;
    }

    public function getBilletUrl( $payment ) {

        if ($payment->getMethod() == 'pagarme_boleto') {
            return $payment->getPagarmeBoletoUrl();
        } else if ($payment->getMethod() == 'mercadopago_customticket') {
            $additional = $payment->getAdditionalInformation();
            return $additional['activation_uri'];
        } else {
            return '';
        }

        
    }

}