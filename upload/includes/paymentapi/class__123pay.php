<?php
if ( ! isset( $GLOBALS['vbulletin']->db ) ) {
	exit;
}

class vB_PaidSubscriptionMethod__123pay extends vB_PaidSubscriptionMethod {
	var $supports_recurring = false;
	var $display_feedback = true;

	function verify_payment() {
		@session_start();
		if ( isset( $_SESSION['_123pay_merchant_id'] ) && ( isset( $_REQUEST['State'] ) && $_REQUEST['State'] == 'OK' ) && isset( $_REQUEST['RefNum'] ) ) {
			$merchant_id = $_SESSION['_123pay_merchant_id'];
			$this->registry->input->clean_array_gpc( 'r', array( 'item' => TYPE_STR, 'au' => TYPE_STR ) );
			$this->transaction_id = $this->registry->GPC['item'];
			if ( ! empty( $this->registry->GPC['item'] ) ) {
				$this->paymentinfo = $this->registry->db->query_first( "
				SELECT paymentinfo.*, user.username
				FROM " . TABLE_PREFIX . "paymentinfo AS paymentinfo
				INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				WHERE hash = '" . $this->registry->db->escape_string( $this->registry->GPC['item'] ) . "'
			" );
				if ( ! empty( $this->paymentinfo ) ) {
					$sub    = $this->registry->db->query_first( "SELECT * FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $this->paymentinfo['subscriptionid'] );
					$cost   = unserialize( $sub['cost'] );
					$amount = floor( $cost[0][ cost ][ usd ] );
					$amount = $amount * 10000;
					$RefNum = $_REQUEST['RefNum'];
					$ch     = curl_init();
					curl_setopt( $ch, CURLOPT_URL, 'https://123pay.ir/api/v1/verify/payment' );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, "merchant_id=$merchant_id&RefNum=$RefNum" );
					curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					$response = curl_exec( $ch );
					curl_close( $ch );
					
					$result = json_decode( $response );
					if ( $result->status ) {
						$this->paymentinfo['currency'] = 'usd';
						$this->paymentinfo['amount']   = $cost[0][ cost ][ usd ];
						$this->type                    = 1;

						return true;
					} else {
						$this->error = 'Failed Transaction !';

						return false;
					}
				}
			}

		} else {
			$this->error = 'Error !';

			return false;
		}

	}

	function test() {
		@session_start();

		return true;
	}

	function generate_form_html( $hash, $cost, $currency, $subinfo, $userinfo, $timeinfo ) {
		$item = $hash;
		global $vbphrase, $vbulletin, $show;
		@session_start();
		$cost                                 = floor( $cost );
		$_SESSION[ '_123pay_amount' . $item ] = $cost;
		$form['action']                       = '_123pay/_123pay.php';
		$form['method']                       = 'POST';
		$settings                             = &$this->settings;
		$templater                            = vB_Template::create( '_123pay' );
		$templater->register( 'item', $item );
		$templater->register( 'userinfo', $userinfo );
		$form['hiddenfields'] .= $templater->render();

		return $form;
	}

}

?>