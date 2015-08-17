{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
*  @author Kenneth Onah <kenneth@netcraft-devops.com>
*  @copyright  2015 NetCraft DevOps
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  Property of NetCraft DevOps
*}

<p>{l s='Your order on ' mod='payU'} <span class="bold">{$shop_name}</span> {l s=' has failed.' mod='payU'}
	<br /><br />{l s='Please contact our ' mod='payU'} 
	<a href="{$link->getPageLink('contact', true)|escape:'html'}" style="color:#317fd8">{l s='CUSTOMER CARE.' mod='payU'}</a>
</p>
