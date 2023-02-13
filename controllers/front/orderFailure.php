<?php
class Ps_mesombOrderFailureModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'mesomb_order_url' => $this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ),
            'message' => urldecode(Tools::getValue('message'))
        ]);

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->setTemplate('module:ps_mesomb/views/templates/front/order-confirmation-failed-17.tpl');
        } else {
            $this->setTemplate('order-confirmation-failed-16.tpl');
        }
    }
}
