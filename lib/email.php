<?php
// Email sending functions

// Send email
function email_add($to, $subject, $body) {
	global $email_sender;
	global $email_reply_to;
	
	$headers = "From: $email_sender\r\n" .
    	"Reply-To: $email_reply_to\r\n";
	mail($to, $subject, $body, $headers);

	$message = [
		"to" => $to,
		"from" => $email_sender,
		"reply" => $email_reply_to,
		"subject" => $subject,
		"body" => $body,
	];
	
	write_log($message, 6);
}
