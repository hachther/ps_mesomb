{*
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
*}

<form action="{$action}" id="payment-form">
  <div style="display: none;" id="mesomb-provider-names" data-json='{json_encode($placeholders)}'></div>
  {if is_array($countries) && count($countries) > 1}
    <div class="form-group row">
      <label class="col-md-3 form-control-label required" for="id_country">{l s='Country'}</label>
      <div class="col-md-6 js-input-column">
        <select name="country" id="id_country" class="form-control form-control-select js-country">
            {foreach from=$countries item=country}
              <option value="{$country.value}">{$country.name}</option>
            {/foreach}
        </select>
      </div>
    </div>
  {/if}
  <div class="form-group row">
    <label class="col-md-3 form-control-label required" for="id_country">{l s='Operator'}</label>
    <div class="col-md-6 js-input-column">
      <div id="providers" style="display: flex; flex-direction: row; flex-wrap: wrap;">
          {foreach from=$providers item=provider}
            <div class="form-row provider-row {implode(' ', $provider.countries)}" style="width: 47%; margin-right: 2%; margin-bottom: 2%;">
              <label class="kt-option">
                <span class="kt-option__label">
                  <span class="kt-option__head">
                    <span class="kt-option__control">
                        <span class="kt-radio">
                          <input name="service" value="{$provider.key}" type="radio"/>
                          <span></span>
                        </span>
                    </span>
                    <span class="kt-option__title">{$provider.name}</span>
                    <span class="kt-option__focus"
                          style="position: relative; right: -10px; top: -10px;">
                      <img alt="{$provider.key}" src="{$provider.icon}"
                           style="width: 25px; border-radius: 13px;"/>
                    </span>
                  </span>
                  <span class="kt-option__body">{$provider.description}</span>
                </span>
              </label>
            </div>
          {/foreach}
      </div>
    </div>
  </div>
  <div class="form-group row">
    <label class="col-md-3 form-control-label required" for="id_country">{l s='Phone Number'}</label>
    <div class="col-md-6 js-input-column">
      <input type="tel" autocomplete="off" name="payer" required="required" class="form-control">
    </div>
  </div>
</form>
