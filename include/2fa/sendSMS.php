<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * send SMS messages with Twilio, return any errors
 */

function sendSMS($body, $phone) { 
	$id = ""; // from Twilio account
	$token = ""; // from Twilio account
	$from = ""; // from Twilio account (with +1 at front, etc)
	$url = "https://api.twilio.com/2010-04-01/Accounts/$id/Messages.json";
	$to = "+1".preg_replace("/[^0-9]/", '', $phone); // force North America!
	$data = array (
		'From' => $from,
		'To' => $to,
		'Body' => $body,
	);
	$post = http_build_query($data);
	$x = curl_init($url);
	curl_setopt($x, CURLOPT_POST, true);
	curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($x, CURLOPT_USERPWD, "$id:$token");
	curl_setopt($x, CURLOPT_POSTFIELDS, $post);
	$y = curl_exec($x);
	return curl_error($x);
}