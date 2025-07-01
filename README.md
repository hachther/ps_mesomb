# MeSomb for PrestaShop

MeSomb for PrestaShop is a fast and easy way to integrate mobile payment (Mobile Money Orange Money, Airtel Money) in your PrestaShop shop.

This will help your add the mobile payment on your shop by relying on MeSomb services which is currently available in Cameroon and Niger.

## Installation

1. First you must register your service on MeSomb and create API access Key: [follow this tutorial](https://mesomb.hachther.com/en/blog/tutorials/how-to-register-your-service-on-mesomb/)
2. Once your service is registered, you must get those three pieces of information: Application Key, Access Key and Secret Key
3. Activate your MeSomb Payment Method in your PrestaShop backend. On the left menu in the admin panel go to Payment -> Payment Methods and click on *Configure* under "Mobile Payment"
4. Set up the gateway by filling out the form. You must set the following parameters:
   - *Title*: The title to give to your payment gateway (what customers will see)
   - *Description*: A quick description of the gateway
   - *Fees Included*: Check this if you want to say that amount shown to the customer already included MeSomb fees. Otherwise, the amount asks the customer will be greater than what your shop shows.
   - *MeSomb Application Key*: Got from MeSomb.
   - *MeSomb Access Key*: from MeSomb.
   - *MeSomb Secret Key*: from MeSomb.
   - *Countries*: Select countries which you want to receive payment from.
   - *Currency Conversion*: In case your shop is in foreign currency, check this if you want MeSomb to convert to the local currency before debiting the customer otherwise you must set your shop to the local currency.

## Frequently Asked Questions

### MeSomb is available in which countries?

- Cameroon 
- Nigeria
- Benin
- Burkina Faso
- RDC
- Congo
- Gabon
- Ivory Coast
- Malawi
- Rwanda
- Senegal
- Sierra Leone
- Tanzania
- Uganda
- Zambia

### Which operators supported by MeSomb?

Orange Money and Mobile Money for the Cameroon and Airtel Money for the Niger

### Does MeSomb has installation fees?

No, Installation is free.
