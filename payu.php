<?php

if(!defined('_PS_VERSION_'))
	exit;

class payu extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();
	public $confirmationUrl = '';
	public static $ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
	public static $soapClient;

	public function __construct()
	{
		$this->name = 'payu';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.1';

		$this->currencies = true;
		$this->currencies_mode = 'radio';

		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency set for this module');

		if (!extension_loaded('soap'))
			$this->warning = $this->l('SOAP extension must be enabled on your server to use this module.');

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('PayU');
		$this->description = $this->l('Accepts payments by PayU');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
	}

	public function install()
	{
		if (!parent::install()
		OR !$this->installCurrency()
		OR !Configuration::updateValue('PAYU_MERCHANT_REF', '7')
		OR !Configuration::updateValue('PAYU_SAFE_KEY', '{07F70723-1B96-4B97-B891-7BF708594EEA}')
		OR !Configuration::updateValue('PAYU_SOAP_USERNAME', 'Staging Integration Store 3')
		OR !Configuration::updateValue('PAYU_SOAP_PASSWORD', 'WSAUFbw6')
		OR !Configuration::updateValue('PAYU_PAYMENT_METHOD', 'CREDITCARD, LOYALTY, WALLET, DISCOVERY MILES, GLOBAL PAY, DEBIT CARD, EBUCKS, PAYPAL')
		OR !Configuration::updateValue('PAYU_WHERE_TO_PAY', '')
		OR !Configuration::updateValue('PAYU_INVOICE', '')
		OR !Configuration::updateValue('PAYU_BILLING_CURRENCY', 'ZAR')
		OR !Configuration::updateValue('PAYU_SANDBOX', 1)
		OR !Configuration::updateValue('PAYU_TRANSACTION_TYPE', 'RESERVE')
		OR !Configuration::updateValue('CONFIRMATION_URL', 1)
		OR !$this->registerHook('payment'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('PAYU_MERCHANT_REF')
		OR !Configuration::deleteByName('PAYU_SAFE_KEY')
		OR !Configuration::deleteByName('PAYU_SOAP_USERNAME')
		OR !Configuration::deleteByName('PAYU_SOAP_PASSWORD')
		OR !Configuration::deleteByName('PAYU_SANDBOX')
		OR !Configuration::deleteByName('CONFIRMATION_URL')
		OR !Configuration::deleteByName('PAYU_PAYMENT_METHOD')
		OR !Configuration::deleteByName('PAYU_WHERE_TO_PAY')
		OR !Configuration::deleteByName('PAYU_INVOICE')
		OR !Configuration::deleteByName('PAYU_BILLING_CURRENCY')
		OR !Configuration::deleteByName('PAYU_TRANSACTION_TYPE')
		OR !parent::uninstall())
			return false;
		return true;
	}

	public function installCurrency()
	{
		//Check if rands are installed and install and refresh if not
		$currency = new Currency();
		$currency_rand_id  = $currency->getIdByIsoCode('ZAR');

		if(is_null($currency_rand_id)){
			$currency->name = "South African Rand";
			$currency->iso_code = "ZAR";
			$currency->sign = "R";
			$currency->format = 1;
			$currency->blank = 1;
			$currency->decimals = 1;
			$currency->deleted = 0;
			$currency->conversion_rate = 0.45; //set it to an arb value
			$currency->add();
			$currency->refreshCurrencies();
		}

		return true;
	}

	public function getContent()
	{
		$this->_html = '<h2>payU</h2>';
		if (isset($_POST['submitpayU']))
		{
			if (empty($_POST['merchant_ref']))
				$this->_postErrors[] = $this->l('payU Merchant Reference is required.');
			if (empty($_POST['safe_key']))
				$this->_postErrors[] = $this->l('payU Safe Key is required.');
			if (empty($_POST['soap_username']))
				$this->_postErrors[] = $this->l('payU SOAP Username is required.');
			if (!isset($_POST['sandbox']))
				$_POST['sandbox'] = 1;
			if (!sizeof($this->_postErrors))
			{
				Configuration::updateValue('PAYU_MERCHANT_REF', strval($_POST['merchant_ref']));
				Configuration::updateValue('PAYU_SAFE_KEY', strval($_POST['safe_key']));
				Configuration::updateValue('PAYU_SOAP_USERNAME', strval($_POST['soap_username']));
				Configuration::updateValue('PAYU_SOAP_PASSWORD', strval($_POST['soap_password']));
				Configuration::updateValue('PAYU_SANDBOX', intval($_POST['sandbox']));
				Configuration::updateValue('PAYU_WHERE_TO_PAY', strval($_POST['where_to_pay']));
				Configuration::updateValue('PAYU_PAYMENT_METHOD', strval($_POST['payment_method']));
				Configuration::updateValue('PAYU_BILLING_CURRENCY', strval($_POST['billing_Currency']));
				Configuration::updateValue('PAYU_INVOICE', strval($_POST['payU_invoice_description_prepend']));
				Configuration::updateValue('PAYU_TRANSACTION_TYPE', strval($_POST['payU_transcation_type']));

				$this->displayConf();
			}
			else
				$this->displayErrors();
		}

		$this->displaypayUFormHeader();
		$this->displayFormSettings();
		return $this->_html;
	}

	public function displayConf()
	{
		$this->_html .= '
				<div class="conf confirm">
				<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
						'.$this->l('Settings updated for payU').'
								</div>';
	}

	public function displayErrors()
	{
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
				<div class="alert error">
				<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
						<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
				</ol>
				</div>';
	}


	public function displaypayUFormHeader()
	{
		$this->_html .= '
				<div style="float: right; width: 440px; height: 150px; border: dashed 1px #666; padding: 8px; margin-left: 12px;">
				<h2>'.$this->l('Open/Access your payU Account').'</h2>
						<div style="clear: both;"></div></b><br />
						<p>'.$this->l('Click on the payU Logo Below to register or edit your payU Account').'</p>
								<p style="text-align: center;"><a href="https://www.payu.co.za/signup.do"><img src="../modules/payu/payu.gif" alt="payU" style="margin-top: 12px;" /></a></p>
								<div style="clear: right;"></div>
								</div>
								<b></b><br />
								<b>'.$this->l('This module allows you to accept payments by payU.').'</b><br /><br /><br />
										'.$this->l('If the client chooses this payment mode, your payU account will be automatically credited.').'<br /><br />
												'.$this->l('You need to configure your payU account first before using this module.').'
														<div style="clear:both;">&nbsp;</div>';
	}

	public function displayFormSettings()
	{
		$conf = Configuration::getMultiple(array('PAYU_MERCHANT_REF', 'PAYU_SAFE_KEY', 'PAYU_SOAP_USERNAME', 'PAYU_SOAP_PASSWORD', 'PAYU_SANDBOX','PAYU_WHERE_TO_PAY','PAYU_PAYMENT_METHOD','PAYU_BILLING_CURRENCY','PAYU_INVOICE'));
			
		$where_to_pay = array_key_exists('where_to_pay', $_POST) ? $_POST['where_to_pay'] : (array_key_exists('PAYU_WHERE_TO_PAY', $conf) ? $conf['PAYU_WHERE_TO_PAY'] : '');
		$payment_method = array_key_exists('payment_method', $_POST) ? $_POST['payment_method'] : (array_key_exists('PAYU_PAYMENT_METHOD', $conf) ? $conf['PAYU_PAYMENT_METHOD'] : '');
		$billing_Currency = array_key_exists('billing_Currency', $_POST) ? $_POST['billing_Currency'] : (array_key_exists('PAYU_BILLING_CURRENCY', $conf) ? $conf['PAYU_BILLING_CURRENCY'] : '');
		$payU_invoice_description_prepend = array_key_exists('payU_invoice_description_prepend', $_POST) ? $_POST['payU_invoice_description_prepend'] : (array_key_exists('PAYU_INVOICE', $conf) ? $conf['PAYU_INVOICE'] : '');
		$payU_transcation_type = array_key_exists('payU_transcation_type', $_POST) ? $_POST['payU_transcation_type'] : (array_key_exists('PAYU_TRANSACTION_TYPE', $conf) ? $conf['PAYU_TRANSACTION_TYPE'] : '');
		$merchant_ref = array_key_exists('merchant_ref', $_POST) ? $_POST['merchant_ref'] : (array_key_exists('PAYU_MERCHANT_REF', $conf) ? $conf['PAYU_MERCHANT_REF'] : '');
		$safe_key = array_key_exists('safe_key', $_POST) ? $_POST['safe_key'] : (array_key_exists('PAYU_SAFE_KEY', $conf) ? $conf['PAYU_SAFE_KEY'] : '');
		$soap_username = array_key_exists('soap_username', $_POST) ? $_POST['soap_username'] : (array_key_exists('PAYU_SOAP_USERNAME', $conf) ? $conf['PAYU_SOAP_USERNAME'] : '');
		$soap_password = array_key_exists('soap_password', $_POST) ? $_POST['soap_password'] : (array_key_exists('PAYU_SOAP_PASSWORD', $conf) ? $conf['PAYU_SOAP_PASSWORD'] : '');
		$sandbox = array_key_exists('sandbox', $_POST) ? $_POST['sandbox'] : (array_key_exists('PAYU_SANDBOX', $conf) ? $conf['PAYU_SANDBOX'] : '');

		$this->_html .= '
				<form action="'.$_SERVER['REQUEST_URI'].'" method="post" style="clear: both;">
						<fieldset>
						<legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>
									
								<label>'.$this->l('Where to Pay.').'</label>
										<div class="margin-form"><input type="text" size="40" name="where_to_pay" value="'.htmlentities($where_to_pay, ENT_COMPAT, 'UTF-8').'" /> * </div>
													
												<label>'.$this->l('Merchant Reference.').'</label>
														<div class="margin-form"><input type="text" size="40" name="merchant_ref" value="'.htmlentities($merchant_ref, ENT_COMPAT, 'UTF-8').'" /> * </div>
																	
																<label>'.$this->l('Safe Key').'</label>
																		<div class="margin-form"><input type="text" size="40" name="safe_key" value="'.htmlentities($safe_key, ENT_COMPAT, 'UTF-8').'" /> * </div>
																					
																				<label>'.$this->l('SOAP Username').'</label>
																						<div class="margin-form"><input type="text" size="40" name="soap_username" value="'.htmlentities($soap_username, ENT_COMPAT, 'UTF-8').'" /> * </div>
																									
																								<label>'.$this->l('SOAP Password').'</label>
																										<div class="margin-form"><input type="text" size="40" name="soap_password" value="'.htmlentities($soap_password, ENT_COMPAT, 'UTF-8').'" /> * </div>


																												<label>'.$this->l('Payment Method').'</label>
																														<div class="margin-form"><input type="text" size="110" name="payment_method" value="'.htmlentities($payment_method, ENT_COMPAT, 'UTF-8').'" /> * </div>
																																';
			
		if($payU_transcation_type == 'RESERVE'){
			$this->_html .= '
					<label>'.$this->l('Transcation Type').'</label>
							<div class="margin-form">
							<select name="payU_transcation_type">
							<option selected="selected" value="RESERVE">RESERVE</option>
							<option value="PAYMENT">PAYMENT</option>
							</select>

							</div>
							';
		}else{
			$this->_html .= '
					<label>'.$this->l('Transcation Type').'</label>
							<div class="margin-form">
							<select name="payU_transcation_type">
							<option value="RESERVE">RESERVE</option>
							<option selected="selected" value="PAYMENT">PAYMENT</option>
							</select>

							</div>
							';
		}
			
		$this->_html .= '
				<label>'.$this->l('Billing Currency').'</label>
						<div class="margin-form"><input type="text" size="40" name="billing_Currency" value="'.htmlentities($billing_Currency, ENT_COMPAT, 'UTF-8').'" /> * </div>
									
								<label>'.$this->l('PayU Invoice Description Prepend').'</label>
										<div class="margin-form"><input type="text" size="40" name="payU_invoice_description_prepend" value="'.htmlentities($payU_invoice_description_prepend, ENT_COMPAT, 'UTF-8').'" /> * </div>
													
													
													


												<label>'.$this->l('Transaction Server').'</label>
														<div class="margin-form">
														<input type="radio" name="sandbox" value="1" '.($sandbox ? 'checked="checked"' : '').' /> <label class="t">'.$this->l('Sandbox (Test)').'</label><br /><br />
																<input type="radio" name="sandbox" value="0" '.(!$sandbox ? 'checked="checked"' : '').' /> <label class="t">'.$this->l('Live').'</label><br /><br />
																		<p class="hint clear" style="display: block; width: 501px;">'.$this->l('Select which payU Server you would like to use. Remember to change the default details to your own if you select the Live Server. ').'</p></div><br />
																				<br /><center><input type="submit" name="submitpayU" value="'.$this->l('Update settings').'" class="button" /></center>
																						</fieldset>
																						</form><br /><br />
																						';
		$this->_html .= '
				<fieldset class="width3">
				<legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
						'.$this->l('All fields with an * must be filled in for this module to work properly in both Live and Sandbox Modes.</br />
								Email confirmation is automatically sent to the customer.<br />
								In order to use this module in live mode, you need to first have a payU account. </br />
								You can use the sandbox without an account but only use it for testing perposes.').'<br />
										<b>'.$this->l('In order to use sand box save the default values').' : </b><br /><br />
												<br /><br />
												</fieldset>';
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return ;

		$customer = new Customer(intval($params['cart']->id_customer));
		$safe_key = Configuration::get('PAYU_SAFE_KEY');
		$merchantReference = Configuration::get('PAYU_MERCHANT_REF');
		$PAYU_PAYMENT_METHOD = Configuration::get('PAYU_PAYMENT_METHOD');
		$PAYU_BILLING_CURRENCY = Configuration::get('PAYU_BILLING_CURRENCY');
		$PAYU_INVOICE = Configuration::get('PAYU_INVOICE');
		$PAYU_TRANSACTION_TYPE = Configuration::get('PAYU_TRANSACTION_TYPE');
		$baseUrl = self::getTransactionServer();
		$payURppUrl = $baseUrl.'/rpp.do?PayUReference=';
		$apiVersion = 'ONE_ZERO';
			
		$currency = $this->getCurrency();

		if (!Validate::isLoadedObject($customer) OR !Validate::isLoadedObject($currency))
			return $this->l('payU error: (Invalid customer or currency)');
			
		$setTransactionArray = array();
		$setTransactionArray['Api'] = $apiVersion;
		$setTransactionArray['Safekey'] = $safe_key;
		$setTransactionArray['TransactionType'] = $PAYU_TRANSACTION_TYPE;

		$setTransactionArray['AdditionalInformation']['merchantReference'] = $merchantReference;
		//$setTransactionArray['AdditionalInformation']['notificationUrl'] = Context::getContext()->link->getModuleLink('payu', 'validation');
		$setTransactionArray['AdditionalInformation']['cancelUrl'] = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'index.php?controller=order';
		$setTransactionArray['AdditionalInformation']['returnUrl'] = Context::getContext()->link->getModuleLink('payu', 'validation');
		$setTransactionArray['AdditionalInformation']['supportedPaymentMethods'] = $PAYU_PAYMENT_METHOD;

		$setTransactionArray['Basket']['description'] = $PAYU_INVOICE;
		$setTransactionArray['Basket']['amountInCents'] =(int)((number_format(Tools::convertPrice($params['cart']->getOrderTotal(true, Cart::BOTH), $currency), 2, '.', '')) * 100);
		$setTransactionArray['Basket']['currencyCode'] = $PAYU_BILLING_CURRENCY;

		$setTransactionArray['Customer']['merchantUserId'] = stripslashes($params['cart']->id_customer);;
		$setTransactionArray['Customer']['email'] = stripslashes($customer->email);
		$setTransactionArray['Customer']['firstName'] = stripslashes($customer->firstname);
		$setTransactionArray['Customer']['lastName'] = stripslashes($customer->lastname);
		$setTransactionArray['Customer']['mobile'] = '0211234567';
		$setTransactionArray['Customer']['regionalId'] = '1234512345122';
		$setTransactionArray['Customer']['countryCode'] = '27';

		$returnData = $this->setSoapTransaction($setTransactionArray);
		$payUReference = $returnData['return']['payUReference'];
			
		$confirmPayment = 0;
		if($payUReference != ''){
			if(Configuration::get('PAYU_SANDBOX')){
				$payURppUrl .= $payUReference;
				Configuration::updateValue('CONFIRMATION_URL', $payURppUrl);
			} else {
				$payURppUrl .= $payUReference;
				Configuration::updateValue('CONFIRMATION_URL', $payURppUrl);
			}
			$confirmPayment = 1;
		}

		$this->smarty->assign(array(
				'this_path_pu' => $this->_path,
				'confirmUrl' => $payURppUrl,
				'confirmPayment' => $confirmPayment,
				'requestData' => $returnData
		));

		return $this->display(__FILE__, 'payment.tpl');
	}

	public function confirmationUrl()
	{

		$this->confirmationUrl = Configuration::GET('CONFIRMATION_URL');

		if($this->confirmationUrl != '')
		{
			Tools::redirect($this->confirmationUrl);
			exit;
		}
		return true;
	}

	private static function getTransactionServer()
	{
		if(Configuration::get('PAYU_SANDBOX'))
		{
			$baseUri = 'https://staging.payu.co.za';
		}
		else
		{
			$baseUri = 'https://secure.payu.co.za';
		}
		return $baseUri;
	}

	private static function getSoapHeaderXml()
	{
		$soap_username = Configuration::get('PAYU_SOAP_USERNAME');
		$soap_password = Configuration::get('PAYU_SOAP_PASSWORD');

		$headerXml  = '<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">';
		$headerXml .= '<wsse:UsernameToken wsu:Id="UsernameToken-9" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">';
		$headerXml .= '<wsse:Username>'.$soap_username.'</wsse:Username>';
		$headerXml .= '<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.$soap_password.'</wsse:Password>';
		$headerXml .= '</wsse:UsernameToken>';
		$headerXml .= '</wsse:Security>';

		return $headerXml;
	}

	public function getSoapTransaction($payUReference)
	{
		$apiVersion = 'ONE_ZERO';
		$safeKey = Configuration::get('PAYU_SAFE_KEY');
		$payu_ref = $payUReference;

		$getDataArray = array();
		$getDataArray['Api'] = $apiVersion;
		$getDataArray['Safekey'] = $safeKey;
		$getDataArray['AdditionalInformation']['payUReference'] = $payu_ref;

		$soapCallResult = self::getSoapSingleton()->getTransaction($getDataArray);
		return json_decode(json_encode($soapCallResult), true);
	}

	private function setSoapTransaction($trans_array)
	{
		$setTransactionArray = $trans_array;
		$soapCallResult = self::getSoapSingleton()->setTransaction($setTransactionArray);

		return json_decode(json_encode($soapCallResult), true);
	}

	public static function getSoapSingleton()
	{
		if(!self::$soapClient)
		{
			$headerXml = self::getSoapHeaderXml();
			$baseUrl = self::getTransactionServer();
			$soapWsdlUrl = $baseUrl.'/service/PayUAPI?wsdl';

			$headerbody = new SoapVar($headerXml, XSD_ANYXML, null, null, null);
			$soapHeader = new SOAPHeader(self::$ns, 'Security', $headerbody, true);

			$soap_client = new SoapClient($soapWsdlUrl, array('trace' => 1, 'exception' => 0));
			$soap_client->__setSoapHeaders($soapHeader);
				
			return self::$soapClient = $soap_client;
		}
		return self::$soapClient;
	}

	private function _updatePaymentStatusOfOrder($id_order,$params)
	{
			
		$objOrder = new Order($id_order);
			
		$history = new OrderHistory();
		$history->id_order = (int)$objOrder->id;

		$history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), (int)($objOrder->id)); //order status=3

		Db::getInstance()->execute('
				insert into `'._DB_PREFIX_.'order_history` (id_order_state,id_order,id_employee,date_add) values ("'.(int)Configuration::get('PS_OS_PAYMENT').'","'.(int)($objOrder->id).'","'.$params['objOrder']->id_customer.'","'.date("Y-m-d H:i:s").'")');

		return true;
	}
}
?>
