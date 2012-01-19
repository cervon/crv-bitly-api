<?php

/**
 * Cervon simple bit.ly API access classes
 * 
 * @author Cervon Latvia SIA / Eduards Andersons <eduards@cervon.net>
 * @version 1.0b
 */

/**
 * Simple cURL wrapper for general use
 */
abstract class curl_wrapper
{
	private $curl, $options = array();
	private $url = false; # Boolean that indicates URL option has been set

	public function __construct($return = true)
	{
		$this->curl = curl_init();

		// Return or output response?
		if ($return)
		{
			$this->addOption(CURLOPT_RETURNTRANSFER);
			$this->addOption(CURLOPT_HEADER, false);
		}
	}

	public function __destruct()
	{
		curl_close($this->curl);
	}

	protected function addOption($option, $value = true)
	{
		$this->options[$option] = $value;
	}

	protected function post(array $data = array())
	{
		$this->addOption(CURLOPT_POST);
		$this->addOption(CURLOPT_POSTFIELDS, $data);
	}

	protected function setUrl($url)
	{
		$this->url = curl_setopt($this->curl, CURLOPT_URL, $url);
	}

	private function appendOptions()
	{
		foreach ($this->options AS $option => $value)
		{
			curl_setopt($this->curl, $option, $value);
		}
	}

	protected function doRequest()
	{
		if (!$this->url)
		{
			throw new curlException('URL not set!', 404);
		}

		$this->appendOptions();

		$return = curl_exec($this->curl);

		if ($errno = curl_errno($this->curl))
		{
			throw new curlException(curl_error($this->curl), $errno);
		}

		// bit.ly sometimes returns data with newline at the end
		return trim($return);
	}
}

/**
 * bit.ly API base class for API version 3
 * 
 * bit.ly docs: code.google.com/p/bitly-api/wiki/ApiDocumentation
 */
abstract class bitly extends curl_wrapper
{
	const api_hostname = 'api-ssl.bitly.com';

	const useragent = 'Cervon bit.ly API access v1.0b (github.com/cervon/crv-bitly-api)';

	const use_ssl = true;
	const cert_file = 'bitly_ca.crt'; # Certificate chain bundle

	const access_token_path = 'oauth/access_token';

	const shorten_path = 'v3/shorten';
	const expand_path = 'v3/expand';
	const validate_path = 'v3/validate';
	const clicks_path = 'v3/clicks';
	const referrers_path = 'v3/referrers';
	const countries_path = 'v3/countries';
	const clicks_by_minute_path = 'v3/clicks_by_minute';
	const clicks_by_day_path = 'v3/clicks_by_day';
	const bitly_pro_domain_path = 'v3/bitly_pro_domain';
	const lookup_path = 'v3/lookup';
	const info_path = 'v3/info';

	const user_clicks_path = 'v3/user/clicks';
	const user_referrers_path = 'v3/user/referrers';
	const user_countries_path = 'v3/user/countries';
	const user_realtime_links_path = 'v3/user/realtime_links';

	protected $client_id, $client_secret;

	private $params;

	public function __construct($client_id, $client_secret)
	{
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;

		parent::__construct();

		if (self::use_ssl && self::cert_file && file_exists(self::cert_file))
		{
			$this->addOption(CURLOPT_CAINFO, self::cert_file);
		}

		$this->addOption(CURLOPT_USERAGENT, self::useragent);
	}

	protected function setURLparams(array $params = array(), $oauth_token = false)
	{
		$parts = array();

		if ($oauth_token)
		{
			$params['access_token'] = $oauth_token;
		}

		foreach ($params AS $param => $value)
		{
			$param = rawurlencode($param);
			$value = rawurlencode($value);

			array_push($parts, sprintf('%s=%s', $param, $value));
		}

		$this->params = join('&', $parts);
	}

	protected function setEndpoint($endpoint)
	{
		$constant = sprintf('self::%s_path', $endpoint);

		if (!defined($constant))
		{
			throw new bitlyException(sprintf('Endpoint "%s" is not defined!', $endpoint), 400);
		}

		$protocol = (self::use_ssl) ? 'https' : 'http';

		$requestURL = sprintf('%s://%s/%s', $protocol, self::api_hostname, constant($constant));

		if ($this->params)
		{
			$requestURL = sprintf('%s?%s', $requestURL, $this->params);
		}

		$this->setUrl($requestURL);
	}
}

/**
 * bit.ly xAuth class
 * 
 * API authentication via XAuth must be requested by e-mailing api@bitly.com
 */
class bitly_xauth extends bitly
{
	public function requestData($username, $password)
	{
		$this->setEndpoint('access_token');

		$this->post(array(
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'x_auth_username' => $username,
			'x_auth_password' => $password,
		));

		return $this->doRequest();
	}
}

/**
 * Simple bit.ly link shorten class
 */
class bitly_links extends bitly
{
	protected $token;

	public function __construct($client_id, $client_secret, $oauth_token)
	{
		$this->token = $oauth_token;

		parent::__construct($client_id, $client_secret);
	}

	public function shorten($longUrl, $responseFormat = 'json')
	{
		$data = array(
			'format' => $responseFormat,
			'longUrl' => $longUrl,
		);

		$this->setURLparams($data, $this->token);

		$this->setEndpoint('shorten');

		return $this->doRequest();
	}
}

class curlException extends Exception {}
class bitlyException extends Exception {}

?>