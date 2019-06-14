<?php

class Cammino_Billetremember_NotifyController extends Mage_Core_Controller_Front_Action
{
    /**
    * Function responsible for start notify process
    *
    * @return null
    */
    public function indexAction()
    {
        $model = Mage::getModel("billetremember/job");
        $model->notify();
        die;
    }

}