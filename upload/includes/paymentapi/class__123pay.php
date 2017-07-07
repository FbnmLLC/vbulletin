<?php
if ( ! isset( $GLOBALS['vbulletin']->db ) ) {
	exit;
}

class vB_PaidSubscriptionMethod__123pay extends vB_PaidSubscriptionMethod {
	var $supports_recurring = false;
	var $display_feedback = true;

	function create( $merchant_id, $amount, $callback_url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, 'https://123pay.ir/api/v1/create/payment' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "merchant_id=$merchant_id&amount=$amount&callback_url=$callback_url" );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );
		curl_close( $ch );

		return $response;
	}

	function verify_payment() {
		$this->registry->input->clean_array_gpc( 'r', array(
			'item'   => TYPE_STR,
			'State'  => TYPE_STR,
			'RefNum' => TYPE_STR
		) );

		$this->State          = $_REQUEST['State'];
		$this->transaction_id = $_REQUEST['RefNum'];

		if ( ! empty( $this->registry->GPC['item'] ) && ! empty( $this->transaction_id ) ) {
			$this->paymentinfo = $this->registry->db->query_first( "
				SELECT paymentinfo.*, user.username
				FROM " . TABLE_PREFIX . "paymentinfo AS paymentinfo
				INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				WHERE hash = '" . $this->registry->db->escape_string( $this->registry->GPC['item'] ) . "'
			" );
			if ( ! empty( $this->paymentinfo ) ) {
				$sub = $this->registry->db->query_first( "SELECT * FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $this->paymentinfo['subscriptionid'] );

				$cost   = unserialize( $sub['cost'] );
				$amount = floor( $cost[0][ cost ][ usd ] * $this->settings['d2t'] ) * 10;


				$State  = $_REQUEST['State'];
				$RefNum = $_REQUEST['RefNum'];

				$response = $this->verify( $this->settings['plmid'], $RefNum );
				$result   = json_decode( $response );

				if ( $State == 'OK' && $result->status && $amount == $result->amount && $amount == $_REQUEST['amount_check'] ) {
					$this->paymentinfo['currency'] = 'usd';
					$this->paymentinfo['amount']   = $cost[0][ cost ][ usd ];
					$this->type                    = 1;

					return true;
				}
			}
		}
		$this->error = 'Duplicate transaction.';

		return false;
	}


	function generate_form_html( $hash, $cost, $currency, $subinfo, $userinfo, $timeinfo ) {
		global $vbphrase, $vbulletin, $show;

		$item = $hash;
		$cost = ( floor( $cost * $this->settings['d2t'] ) ) * 10;
		$api  = $this->settings['plmid'];

		$form['action'] = '_123pay.php';
		$form['method'] = 'POST';

		$settings =& $this->settings;

		$templater = vB_Template::create( 'subscription_payment__123pay' );
		$templater->register( 'API', $api );
		$templater->register( 'cost', $cost );
		$templater->register( 'item', $item );
		$templater->register( 'subinfo', $subinfo );
		$templater->register( 'settings', $settings );
		$templater->register( 'userinfo', $userinfo );
		$form['hiddenfields'] .= $templater->render();

		return $form;
	}
}

?>