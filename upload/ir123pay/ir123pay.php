<?php

session_start();
if ( isset( $_SESSION['ir123pay_merchant_id'] ) && isset( $_POST['ir123pay_callback_url'] ) ) {
	$item = $_POST['ir123pay_item'];
	if ( isset( $_SESSION[ 'ir123pay_amount' . $item ] ) ) {
		echo 'Loading...';
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

		$merchant_id  = $_SESSION['ir123pay_merchant_id'];
		$amount       = $_SESSION[ 'ir123pay_amount' . $item ] * 10000;
		$callback_url = urlencode( $_POST['ir123pay_callback_url'] . $item );

		$response = create( $merchant_id, $amount, $callback_url );
		$result   = json_decode( $response );
		if ( $result->status ) {
			echo '<script>window.location=("' . $result->payment_url . '");</script>';
			exit();
		} else {
			echo $result->message;
			exit();
		}
	} else {
		echo 'Plase Enter Valid Amount';
		exit();
	}
} else {
	echo 'Full Error';
	exit();
}
