<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_mesomb extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    private $countries_name = [
        'CM' => 'Cameroon',
        'NE' => 'Niger',
    ];

    public function __construct()
    {
        $this->name = 'ps_mesomb';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Hachther LLC';
        $this->controllers = ['payment', 'validation'];
        $this->hooks = array('displayHeader');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(['APP_KEY', 'CLIENT_KEY', 'SECRET_KEY', 'MESOMB_COUNTRIES', 'FEES_INCLUDED', 'CONVERSION']);
        if (isset($config['APP_KEY'])) {
            $this->appKey = $config['APP_KEY'];
        }
        if (isset($config['CLIENT_KEY'])) {
            $this->clientKey = $config['CLIENT_KEY'];
        }
        if (isset($config['SECRET_KEY'])) {
            $this->secretKey = $config['SECRET_KEY'];
        }
        if (isset($config['MESOMB_COUNTRIES'])) {
            $this->countries = explode(',', $config['MESOMB_COUNTRIES']);
        }
        if (isset($config['FEES_INCLUDED'])) {
            $this->feesIncluded = $config['FEES_INCLUDED'];
        }
        if (isset($config['CONVERSION'])) {
            $this->conversion = $config['CONVERSION'];
        }

        $this->providers = array(
            array(
                'key' => 'MTN',
                'name' => 'MTN',
                'description' => $this->trans("Pay with Mobile Money", [], 'Modules.Mesomb.Admin'),
                'icon' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/images/logo-momo.png'),
                'countries' => array('CM')
            ),
            array(
                'key' => 'ORANGE',
                'name' => 'Orange',
                'description' => $this->trans("Pay with Orange Money", [], 'Modules.Mesomb.Admin'),
                'icon' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/images/logo-orange.jpg'),
                'countries' => array('CM')
            ),
            array(
                'key' => 'AIRTEL',
                'name' => 'Airtel',
                'description' => $this->trans('Pay with Airtel Money', [], 'Modules.Mesomb.Admin'),
                'icon' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/images/logo-airtel.jpg'),
                'countries' => array('NE')
            )
        );

        $this->bootstrap = true;
        parent::__construct();

        require_once realpath(dirname(__FILE__) . '/smarty/plugins') . '/modifier.mesomblreplace.php';

        $this->displayName = $this->trans('Mobile Payment', [], 'Modules.Mesomb.Admin');
        $this->description = $this->trans('This module allows you to accept mobile payments (Mobile Money, Orange Money ...) in your shop', [], 'Modules.Mesomb.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete these details?', [], 'Modules.Mesomb.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

        if ((!isset($this->appKey) || !isset($this->clientKey) || empty($this->secretKey) || empty($this->countries))) {
            $this->warning = $this->trans('MeSomb settings must be configured before using this module.', [], 'Modules.Mesomb.Admin');
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', [], 'Modules.Mesomb.Admin');
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('displayHeader')) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        return Configuration::deleteByName('APP_KEY')
            && Configuration::deleteByName('CLIENT_KEY')
            && Configuration::deleteByName('SECRET_KEY')
            && Configuration::deleteByName('MESOMB_COUNTRIES')
            && Configuration::deleteByName('FEES_INCLUDED')
            && Configuration::deleteByName('CONVERSION')
            && parent::uninstall();
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('APP_KEY')) {
                $this->_postErrors[] = $this->trans('The "Application Key" field is required.', [], 'Modules.Mesomb.Admin');
            }
            if (!Tools::getValue('CLIENT_KEY')) {
                $this->_postErrors[] = $this->trans('The "Client Key" field is required.', [], 'Modules.Mesomb.Admin');
            }
            if (!Tools::getValue('SECRET_KEY')) {
                $this->_postErrors[] = $this->trans('The "Secret Key" field is required.', [], 'Modules.Mesomb.Admin');
            }
            if (!Tools::getValue('MESOMB_COUNTRIES')) {
                $this->_postErrors[] = $this->trans('You must select at least one country', [], 'Modules.Mesomb.Admin');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('APP_KEY', Tools::getValue('APP_KEY'));
            Configuration::updateValue('CLIENT_KEY', Tools::getValue('CLIENT_KEY'));
            Configuration::updateValue('SECRET_KEY', Tools::getValue('SECRET_KEY'));
            Configuration::updateValue('MESOMB_COUNTRIES', implode(',', Tools::getValue('MESOMB_COUNTRIES')));
            Configuration::updateValue('FEES_INCLUDED', Tools::getValue('FEES_INCLUDED'));
            Configuration::updateValue('CONVERSION', Tools::getValue('CONVERSION'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Notifications.Success'));
    }

    private function _displayCheck()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayCheck();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [
//            $this->getOfflinePaymentOption(),
//            $this->getExternalPaymentOption(),
            $this->getEmbeddedPaymentOption(),
//            $this->getIframePaymentOption(),
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('MeSomb Service Settings', [], 'Modules.Mesomb.Admin'),
                    'icon' => 'icon-cog',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Application Key', [], 'Modules.Mesomb.Admin'),
                        'name' => 'APP_KEY',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Client Key', [], 'Modules.Mesomb.Admin'),
                        'name' => 'CLIENT_KEY',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Secret Key', [], 'Modules.Mesomb.Admin'),
                        'name' => 'SECRET_KEY',
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Countries', [], 'Modules.Mesomb.Admin'),
                        'name' => 'MESOMB_COUNTRIES',
                        'required' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'CM', 'name' => $this->trans('Cameroon', [], 'Modules.Mesomb.Admin')),
                                array('id' => 'NE', 'name' => $this->trans('Niger', [], 'Modules.Mesomb.Admin')),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                        'hint' => $this->trans('You can receive payments from which countries', [], 'Modules.Mesomb.Admin'),
                        'multiple' => true
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Fees Included', [], 'Modules.Mesomb.Admin'),
                        'name' => 'FEES_INCLUDED',
                        'required' => true,
                        'hint' => $this->trans('Fees are already included in the displayed price', [], 'Modules.Mesomb.Admin'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'fees_included_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Modules.Mesomb.Admin'),
                            ],
                            [
                                'id' => 'fees_included_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global')
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Currency Conversion', [], 'Modules.Mesomb.Admin'),
                        'name' => 'CONVERSION',
                        'required' => false,
                        'hint' => $this->trans('Rely on MeSomb to automatically convert foreign currencies', [], 'Modules.Mesomb.Admin'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'conversion_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Modules.Mesomb.Admin'),
                            ],
                            [
                                'id' => 'conversion_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global')
                            ]
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'APP_KEY' => Tools::getValue('APP_KEY', Configuration::get('APP_KEY')),
            'CLIENT_KEY' => Tools::getValue('CLIENT_KEY', Configuration::get('CLIENT_KEY')),
            'SECRET_KEY' => Tools::getValue('SECRET_KEY', Configuration::get('SECRET_KEY')),
            'MESOMB_COUNTRIES[]' => Tools::getValue('MESOMB_COUNTRIES', explode(',', Configuration::get('MESOMB_COUNTRIES', null, null, null, 'CM'))),
            'FEES_INCLUDED' => Tools::getValue('FEES_INCLUDED', Configuration::get('FEES_INCLUDED', null, null, null, true)),
            'CONVERSION' => Tools::getValue('CONVERSION', Configuration::get('CONVERSION')),
        ];
    }

