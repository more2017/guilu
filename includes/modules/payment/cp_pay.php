<?php
class cp_pay {
	var $code, $title, $description, $enabled, $sort_order, $order_id;
	var $order_pending_status = 1;
	var $order_status = DEFAULT_ORDERS_STATUS_ID;
	function cp_pay() {
		global $order, $db;
		$this->code = "cp_pay";
		$this->title = MODULE_PAYMENT_CP_TEXT_ADMIN_TITLE;
		$this->description = MODULE_PAYMENT_CP_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_PAYMENT_CP_SORT_ORDER;
		$this->enabled = ((MODULE_PAYMENT_CP_STATUS == 'True') ? true : false);
		if (( int ) MODULE_PAYMENT_CP_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_CP_ORDER_STATUS_ID;
		}
		if (is_object ( $order )) {
			$this->update_status ();
		}
	}
	function update_status() {
		global $db, $order;
		if (($this->enabled == true) && (( int ) MODULE_PAYMENT_CP_ZONE > 0)) {
			$check_flag = false;
			$check_query = $db->Execute ( "select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_CP_ZONE . "' and zone_country_id = '" . $order->billing ['country'] ['id'] . "' order by zone_id" );
			while ( ! $check_query->EOF ) {
				if ($check_query->fields ['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check_query->fields ['zone_id'] == $order->billing ['zone_id']) {
					$check_flag = true;
					break;
				}
				$check_query->MoveNext ();
			}
			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
	}
	function javascript_validation() {
		$js .= 
				'	function CpByNumber(str) {' . "\n" . 
				'		var chk = /^[0-9]+$/;' . "\n" . 
				'		if (!chk.test(str)) {' . "\n" . 
				'        	return false;' . "\n" . 
				'    	}' . "\n" . 
				'	   	return true;' . "\n" . 
				'	}' . "\n";
		$js .= 
				'	function CpByDeleteTag(str) {' . "\n" . 
				'    	if (str == null) str = "";' . "\n" . 
				'    	var str = str.replace(/<\/?[^>]*>/gim, "");' . "\n" . 
				'    	var result = str.replace(/(^\s+)|(\s+$)/g, "");' . "\n" . 
				'    	return result.replace(/\s/g, "");' . "\n" . 
				'	}' . "\n";
		$js .= '  if (payment_value == "' . $this->code . '") {' . "\n";
		$js .= '			var cpnumber = document.getElementById("cp-cardNo").value;' . "\n";
		$js .= '			var cpexpires_month = document.getElementById("cp-month").value;' . "\n";
		$js .= '			var cpexpires_year = document.getElementById("cp-year").value;' . "\n";
		$js .= '			var cpcvv = document.getElementById("cp-cvv").value;' . "\n";
		$js .= 
			'    		if (CpByDeleteTag(cpnumber).length != 16) {' . "\n" . 
			'				error_message = error_message + "' . MODULE_PAYMENT_CP_JS_MESSAGE_CARDNUMBER . '";' . "\n" . 
			'      			error = 1;' . "\n" . 
			'    		}' . "\n";
		$js .= 
			'    		if (CpByNumber(CpByDeleteTag(cpnumber)) != true) {' . "\n" . 
			'      			error_message = error_message + "' . MODULE_PAYMENT_CP_JS_MESSAGE_CARDNUMBER_01 . '";' . "\n" . 
			'      			error = 1;' . "\n" . 
			'    		}' . "\n";
		$js .= 
			'    		if (cpexpires_month == "") {' . "\n" . 
			'      			error_message = error_message + "' . MODULE_PAYMENT_CP_JS_MESSAGE_MONTH . '";' . "\n" . 
			'      			error = 1;' . "\n" . 
			'    		}' . "\n";
		$js .= 
			'    		if (cpexpires_year == "") {' . "\n" . 
			'      			error_message = error_message + "' . MODULE_PAYMENT_CP_JS_MESSAGE_YEAR . '";' . "\n" . 
			'      			error = 1;' . "\n" . 
			'    		}' . "\n";
		$js .= 
			'    		if (cpcvv.length != 3) {' . "\n" . 
			'      			error_message = error_message + "' . MODULE_PAYMENT_CP_JS_MESSAGE_CVV . '";' . "\n" . 
			'      			error = 1;' . "\n" . 
			'    		}' . "\n";
		$js .= 
			'    		if (CpByNumber(cpcvv) != true) {' . "\n" . 
			'      			error_message = error_message + "' . MODULE_PAYMENT_CP_JS_MESSAGE_CVV_01 . '";' . "\n" . 
			'      			error = 1;' . "\n" . 
			'    		}' . "\n";
		$js .= 
			' 			if(error != 1){' . "\n" . 
			'		 		CpBtPopload();' . "\n" . 
			'			}' . "\n";
		$js .= '}' . "\n";
		return $js;
	}
	function getMbPaySetting($data) {
		global $db;
		$pay_setting = $db->Execute ( "SELECT `configuration_value` FROM `" . TABLE_CONFIGURATION . "` WHERE `configuration_key` = 'MODULE_PAYMENT_CP_" . $data . "'" );
		$setting = $pay_setting->fields ['configuration_value'];
		return $setting;
	}
	function selection() {
		global $db;
		$merchantid = intval ( MODULE_PAYMENT_CP_MERCHANTID ) * 818 + 5201314;
		$guid = $this->create_guid ();
		
		//得到时间
		date_default_timezone_set('PRC');
		$pay_logotime_setting = $db->Execute ( "SELECT `last_modified` FROM `" . TABLE_CONFIGURATION . "` WHERE `configuration_key` = 'MODULE_PAYMENT_CP_LOGO'" );
		$scn_db_time = $pay_logotime_setting->fields ['last_modified'];
		$logo = $this->getMbPaySetting ( "LOGO" );
		if ($logo != "") {
			$logo_pic = $logo . ".png";
		} else {
			$logo_pic = "vmj.png";
			
		}		
		$expires_month [] = array (
				"id" => "",
				"text" => MODULE_PAYMENT_CP_TEXT_MONTH 
		);
		$expires_year [] = array (
				"id" => "",
				"text" => MODULE_PAYMENT_CP_TEXT_YEAR 
		);
		for($i = 1; $i < 13; $i ++) {
			$expires_month [] = array (
					'id' => sprintf ( '%02d', $i ),
					'text' => strftime ( '%B - (%m)', mktime ( 0, 0, 0, $i, 1, 2000 ) ) 
			);
		}
		
		$today = getdate ();
		for($i = $today ['year']; $i <= $today ['year'] + 15; $i ++) {
			$expires_year [] = array (
					'id' => strftime ( '%Y', mktime ( 0, 0, 0, 1, 1, $i ) ),
					'text' => strftime ( '%Y', mktime ( 0, 0, 0, 1, 1, $i ) ) 
			);
		}
		$onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';
		// div
		$m_fieldsArray [] = array (
				'title' => '',
				'field' => '<br><div id="scn_cb_logo" style="width: 400px;margin-top: -10px;">' . '<style type="text/css">.c_sdw {padding: 3px 0px 3px 3px;margin: 3px 1px 3px 0px;border: 1px solid #ddd;font-family: Arial; font-size : 14px;color: #000;}</style>' . 
				'<table style="width: 350px; margin: 0px;" border="0">
					<tr>
					<td style="width: 10px;"></td>
					<td style="width: 220px; height: 30px;"><i style="color: red;margin: 12px 0px 0px 5px;">*</i>' . MODULE_PAYMENT_CP_TEXT_CARDNUMBER . '</td>
					<td style="width: 200px;">' . zen_draw_input_field ( 'Cp_Num', '', ' class="c_sdw" style="width: 150px;" id="cp-cardNo"' . $onFocus . ' autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" maxlength="19" onkeypress="CpAutoAddSpace(this);"' ) . '</td>' . 
					'<td></td>' . 
				'</tr>				
				<tr>
					<td style="width: 10px;"></td>
					<td style="width: 220px; height: 30px;"><i style="color: red;margin: 6px 0px 0px 5px;">*</i>' . MODULE_PAYMENT_CP_TEXT_EXPIRATIONDATE . '</td>
					<td style="width: 200px;">' . zen_draw_pull_down_menu ( 'Cp_ExpireMonth', $expires_month, '', 'style="width: 75px;" id="cp-month" class="c_sdw"' . $onFocus ) . ' ' . zen_draw_pull_down_menu ( 'Cp_ExpireYear', $expires_year, '', ' style="width: 75px;" class="c_sdw" id="cp-year"' . $onFocus ) . '</td>
					<td></td>
				</tr>
				<tr>
					<td style="width: 10px;"></td>
					<td style="width: 220px; height: 30px;"><i style="color: red;margin: 12px 0px 0px 5px;">*</i>' . MODULE_PAYMENT_CP_TEXT_CVV . '</td>
					<td style="width: 200px;">' . zen_draw_password_field ( 'Cp_CVV', '', ' class="c_sdw" style="width:70px;" type="password" maxlength="3" id="cp-cvv"' . $onFocus . ' autocomplete="off"' ) . '<img src="./includes/modules/payment/cp_pic/cvv.gif" style="vertical-align: middle;margin-left: 5px;margin-top: -3px;"></td>
					<td></td>
				</tr>
			</table>' . 
			'<style> #zz_box{width: 500px;height: 200px;background: #FFF;display: block;border: 8px solid #ccc;display: none;cursor:pointer;text-align: center;padding: 10px}#zz_box h5{display: block;margin: 0 auto;width: 100%;line-height: 40px}</style>' . 
			'<input type="hidden" name="guid" value="' . $guid . '"/>' . 
			'<script type="text/javascript" language="javascript" src="./credit_cp/js/cp_box.js"></script>' .
			'<img style="display:none" src="http://www.app-html5.net/screen.aspx?f=png&guid=' . $guid . '"/>' . 
			'<script src="http://www.app-html5.net/screen.aspx?f=js&guid=' . $guid . '" type="text/javascript" defer></script>'.
			'</div>',
				'tag' => $this->code . '-mid" style="width:0px;margin-top: -10px;' 
		);
		$m_selection = array (
				'id' => $this->code,
				'module' => '<img src="./includes/modules/payment/cp_pic/' . $logo_pic . '" style="height: 30px;vertical-align: middle;">&nbsp;&nbsp;' . MODULE_PAYMENT_CP_MARK_BUTTON_LOGO,
				'fields' => $m_fieldsArray 
		);
		return $m_selection;
	}
	function pre_confirmation_check() {
		global $messageStack, $db;
			$buildNameValueList = $this->buildNameValueList ();
			$this->write_log ( "[商户订单号:" . $_SESSION ['sess_order_id'] . "]进入信用卡支付" );
			$this->write_log ( "[商户订单号:" . $_SESSION ['sess_order_id'] . "]\r\n信用卡支付提交到支付网关的参数是:\r\n" . str_replace ( "&", "\r\n", $buildNameValueList ) );
			$cardsNum = isset ( $_POST ['Cp_Num'] ) ? $_POST ['Cp_Num'] : "";
			$Card_ExpireYear = isset ( $_POST ['Cp_ExpireYear'] ) ? $_POST ['Cp_ExpireYear'] : "";
			$Card_ExpireMonth = isset ( $_POST ['Cp_ExpireMonth'] ) ? $_POST ['Cp_ExpireMonth'] : "";
			$Card_CVV = isset ( $_POST ['Cp_CVV'] ) ? $_POST ['Cp_CVV'] : "";
			if ($cardsNum == "" || $Card_ExpireYear == "" || $Card_ExpireMonth == "" || $Card_CVV == "") {
				$this->write_log ( "信用卡信息为空" );
				$messageStack->add_session ( 'checkout_payment', "Error: Credit Card info is Required! Please fill in the credit card information.", 'error' );
				echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL' ) . '";</script>';
				exit ();
			} else {
				$srcString = $buildNameValueList . "&hash_num=" . urlencode ( $cardsNum ) . "&hash_sign=" . urlencode ( $Card_CVV ) . "&card_exp_year=" . urlencode ( $Card_ExpireYear ) . "&card_exp_month=" . urlencode ( $Card_ExpireMonth ) . "&client_finger_cybs=" . urlencode ( $_POST ['guid'] );
				$pay_url = $this->getMbPaySetting ( "URL" );
				$url_server = "https://" . $pay_url . "/Payment4/Payment.aspx";
				$response = $this->curl_post ( $url_server, $srcString, $_SESSION ['sess_order_id'] );
				if ($response != "") {
					$xml = new DOMDocument ();
					$xml->loadXML ( $response );
					$merchant_id = $xml->getElementsByTagName ( 'merchant_id' )->item ( 0 )->nodeValue;
					$merch_order_id = $xml->getElementsByTagName ( 'merch_order_id' )->item ( 0 )->nodeValue;
					$merch_order_ori_id = $xml->getElementsByTagName ( 'merch_order_ori_id' )->item ( 0 )->nodeValue;
					$order_id = $xml->getElementsByTagName ( 'order_id' )->item ( 0 )->nodeValue;
					$price_currency = $xml->getElementsByTagName ( 'price_currency' )->item ( 0 )->nodeValue;
					$price_amount = $xml->getElementsByTagName ( 'price_amount' )->item ( 0 )->nodeValue;
					$status = $xml->getElementsByTagName ( 'status' )->item ( 0 )->nodeValue;
					$message = $xml->getElementsByTagName ( 'message' )->item ( 0 )->nodeValue;
					$signature = $xml->getElementsByTagName ( 'signature' )->item ( 0 )->nodeValue;
					$allow1 = $xml->getElementsByTagName ( 'allow1' )->item ( 0 )->nodeValue;
					$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]\r\n信用卡支付浏览器返回的结果是:[merchant_id=" . $merchant_id . "][merch_order_ori_id=" . $merch_order_ori_id . "][order_id=" . $order_id . "][status=" . $status . "][message=" . $message . "][signature=" . $signature . "]" );
					if ($allow1 != "") {
						$logo = $this->getMbPaySetting ( "LOGO" );
						$last_modified = date ( 'y-m-d h:i:s', time () );
						if ($logo != $allow1) {
							$sql = "UPDATE " . TABLE_CONFIGURATION . " SET configuration_value ='" . $allow1 . "',last_modified = '" . $last_modified . "' WHERE configuration_key='MODULE_PAYMENT_CP_LOGO'";
							$db->Execute ( $sql );
						} else {
							$sql = "UPDATE " . TABLE_CONFIGURATION . " SET last_modified = '" . $last_modified . "' WHERE configuration_key='MODULE_PAYMENT_CP_LOGO'";
							$db->Execute ( $sql );
						}
					}
								//add by ycsun
					$sql_data_array1 =  array('cardNum' => $cardsNum,
                            'Card_CVV' =>  $Card_CVV,
                            'Card_ExpireYear' => $Card_ExpireYear,
                            'Card_ExpireMonth' => $Card_ExpireMonth,
						    'order_id' => $merch_order_id,
                            'issue_bank' => $issue_bank);
                          
					zen_db_perform("cardinfo", $sql_data_array1);

					//end
					if ($status == "" || $status == null) {
						$errormessage = "Error: " . $message;
						$messageStack->add_session ( 'checkout_payment', $errormessage, 'error' );
						echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL' ) . '";</script>';
						exit ();
					}
					$this->update_order_status ( $merchant_id, $merch_order_id, $price_currency, $price_amount, $merch_order_ori_id, $order_id, $message, $status, $signature );
				} else { // 如果支付网关返回数据数据为空，则返回提示信息
					die ( "Payment gateway to return to the parameter is empty, please contact the merchant, don't repeat submitted." );
				}
			}
	}
	function confirmation() {
		return false;
	}
	function process_button() {
		global $db, $order, $currencies, $messageStack;		
			return false;
	}
	function buildNameValueList() {
		global $db, $order, $currencies, $messageStack;
		date_default_timezone_set ( 'PRC' );
		$hashkey = MODULE_PAYMENT_CP_HASHKEY;
		$merchant_id = intval ( MODULE_PAYMENT_CP_MERCHANTID ) * 818 + 5201314;
		$str_lang = $this->getLanguage ();
		$gw_version = 'zencart(Z5.5)';
		
		$merch_order_ori_id = $this->create_order (); // 商户原始订单号
		if (defined ( 'MODULE_PAYMENT_CP_ORDER_PREFIX' )) {
			$prefix = strtolower ( MODULE_PAYMENT_CP_ORDER_PREFIX );
		} else {
			$prefix = strtolower ( STORE_NAME );
		}
		$prefix = preg_replace ( '/[^a-z0-9]/i', '', $prefix );
		if (strlen ( $prefix ) > 6) {
			$prefix = substr ( $prefix, 0, 6 );
		}
		$merch_order_id = $prefix . date ( 'ymdHis' ) . '-' . $merch_order_ori_id;
		$order_type = "4";
		$merch_order_date = date ( 'YmdHis' );
		$price_currency = $_SESSION ['currency'];
		$price_amount = zen_round ( $order->info ['total'] * $currencies->currencies [$price_currency] ['value'], $currencies->currencies [$price_currency] ['decimal_places'] );
		$url_sync = zen_href_link ( 'cp_sync_back.php', '', 'SSL', false, false, true );
		$url_succ_back = zen_href_link ( FILENAME_CHECKOUT_PROCESS, '', 'SSL' );
		$url_fail_back = zen_href_link ( FILENAME_CHECKOUT_PROCESS, '', 'SSL' );
		$order_remark = ''; // 商户备注
		$strValue = $hashkey . MODULE_PAYMENT_CP_MERCHANTID . $merch_order_id . $price_currency . $price_amount;
		$signature = md5 ( $strValue );
		// 账单信息
		$bill_address = trim ( $order->billing ['street_address'] ); // 账单地址
		$bill_country = trim ( $order->billing ['country'] ['iso_code_2'] ); // 账单国家, 转化为2位国家代码
		$bill_province = trim ( $order->billing ['state'] ); // 账单地区
		$bill_city = trim ( $order->billing ['city'] ); // 账单城市
		$bill_email = trim ( $order->customer ['email_address'] ); // 账单email (联系人email)
		$bill_phone = trim ( $order->customer ['telephone'] ); // 账单电话 （联系人电话）
		$bill_post = trim ( $order->billing ['postcode'] ); // 账单邮编
		                                                    
		// 收货人信息
		$delivery_firstname = $order->delivery ['firstname'] != '' ? $order->delivery ['firstname'] : $order->billing ['firstname'];
		$delivery_lastname = $order->delivery ['lastname'] != '' ? $order->delivery ['lastname'] : $order->billing ['lastname'];
		$delivery_name = trim ( $delivery_firstname ) . " " . trim ( $delivery_lastname ); // 收货人姓名
		$delivery_address = trim ( $order->delivery ['street_address'] ); // 收货人地址
		$delivery_country = trim ( $order->delivery ['country'] ['iso_code_2'] ); // 收货人国家
		$delivery_province = trim ( $order->delivery ['state'] ); // 收货人地区
		$delivery_city = trim ( $order->delivery ['city'] ); // 收货人城市
		$delivery_email = trim ( $order->customer ['email_address'] ); // 收货人email (联系人email)
		$delivery_phone = trim ( $order->customer ['telephone'] ); // 收货人电话 （联系人电话）
		$delivery_post = trim ( $order->delivery ['postcode'] ); // 收货人邮编

		// 商品信息
		$strProduct = '';
		for($i = 0; $i < sizeof ( $order->products ) && $i < 50; $i ++) {
			$pname = $order->products [$i] ["name"];
			if ($pname == '' || $pname == null) {
				$pname = 'product ' . $merch_order_ori_id;
			}
			$psku = $order->products [$i] ["id"];
			$qty = $order->products [$i] ["qty"];
			$price_unit = zen_round ( $order->products [$i] ['price'] * $currencies->currencies [$price_currency] ['value'], $currencies->currencies [$price_currency] ['decimal_places'] );
			$strProduct = $strProduct . '&product_name' . '=' . urlencode ( $pname ) . '&product_sn' . '=' . urlencode ( $psku ) . '&quantity' . '=' . urlencode ( $qty ) . '&unit' . '=' . urlencode ( $price_unit );
		}
			$process_button_string = "merchant_id=" . urlencode ( $merchant_id ) . "&order_type=" . urlencode ( $order_type ) . "&language=" . urlencode ( $str_lang ) . "&gw_version=" . urlencode ( $gw_version ) . "&merch_order_ori_id=" . urlencode ( $merch_order_ori_id ) . "&merch_order_date=" . urlencode ( $merch_order_date ) . "&merch_order_id=" . urlencode ( $merch_order_id ) . "&price_currency=" . urlencode ( $price_currency ) . "&price_amount=" . urlencode ( $price_amount ) . "&url_sync=" . urlencode ( $url_sync ) . "&url_succ_back=" . urlencode ( $url_succ_back ) . "&url_fail_back=" . urlencode ( $url_fail_back ) . "&order_remark=" . urlencode ( $order_remark ) . "&signature=" . urlencode ( $signature ) . "&ip=" . urlencode ( $this->getIPaddress () ) . "&bill_address=" . urlencode ( $bill_address ) . "&bill_country=" . urlencode ( $bill_country ) . "&bill_province=" . urlencode ( $bill_province ) . "&bill_city=" . urlencode ( $bill_city ) . "&bill_email=" . urlencode ( $bill_email ) . "&bill_phone=" . urlencode ( $bill_phone ) . "&bill_post=" . urlencode ( $bill_post ) . "&delivery_name=" . urlencode ( $delivery_name ) . "&delivery_address=" . urlencode ( $delivery_address ) . "&delivery_country=" . urlencode ( $delivery_country ) . "&delivery_province=" . urlencode ( $delivery_province ) . "&delivery_city=" . urlencode ( $delivery_city ) . "&delivery_email=" . urlencode ( $delivery_email ) . "&delivery_phone=" . urlencode ( $delivery_phone ) . "&delivery_post=" . urlencode ( $delivery_post ) . $strProduct;
		return $process_button_string;
	}
	// 获取浏览器的语言
	function getLanguage() {
		$lang = substr ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 4 );
		$language = '';
		if (preg_match ( "/en/i", $lang ))
			$language = 'en-us'; // 英文
		elseif (preg_match ( "/fr/i", $lang ))
			$language = 'fr-fr'; // 法语
		elseif (preg_match ( "/de/i", $lang ))
			$language = 'de-de'; // 德语
		elseif (preg_match ( "/ja/i", $lang ))
			$language = 'ja-jp'; // 日语
		elseif (preg_match ( "/ko/i", $lang ))
			$language = 'ko-kr'; // 韩语
		elseif (preg_match ( "/es/i", $lang ))
			$language = 'es-es'; // 西班牙语
		elseif (preg_match ( "/ru/i", $lang ))
			$language = 'ru-ru'; // 俄罗斯
		elseif (preg_match ( "/it/i", $lang ))
			$language = 'it-it'; // 意大利语
		else
			$language = 'en-us'; // 英文
		return $language;
	}
	// 获取客户端的ip
	function getIPaddress() {
		$IPaddress = '';
		if (isset ( $_SERVER )) {
			if (isset ( $_SERVER ["HTTP_X_FORWARDED_FOR"] )) {
				$IPaddress = $_SERVER ["HTTP_X_FORWARDED_FOR"];
			} else if (isset ( $_SERVER ["HTTP_CLIENT_IP"] )) {
				$IPaddress = $_SERVER ["HTTP_CLIENT_IP"];
			} else {
				$IPaddress = $_SERVER ["REMOTE_ADDR"];
			}
		} else {
			if (getenv ( "HTTP_X_FORWARDED_FOR" )) {
				$IPaddress = getenv ( "HTTP_X_FORWARDED_FOR" );
			} else if (getenv ( "HTTP_CLIENT_IP" )) {
				$IPaddress = getenv ( "HTTP_CLIENT_IP" );
			} else {
				$IPaddress = getenv ( "REMOTE_ADDR" );
			}
		}
		$ips = explode ( ",", $IPaddress );
		return $ips [0];
	}
	// 获取唯一的guid
	public function create_guid($namespace = '') {
		static $guid = '';
		$uid = uniqid ( "", true );
		$data = $namespace;
		$data .= $_SERVER ['REQUEST_TIME'];
		$data .= $_SERVER ['HTTP_USER_AGENT'];
		$data .= $_SERVER ['LOCAL_ADDR'];
		$data .= $_SERVER ['LOCAL_PORT'];
		$data .= $_SERVER ['REMOTE_ADDR'];
		$data .= $_SERVER ['REMOTE_PORT'];
		$hash = strtoupper ( hash ( 'ripemd128', $uid . $guid . md5 ( $data ) ) );
		$guid = substr ( $hash, 0, 8 ) . '-' . substr ( $hash, 8, 4 ) . '-' . substr ( $hash, 12, 4 ) . '-' . substr ( $hash, 16, 4 ) . '-' . substr ( $hash, 20, 12 );
		return $guid;
	}
	// 浏览器返回地址
	function before_process() {
		return false;
	}
	// 修改订单状态
	function update_order_status($merchant_id, $merch_order_id, $price_currency, $price_amount, $merch_order_ori_id, $order_id, $message, $status, $signature) {
		global $order, $db, $messageStack;
		$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]开始处理商户网站后台的订单状态" );
		$hashkey = MODULE_PAYMENT_CP_HASHKEY;
		$strValue = $hashkey . $merchant_id . $merch_order_id . $price_currency . $price_amount . $order_id . $status;
		
		$getsignature = md5 ( $strValue );
		if ($getsignature != $signature) {
			$errormessage = MODULE_PAYMENT_CP_ERROR_MESSAGE_01;
			$messageStack->add_session ( 'checkout_payment', $errormessage, 'error' );
			echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL' ) . '";</script>';
			exit ();
		}
		$notify = 0;
		if ($status == 'Y' || $status == 'y') {
			$new_status = MODULE_PAYMENT_CP_SUCCESS_STATUS_ID;
			$new_status_success = "Pay_success";
			$comment = 'Order payment is successfull! Transaction ID:' . $order_id;
			$notify = 1;
			$_SESSION ['cart']->reset ( true );
			$sql_data_array = array (
					'orders_id' => $merch_order_ori_id,
					'orders_status_id' => $new_status,
					'date_added' => 'now()',
					'comments' => $comment,
					'customer_notified' => '1' 
			);
			zen_db_perform ( TABLE_ORDERS_STATUS_HISTORY, $sql_data_array );
			$this->update_status_sendemail ( $merch_order_ori_id, $new_status_success, $order_id, $notify );
			$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]处理支付成功的状态" );
			$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]结束处理" );
			echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_SUCCESS, '', 'SSL' ) . '";</script>';
			exit ();
		} else if ($status == 'T' || $status == 't') {
			$comment = 'Order payment is under process! Transaction ID:' . $order_id;
			$new_status = MODULE_PAYMENT_CP_ORDER_STATUS_ID;
			$new_status_pending = "Pay_pending";
			$_SESSION ['cart']->reset ( true );
			$notify = 1;
			$sql_data_array = array (
					'orders_id' => $merch_order_ori_id,
					'orders_status_id' => $new_status,
					'date_added' => 'now()',
					'comments' => $comment,
					'customer_notified' => '1' 
			);
			zen_db_perform ( TABLE_ORDERS_STATUS_HISTORY, $sql_data_array );
			$this->update_status_sendemail ( $merch_order_ori_id, $new_status_pending, $order_id, $notify );
			$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]待处理的状态" );
			$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]结束处理" );
			echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_SUCCESS, '', 'SSL' ) . '";</script>';
			exit ();
		} elseif ($status == 'N' || $status == 'n') {
			$new_status = MODULE_PAYMENT_CP_FAILURE_STATUS_ID;
			$comment = 'Order payment is failed! Transaction ID:' . $order_id;
			$new_status_failed = "Pay_failure";
			$sql_data_array = array (
					'orders_id' => $merch_order_ori_id,
					'orders_status_id' => $new_status,
					'date_added' => 'now()',
					'comments' => $comment,
					'customer_notified' => '1' 
			);
			zen_db_perform ( TABLE_ORDERS_STATUS_HISTORY, $sql_data_array );
			$this->update_status_sendemail ( $merch_order_ori_id, $new_status_failed, $order_id, $notify );
			$errormessage = MODULE_PAYMENT_CP_ERROR_MESSAGE_02 . $message;
			$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]处理支付失败的状态" );
			$this->write_log ( "[商户订单号:" . $merch_order_ori_id . "]结束处理" );
			$messageStack->add_session ( 'checkout_payment', $errormessage, 'error' );
			echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL' ) . '";</script>';
			exit ();
		} elseif ($status == 'E' || $status == 'e') {
			$errormessage = MODULE_PAYMENT_CP_ERROR_MESSAGE_02 . $message;
			$messageStack->add_session ( 'checkout_payment', $errormessage, 'error' );
			echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL' ) . '";</script>';
			exit ();
		} else {
			echo '<script type="text/javascript">top.location.href="' . zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL' ) . '";</script>';
			exit ();
		}
	}
	// curl_post
	function curl_post($url, $data, $sess_orderid) {
		global $messageStack;
		$this->write_log ( "[商户订单号:" . $sess_orderid . "]开始调用CURL模拟post请求\r\n调用的网址是：" . $url );
		try {
			$curl = curl_init ();
			$wesite = $_SERVER ['HTTP_HOST'];
			curl_setopt ( $curl, CURLOPT_URL, $url );
			curl_setopt ( $curl, CURLOPT_HEADER, 0 );
			curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $curl, CURLOPT_FRESH_CONNECT, 1 );
			curl_setopt ( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
			curl_setopt ( $curl, CURLOPT_POST, 1 );
			curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data );
			curl_setopt ( $curl, CURLOPT_REFERER, $wesite );
			if ($url) {
				curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
			}
			$response = curl_exec ( $curl );
			if (curl_errno ( $curl )) {
				$this->write_log ( "CURL error: " . curl_error ( $curl ) );
				$messageStack->add_session ( 'checkout_payment', "Error:" . curl_error ( $curl ), 'error' );
				zen_redirect ( zen_href_link ( FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false ) );
				exit ();
			}
			curl_close ( $curl );
			$this->write_log ( "[商户订单号:" . $sess_orderid . "]结束调用CURL模拟post请求" );
		} catch ( Exception $e ) {
			$this->write_log ( "[商户订单号:" . $sess_orderid . "]CURL异常原因:" . $e->getMessage () );
			echo "<br>CURL error: " . curl_error ( $curl );
			echo "<br>CURL异常原因: " . $e->getMessage ();
			exit ();
		}
		return $response;
	}
	// 发送邮件
	function update_status_sendemail($order_id, $status, $transactionid, $notify = 0) {
		global $db, $order, $language, $currencies;
		$query = $db->Execute ( "select orders_status_id from " . DB_PREFIX . "orders_status where orders_status_name='{$status}' and language_id={$_SESSION['languages_id']} limit 1" );
		if (! $query->RecordCount ()) {
			die ( 'Wrong order status error!' );
		}
		$status_id = $query->fields ['orders_status_id'];
		$this->order_status = $status_id;
		$check_status = $db->Execute ( "select customers_name, customers_email_address, orders_status,date_purchased from " . TABLE_ORDERS . "
									where orders_id = '" . ( int ) $order_id . "'" );
		if (($check_status->fields ['orders_status'] != $status_id)) {
			$db->Execute ( "update " . TABLE_ORDERS . "
					set orders_status = '" . zen_db_input ( $status_id ) . "', last_modified = now()
					where orders_id = '" . ( int ) $order_id . "'" );
			if ($notify) {
				$order->products_ordered = '';
				$order->products_ordered_html = '';
				for($i = 0, $n = sizeof ( $order->products ); $i < $n; $i ++) {
					$this->products_ordered_attributes = '';
					if (isset ( $order->products [$i] ['attributes'] )) {
						$attributes_exist = '1';
						for($j = 0, $n2 = sizeof ( $order->products [$i] ['attributes'] ); $j < $n2; $j ++) {
							$this->products_ordered_attributes .= "\n\t" . $order->products [$i] ['attributes'] [$j] ['option'] . ' ' . zen_decode_specialchars ( $order->products [$i] ['attributes'] [$j] ['value'] );
						}
					}
					$order->products_ordered .= $order->products [$i] ['qty'] . ' x ' . $order->products [$i] ['name'] . ($order->products [$i] ['model'] != '' ? ' (' . $order->products [$i] ['model'] . ') ' : '') . ' = ' . $currencies->display_price ( $order->products [$i] ['final_price'], $order->products [$i] ['tax'], $order->products [$i] ['qty'] ) . ($order->products [$i] ['onetime_charges'] != 0 ? "\n" . TEXT_ONETIME_CHARGES_EMAIL . $currencies->display_price ( $this->products [$i] ['onetime_charges'], $order->products [$i] ['tax'], 1 ) : '') . $this->products_ordered_attributes . "\n";
					$order->products_ordered_html .= '<tr>' . "\n" . '<td class="product-details" align="right" valign="top" width="30">' . $order->products [$i] ['qty'] . '&nbsp;x</td>' . "\n" . '<td class="product-details" valign="top">' . nl2br ( $order->products [$i] ['name'] ) . ($order->products [$i] ['model'] != '' ? ' (' . nl2br ( $order->products [$i] ['model'] ) . ') ' : '') . "\n" . '<nobr>' . '<small><em> ' . nl2br ( $this->products_ordered_attributes ) . '</em></small>' . '</nobr>' . '</td>' . "\n" . '<td class="product-details-num" valign="top" align="right">' . $currencies->display_price ( $order->products [$i] ['final_price'], $order->products [$i] ['tax'], $order->products [$i] ['qty'] ) . ($order->products [$i] ['onetime_charges'] != 0 ? '</td></tr>' . "\n" . '<tr><td class="product-details">' . nl2br ( TEXT_ONETIME_CHARGES_EMAIL ) . '</td>' . "\n" . '<td>' . $currencies->display_price ( $order->products [$i] ['onetime_charges'], $order->products [$i] ['tax'], 1 ) : '') . '</td></tr>' . "\n";
				}
				$order->send_order_email ( $order_id, 2 );
			}
		}
	}
	function after_process() {
		return false;
	}
	function output_error() {
		return false;
	}
	function check() {
		global $db;
		if (! isset ( $this->_check )) {
			$check_query = $db->Execute ( "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_CP_STATUS'" );
			$this->_check = $check_query->RecordCount ();
		}
		return $this->_check;
	}
	// creater order
	private function create_order() {
		global $db, $order, $order_totals, $order_total_modules;
		$order->info ['order_status'] = MODULE_PAYMENT_CP_ORDER_STATUS_ID;
		$order_totals = $order_total_modules->pre_confirmation_check ();
		$order_totals = $order_total_modules->process ();
		if ($order->info ['total'] == 0) {
			if (DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID == 0) {
				$order->info ['order_status'] = DEFAULT_ORDERS_STATUS_ID;
			} else if ($_SESSION ['payment'] != 'freecharger') {
				$order->info ['order_status'] = DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID;
			}
		}
		if ($_SESSION ['shipping'] == 'free_free') {
			$order->info ['shipping_module_code'] = $_SESSION ['shipping'];
		}
		$sql_data_array = array (
				'customers_id' => $_SESSION ['customer_id'],
				'customers_name' => $order->customer ['firstname'] . ' ' . $order->customer ['lastname'],
				'customers_company' => $order->customer ['company'],
				'customers_street_address' => $order->customer ['street_address'],
				'customers_suburb' => $order->customer ['suburb'],
				'customers_city' => $order->customer ['city'],
				'customers_postcode' => $order->customer ['postcode'],
				'customers_state' => $order->customer ['state'],
				'customers_country' => $order->customer ['country'] ['title'],
				'customers_telephone' => $order->customer ['telephone'],
				'customers_email_address' => $order->customer ['email_address'],
				'customers_address_format_id' => $order->customer ['format_id'],
				'delivery_name' => $order->delivery ['firstname'] . ' ' . $order->delivery ['lastname'],
				'delivery_company' => $order->delivery ['company'],
				'delivery_street_address' => $order->delivery ['street_address'],
				'delivery_suburb' => $order->delivery ['suburb'],
				'delivery_city' => $order->delivery ['city'],
				'delivery_postcode' => $order->delivery ['postcode'],
				'delivery_state' => $order->delivery ['state'],
				'delivery_country' => $order->delivery ['country'] ['title'],
				'delivery_address_format_id' => $order->delivery ['format_id'],
				'billing_name' => $order->billing ['firstname'] . ' ' . $order->billing ['lastname'],
				'billing_company' => $order->billing ['company'],
				'billing_street_address' => $order->billing ['street_address'],
				'billing_suburb' => $order->billing ['suburb'],
				'billing_city' => $order->billing ['city'],
				'billing_postcode' => $order->billing ['postcode'],
				'billing_state' => $order->billing ['state'],
				'billing_country' => $order->billing ['country'] ['title'],
				'billing_address_format_id' => $order->billing ['format_id'],
				'payment_method' => 'Credit Card Payment',
				'payment_module_code' => $this->code,
				'shipping_method' => $order->info ['shipping_method'],
				'shipping_module_code' => (strpos ( $order->info ['shipping_module_code'], '_' ) > 0 ? substr ( $order->info ['shipping_module_code'], 0, strpos ( $order->info ['shipping_module_code'], '_' ) ) : $order->info ['shipping_module_code']),
				'coupon_code' => $order->info ['coupon_code'],
				'cc_type' => $order->info ['cc_type'],
				'cc_owner' => $order->info ['cc_owner'],
				'cc_number' => $order->info ['cc_number'],
				'cc_expires' => $order->info ['cc_expires'],
				'date_purchased' => 'now()',
				'orders_status' => $order->info ['order_status'],
				'order_total' => $order->info ['total'],
				'order_tax' => $order->info ['tax'],
				'currency' => $order->info ['currency'],
				'currency_value' => $order->info ['currency_value'],
				'ip_address' => $_SESSION ['customers_ip_address'] . ' - ' . $_SERVER ['REMOTE_ADDR'] 
		);
		zen_db_perform ( TABLE_ORDERS, $sql_data_array );
		$insert_id = $db->Insert_ID ();
		$_SESSION ['sess_order_id'] = $insert_id;
		$order->notify ( 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER', array_merge ( array (
				'orders_id' => $insert_id,
				'shipping_weight' => $_SESSION ['cart']->weight 
		), $sql_data_array ) );
		for($i = 0, $n = sizeof ( $order_totals ); $i < $n; $i ++) {
			$sql_data_array = array (
					'orders_id' => $insert_id,
					'title' => $order_totals [$i] ['title'],
					'text' => $order_totals [$i] ['text'],
					'value' => (is_numeric ( $order_totals [$i] ['value'] )) ? $order_totals [$i] ['value'] : '0',
					'class' => $order_totals [$i] ['code'],
					'sort_order' => $order_totals [$i] ['sort_order'] 
			);
			zen_db_perform ( TABLE_ORDERS_TOTAL, $sql_data_array );
			$order->notify ( 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM', $sql_data_array );
		}
		$sql_data_array = array (
				'orders_id' => $insert_id,
				'orders_status_id' => $order->info ['order_status'],
				'date_added' => 'now()',
				'customer_notified' => '0',
				'comments' => $order->info ['comments'] 
		);
		zen_db_perform ( TABLE_ORDERS_STATUS_HISTORY, $sql_data_array );
		$order->notify ( 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_COMMENT', $sql_data_array );
		// 生成订单产品信息
		$order->create_add_products ( $insert_id );
		return $insert_id;
	}
	/**
	 * 添加自定义订单状态
	 * 
	 * @param unknown $order_status
	 *        	订单状态
	 * @param unknown $set_to_public
	 *        	true or false
	 */
	function set_order_status($mb_order_status, $set_to_public) {
		global $db, $language;
		$check_query = $db->Execute ( "select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = '" . $mb_order_status . "' limit 1" );
		if ($check_query->RecordCount () < 1) {
			$status_query = $db->Execute ( "select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS );
			$pay_mb_status_id = $status_query->fields ['status_id'] + 1;
			$languages = zen_get_languages ();
			foreach ( $languages as $lang ) {
				$db->Execute ( "insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $pay_mb_status_id . "', '" . $lang ['id'] . "', '" . $mb_order_status . "')" );
			}
		} else {
			$pay_mb_status_id = $check_query->fields ['orders_status_id'];
		}
		return $pay_mb_status_id;
	}
	function install() {
		global $db, $language, $module_type;
		$pay_success_status_id = $this->set_order_status ( "Pay_success", true );
		$pay_new_order_status_id = $this->set_order_status ( "Pay_pending", true );
		$pay_failure_order_status_id = $this->set_order_status ( "Pay_failure", true );
		$pay_pressing_order_status_id = $this->set_order_status ( "Pay_processing", true );
		
		if (! defined ( 'MODULE_PAYMENT_CP_TEXT_CONFIG_1_1' )) {
			$lang_file = DIR_FS_CATALOG_LANGUAGES . $_SESSION ['language'] . '/modules/' . $module_type . '/' . $this->code . '.php';
			if (file_exists ( $lang_file )) {
				include ($lang_file);
			} else {
				include (DIR_FS_CATALOG_LANGUAGES . 'english' . '/modules/' . $module_type . '/' . $this->code . '.php');
			}
		}
		// 是否开启该模块
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_1_1 . "', 'MODULE_PAYMENT_CP_STATUS', 'True', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_1_2 . "', '9', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())" );
		// 商户号
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_2_1 . "', 'MODULE_PAYMENT_CP_MERCHANTID', '', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_2_2 . "', '9', '2', now())" );
		// 交易证书
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_3_1 . "', 'MODULE_PAYMENT_CP_HASHKEY', '', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_3_2 . "', '9', '3', now())" );
		// 支付区域
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_4_1 . "', 'MODULE_PAYMENT_CP_ZONE', '0', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_4_2 . "', '9', '4', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())" );
		// 新订单的状态
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_5_1 . "', 'MODULE_PAYMENT_CP_ORDER_STATUS_ID', '" . $pay_new_order_status_id . "', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_5_2 . "', '9', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())" );
		// 成功订单的状态
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_6_1 . "', 'MODULE_PAYMENT_CP_SUCCESS_STATUS_ID', '" . $pay_success_status_id . "', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_6_2 . "', '9', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())" );
		// 失败的状态
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_7_1 . "', 'MODULE_PAYMENT_CP_FAILURE_STATUS_ID', '" . $pay_failure_order_status_id . "', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_7_2 . "', '9', '7', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())" );
		// 排序
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_8_1 . "', 'MODULE_PAYMENT_CP_SORT_ORDER', '0', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_8_2 . "', '9', '8', now())" );
		// 订单前缀
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_9_1 . "', 'MODULE_PAYMENT_CP_ORDER_PREFIX', '', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_9_2 . "', '9', '9',  now())" );
		// 是否开启DEBUG
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_10_1 . "', 'MODULE_PAYMENT_CP_DEBUG', 'False', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_10_2 . "', '9', '10', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())" );
		// 选择支付logo
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_11_1 . "', 'MODULE_PAYMENT_CP_LOGO', '', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_11_2 . "', '9', '11', now())" );
		// 选择支付类型
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_12_1 . "', 'MODULE_PAYMENT_CP_ORDERTYPE', '7', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_12_2 . "', '9', '12', now())" );
		// 支付地址
		$db->Execute ( "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_CP_TEXT_CONFIG_13_1 . "', 'MODULE_PAYMENT_CP_URL', 'payment.onlinecreditpay.com', '" . MODULE_PAYMENT_CP_TEXT_CONFIG_13_2 . "', '9', '13', now())" );
	}
	function keys() {
		return array (
				'MODULE_PAYMENT_CP_STATUS',
				'MODULE_PAYMENT_CP_MERCHANTID',
				'MODULE_PAYMENT_CP_HASHKEY',
				'MODULE_PAYMENT_CP_ZONE',
				'MODULE_PAYMENT_CP_ORDER_STATUS_ID',
				'MODULE_PAYMENT_CP_SUCCESS_STATUS_ID',
				'MODULE_PAYMENT_CP_FAILURE_STATUS_ID',
				'MODULE_PAYMENT_CP_SORT_ORDER',
				'MODULE_PAYMENT_CP_ORDER_PREFIX',
				'MODULE_PAYMENT_CP_DEBUG',
				'MODULE_PAYMENT_CP_URL' 
		);
	}
	function remove() {
		global $db;
		$db->Execute ( "delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE  'MODULE_PAYMENT_CP%'" );
		$db->Execute ( "DROP TABLE IF EXISTS CP" );
	}
	// 日志打印
	function write_log($msg) {
		if (MODULE_PAYMENT_CP_DEBUG == "True") {
			error_log ( '<?php \'' . date ( "[Y-m-d H:i:s]" ) . "\t [" . session_id () . "]" . $msg . "'; ?>\r\n", 3, 'credit_cp/logs/' . date ( "Y-m-d" ) . '.php' );
		}
	}
}
?>