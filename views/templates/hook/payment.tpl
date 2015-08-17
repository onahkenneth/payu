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

<p class="payment_module">
	{if $confirmPayment == 1}
		<a href="{$link->getModuleLink('payu', 'payment', [], true)}" title="{l s='Pay with Credit/Debit Card' mod='payu'}">
			<img src="{$this_path_pu}credit-card.png" alt="{l s='Pay with Credit/Debit Card' mod='payu'}" />
	{else}
		<img src="{$this_path_pu}payu.gif" alt="{l s='Pay with Credit/Debit Card' mod='payu'}" />
		{l s='An error was encountered. Please contact our ' mod='payu'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}" style="color:#317fd8">{l s='CUSTOMER CARE.' mod='payu'}</a>
	{/if}
</p>
 
