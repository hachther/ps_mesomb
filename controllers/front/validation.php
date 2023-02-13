<?php
use MeSomb\Operation\PaymentOperation;
use MeSomb\Util\RandomGenerator;

/**
 * @since 1.5.0
 */
class Ps_mesombValidationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent(); // TODO: Change the autogenerated stub

        $this->context->smarty->assign(
            array(
                'paymentId' => Tools::getValue('id'), // Retrieved from GET vars
                'paymentStatus' => '',
            ));
        $this->setTemplate('module:ps_mesomb/views/templates/front/validation.tpl');
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $cart = $this->context->cart;

        $customer = new Customer($cart->id_customer);
        $delivery = new Address((int) $cart->id_address_delivery);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        MeSomb\MeSomb::$apiBase = 'http://127.0.0.1:8000';
        $country_codes = ['CM' => '237', 'NE' => '227'];
        $countries = explode(',', Configuration::get('MESOMB_COUNTRIES'));
        $operation = new PaymentOperation(Configuration::get('APP_KEY'), Configuration::get('CLIENT_KEY'), Configuration::get('SECRET_KEY'));
        $country = Tools::getValue('country', $countries[0]);
        $service = Tools::getValue('service');
        $payer = Tools::getValue('payer');
        $payer = ltrim($payer, '00');
        $payer = ltrim($payer, $country_codes[$country]);
        $phone = $delivery->phone ?? $delivery->phone_mobile;
        $cust = [
            'first_name' => $customer->firstname,
            'last_name' => $customer->lastname,
            'town' => $delivery->city,
            'country' => $delivery->country,
            'address_1' => $delivery->address1,
            'postcode' => $delivery->postcode,
        ];
        if (!empty($phone)) {
            $cust['phone'] = $phone;
        }
        if (!empty($customer->email)) {
            $cust['email'] = $customer->email;
        }
        $products = [];
        foreach ($cart->getProducts() as $item) {
            $products[] = [
                'id' => $item['id_product'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'amount' => $item['total_wt'],
                'category' => $item['category'],
            ];
        }
        try {
            $ret = $operation->makeCollect(
                $total,
                $service,
                $payer,
                new DateTime('now'),
                RandomGenerator::nonce(),
                $cart->id,
                $country,
                $currency->iso_code,
                Configuration::get('FEES_INCLUDED'),
                null,
                Configuration::get('CONVERSION'),
                null,
                $cust,
                $products,
                ['source' => 'PrestaShop '._PS_VERSION_]
            );
            if ($ret->isTransactionSuccess()) {
                $trxData = $ret->getData();
                $this->module->validateOrder(
                    (int) $cart->id,
                    (int) Configuration::get('PS_OS_PAYMENT'),
                    $total,
                    $this->module->displayName,
                    null,
                    [],
                    (int) $currency->id,
                    false,
                    $customer->secure_key,
                );

                $orderId = Order::getIdByCartId($cart->id);
                $order = new Order($orderId);
                $orderPaymentDatas = $order->getOrderPaymentCollection();
                $orderPayment = new OrderPayment($orderPaymentDatas[0]->id);
                $orderPayment->transaction_id = $trxData['pk'];
                $orderPayment->card_number = $trxData['b_party'];
                $orderPayment->card_brand = $trxData['service'];
                $orderPayment->save();

                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int) $cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
            }

            Tools::redirect(Context::getContext()->link->getModuleLink(
                $this->module->name,
                'orderFailure',
                ['message' => urlencode($ret->getMessage())]
            ));
        } catch (Exception $e) {
            Tools::redirect(Context::getContext()->link->getModuleLink(
                $this->module->name,
                'orderFailure',
                ['message' => urlencode($e->getMessage())]
            ));
            return;
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        $this->setTemplate('module:ps_mesomb/views/templates/front/payment_return.tpl');
    }

    /**
     * Check if the context is valid
     * - Cart is loaded
     * - Cart has a Customer
     * - Cart has a delivery address
     * - Cart has an invoice address
     * - Cart doesn't contains virtual product
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice)
            && false === $this->context->cart->isVirtualCart();
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
