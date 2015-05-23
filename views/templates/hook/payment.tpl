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
		<a href="{$link->getModuleLink('payu', 'payment', [], true)}" title="{l s='Pay with Credit Card' mod='payU'}">
			<img src="{$this_path_pu}credit-card.png" alt="{l s='Pay with PAYU' mod='payU'}" />
	{else}
		<img src="{$this_path_pu}payu.gif" alt="{l s='Pay with Credit Card' mod='payU'}" />
		{l s='PayU Details Not Correct' mod='payU'}
		
	{/if}
</p>
 
