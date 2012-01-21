<?php

require_once 'bitly_api.php';
require_once 'constants.php';

/**
 * bit.ly API class usage example
 */
class bitly_examples
{
	public static function xauth()
	{
		$xauth_test = new bitly_xauth(client_id, client_secret);

		$response = $xauth_test->requestData(test_user, test_password);

		return parse_str($response);
	}

	public static function shorten()
	{
		$shorten_test = new bitly_links(client_id, client_secret, oauth_token);

		$data = $shorten_test->shorten('http://www.cervon.net/');

		return json_decode($data, true);
	}
}

try
{
	$data = bitly_examples::shorten();

	var_dump($data);
}
catch (Exception $e)
{
	trigger_error($e->getMessage());
}

?>