//    public function getOfflinePaymentOption()
//    {
//        $offlineOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
//        $offlineOption->setCallToActionText($this->l('Pay offline'))
//                      ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
//                      ->setAdditionalInformation($this->context->smarty->fetch('module:paymentexample/views/templates/front/payment_infos.tpl'))
//                      ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));
//
//        return $offlineOption;
//    }

//    public function getExternalPaymentOption()
//    {
//        $externalOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
//        $externalOption->setCallToActionText($this->l('Pay external'))
//                       ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
//                       ->setInputs([
//                            'token' => [
//                                'name' =>'token',
//                                'type' =>'hidden',
//                                'value' =>'12345689',
//                            ],
//                        ])
//                       ->setAdditionalInformation($this->context->smarty->fetch('module:paymentexample/views/templates/front/payment_infos.tpl'))
//                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));
//
//        return $externalOption;
//    }

    public function getEmbeddedPaymentOption()
    {
        $embeddedOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $embeddedOption->setCallToActionText($this->l('Pay by Mobile Payment'))
                       ->setForm($this->generateForm())
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                       ->setAdditionalInformation($this->context->smarty->fetch('module:ps_mesomb/views/templates/front/payment_infos.tpl'));
//                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));

        return $embeddedOption;
    }

//    public function getIframePaymentOption()
//    {
//        $iframeOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
//        $iframeOption->setCallToActionText($this->l('Pay iframe'))
//                     ->setAction($this->context->link->getModuleLink($this->name, 'iframe', array(), true))
//                     ->setAdditionalInformation($this->context->smarty->fetch('module:ps_mesomb/views/templates/front/payment_infos.tpl'))
//                     ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));
//
//        return $iframeOption;
//    }

    protected function generateForm()
    {
        $provs = array_filter($this->providers, function($k, $v) {
            return count(array_intersect($k['countries'], (array)$this->countries)) > 0;
        }, ARRAY_FILTER_USE_BOTH);

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
            'providers' => $provs,
            'countries' => array_map(function ($v) {return ['name' => $this->countries_name[$v], 'value' => $v];}, $this->countries),
        ]);

        return $this->context->smarty->fetch('module:ps_mesomb/views/templates/front/payment_form.tpl');
    }

    /**
     * Load JS on the front office order page
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->registerJavascript(
            $this->name,
            'modules/' . $this->name . '/views/js/ps_mesomb.js'
        );

        $this->context->controller->registerStylesheet(
            $this->name,
            'modules/' . $this->name . '/views/css/style.css'
        );
    }
}
