<?php
class Shopware_Controllers_Frontend_BxNotification extends Enlight_Controller_Action {


    public function indexAction() {
    }

    public function voucherAction() {
        $helper = \Boxalino\Helper\P13NHelper::instance();
        $helper->setRequest($this->Request());
        $choiceId = $this->Request()->getQuery('bxChoiceId');

        if(is_null($choiceId) || $choiceId == '') {
            $choiceId = 'voucher';
        }
        $data = $helper->addVoucher($choiceId);
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-type', 'application/json', true);
        $this->Response()->setBody(json_encode($data));
    }
}