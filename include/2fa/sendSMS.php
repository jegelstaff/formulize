<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * send SMS messages with Twilio, return any errors
 */

function sendSMS($body, $phone) { 
	$id = "AC56176b80859cd83b4d615a2d2bcf878d"; // from Twilio account
	$token = "c4c5e41faa5746ed6eddc8882740cf65"; // from Twilio account
	$from = "+17098005969"; // from Twilio account
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
	return curl_error();
}