<?php
class Cammino_Billetremember_Model_Job
{
    public function notify() {

        $helper = Mage::helper("billetremember");
        
        $moduleIsActive     = $helper->moduleIsActive();
        $notifyByEmail      = $helper->notifyByEmail();
        $notifyByWhatsapp   = $helper->notifyByWhatsapp();
        $logIsActive        = $helper->logsIsActive();
        
        if($moduleIsActive && ($notifyByEmail || $notifyByWhatsapp)):

            $payments = $this->getBilletOrders();

            if($notifyByEmail):
                Mage::getModel("billetremember/email")->sendEmail($payments);
            endif;

            if($notifyByWhatsapp):
                Mage::getModel("billetremember/whatsapp")->sendMessage($payments);
            endif;


        endif;
    }

    private function getBilletOrders() {
        $payments = Mage::getModel( 'sales/order_payment' )->getCollection();
        $hours = Mage::helper("billetremember")->getHours();
        
        $payments->getSelect()
            ->where('pagarme_boleto_url IS NOT NULL')
            ->where('DATE(pagarme_boleto_expiration_date) >= DATE(NOW())')
            ->where('DATE_ADD(pagarme_boleto_expiration_date, INTERVAL -'.$hours.' HOUR) < NOW()')
            ->where('((additional_data IS NULL) OR (additional_data NOT LIKE \'%billetremember%\'))');
        
        $payments->load();

        return $payments;
    }

    public function getBilletUrl( $payment ) {
        return $payment->getPagarmeBoletoUrl();
    }

}