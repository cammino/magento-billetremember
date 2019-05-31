<?php
class Cammino_Billetremember_Model_Email
{
    public function sendEmail($payments) {
        header("Content-type: text/html; charset=utf-8");
        
        foreach($payments as $payment):
            try {
                $orderId = $payment->getParentId();
                $order = Mage::getModel("sales/order")->load($orderId);
                $billetUrl = Mage::getModel("billetremember/job")->getBilletUrl($payment);

                $mailer = $this->prepareEmail($order, $billetUrl);
                $sent = $mailer->send();

                if($sent) {
                    $addata = unserialize($payment->getData("additional_data"));
                    $addata["billetremember"] = true;
                    $payment->setAdditionalData(serialize($addata))->save();
                }

            } catch (Exception $e) {
                return false;
            }
        endforeach;
    }

    public function prepareEmail($order, $billetUrl) {
        $storeId = $order->getStore()->getId();

        $customerName = $order->getCustomerFirstname();
        $customerEmail = $order->getCustomerEmail();

        $subject = Mage::helper("billetremember")->renderEmailSubject($customerName);
        $body = Mage::helper("billetremember")->renderEmailBody($customerName);

        $mailer = Mage::getModel("core/email_template_mailer");
        $emailInfo = Mage::getModel("core/email_info");

        $emailInfo->addTo($customerEmail, $customerName);
        $mailer->addEmailInfo($emailInfo);
        $mailer->setSender(Mage::getStoreConfig( Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId("billetremember_email");
        $mailer->setTemplateParams(array(
            'subject'   => $subject,
            'body'      => nl2br($body),
            'billetUrl' => $billetUrl
        ));

        return $mailer;
    }
}