<?php

require_once('phpmailer/PHPMailerAutoload.php');				// send PHPMailer Path

/**----------------
* メール基本設定
-----------------*/
	mb_language("japanese");
	mb_internal_encoding("UTF-8");												// メール文字コード (レガシーの場合は = "ISO-2022-JP")
	$myXmailer = 'Gmail-Countermeasure';									// Xmailer名 (Gmail 対策)
	$to_name = mb_encode_mimeheader('送信元名前', 'utf-8'); // 文字パケ対策 (レガシーの場合は = 'ISO-2022-JP')

	$toemails = array();
	$toemails[] = array(
				'email' => 'user-account@domain.co.jp',					// Your Email Address 送信元メールアドレス記入
				'name' => $to_name															// Your Name
	);

// Form Processing Messages
	$message_success = 'お客様のメッセージは正常に受信されました。できるだけ早くご返信いたします。';

// Add this only if you use reCaptcha with your Contact Forms
	$recaptcha_secret = ''; // Your reCaptcha Secret

	$mail = new PHPMailer();

// If you intend you use SMTP, add your SMTP Code after this Line
if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if( $_POST['email'] != '' ) {

		$name     = isset( $_POST['name'] ) ? $_POST['name'] : '';
		$furigana = isset( $_POST['furigana'] ) ? $_POST['furigana'] : '';
		$email    = isset( $_POST['email'] ) ? $_POST['email'] : '';
		$phone    = isset( $_POST['tel'] ) ? $_POST['tel'] : '';
		$address  = isset( $_POST['address'] ) ? $_POST['address'] : '';
		$subject  = isset( $_POST['subject'] ) ? $_POST['subject'] : '';
		$message  = isset( $_POST['message'] ) ? $_POST['message'] : '';

		$name     = strip_tags($name);
		$furigana = strip_tags($furigana);
		$email    = strip_tags($email);
		$phone    = strip_tags($tel);
		$address  = strip_tags($address);
		$subject  = strip_tags($subject);
		$message  = strip_tags($message);

		$subject = isset($subject) ? $subject : 'お問い合わせフォームからのメッセージ';

		$subject = mb_encode_mimeheader( $subject, 'utf-8' );				// 文字化け対策エンコード (レガシーの場合 = 'ISO-2022-JP')
		$send_name = mb_encode_mimeheader( $name, 'utf-8' );				// 送信者名エンコード (レガシーの場合'ISO-2022-JP')

		$botcheck = $_POST['template-contactform-botcheck'];

		if( $botcheck == '' ) {

			$mail->SetFrom( $email , $send_name );
			$mail->AddReplyTo( $email , $send_name );
			foreach( $toemails as $toemail ) {
				$mail->AddAddress( $toemail['email'] , $toemail['name'] );
			}
			$mail->Subject = $subject;

			$name     = isset($name) ? "名前 : $name<br>"."\n" : '';
			$furigana = isset($furigana) ? "フリガナ : $furigana<br>"."\n" : '';
			$email    = isset($email) ? "Email : $email<br>"."\n" : '';
			$address  = isset($address) ? "住所 : $address<br>"."\n" : '';
			$phone    = isset($phone) ? "電話番号 : $phone<br>"."\n" : '';
			$subject  = isset($subject) ? "件名 : $subject<br>"."\n" : '';
			$message  = isset($message) ? "備考 : $message<br>"."\n" : '';

			$referrer = $_SERVER['HTTP_REFERER'] ? "\n\n".'<br><br>このフォームは : ' . $_SERVER['HTTP_REFERER'] . ' より送信されました' : '';

			$body = "$name $furigana $address $phone $email $subject $message $referrer";

			// Runs only when File Field is present in the Contact Form
			if ( isset( $_FILES['template-contactform-file'] ) && $_FILES['template-contactform-file']['error'] == UPLOAD_ERR_OK ) {
				$mail->IsHTML(true);
				$mail->AddAttachment( $_FILES['template-contactform-file']['tmp_name'], $_FILES['template-contactform-file']['name'] );
			}

			// Runs only when reCaptcha is present in the Contact Form
			if( isset( $_POST['g-recaptcha-response'] ) ) {
				$recaptcha_response = $_POST['g-recaptcha-response'];
				$response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $recaptcha_response );
				$g_response = json_decode( $response );
				if ( $g_response->success !== true ) {
					echo '{ "alert": "error", "message": "Captchaは検証出来ませんでした、もう一度お試しください。" }';
					die;
				}
			}

			// Uncomment the following Lines of Code if you want to Force reCaptcha Validation
			// if( !isset( $_POST['g-recaptcha-response'] ) ) {
			// 	echo '{ "alert": "error", "message": "Captcha not Submitted! Please Try Again." }';
			// 	die;
			// }

			$mail->MsgHTML( $body );
			$sendEmail = $mail->Send();

			if( $sendEmail == true ):
				echo '{ "alert": "success", "message": "' . $message_success . '" }';
			else:
				echo '{ "alert": "error", "message": "Email 予期しないエラーのため<strong>送信できませんでした</strong> 後でもう一度お試しください。<br /><br /><strong>Reason:</strong><br />' . $mail->ErrorInfo . '" }';
			endif;
		} else {
			echo '{ "alert": "error", "message": "Bot <strong>検出エラー</strong> クリアにして下さい。" }';
		}
	} else {
		echo '{ "alert": "error", "message": "すべてのフィールドを<strong>入力</strong>してもう一度お試しください。" }';
	}
} else {
	echo '{ "alert": "error", "message": "<strong>予期しないエラー</strong>が発生しました。 後でもう一度お試しください。" }';
}

?>