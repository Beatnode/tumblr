<?php namespace Beatnode\Tumblr;

class Client extends \Tumblr\API\Client{

	protected $requestHandler;
	private $apiKey;

	public function __construct($consumerKey, $consumerSecret = null, $token = null, $secret = null)
	{
		parent::__construct($consumerKey, $consumerSecret, $token, $secret);
		$this->requestHandler = new RequestHandler();
		$this->setConsumer($consumerKey, $consumerSecret);
	}

	/**
     * Set the consumer for this client
     *
     * @param string $consumerKey    the consumer key
     * @param string $consumerSecret the consumer secret
     */
    public function setConsumer($consumerKey, $consumerSecret)
    {
        $this->apiKey = $consumerKey;
        $this->requestHandler->setConsumer($consumerKey, $consumerSecret);
    }

	/**
	 * Get the access token, oh how I hate OAuth 1.0
	 * @param string $oauth_callback Optional callback URL
	 * @return null
	 */

	public function getRequestToken($oauth_callback=null)
	{
		$response = $this->requestHandler->request('GET', 'oauth/request_token', array(
			'url' => 'http://www.tumblr.com'
		));

		$body = (string) $response->body;
		parse_str($body, $token);

		$_SESSION['oauth_token_tumblr'] = $token['oauth_token'];
		$_SESSION['oauth_token_secret_tumblr'] = $token['oauth_token_secret'];
	}

	/**
	 * Get authorization URI
	 * @return string
	 */

	public function getAuthorizationUri()
	{
		return 'http://www.tumblr.com/oauth/authorize?oauth_token='.$_SESSION['oauth_token_tumblr'];
	}

	/**
     * Set the token for this client
     *
     * @param string $token  the oauth token
     * @param string $secret the oauth secret
     */
    public function setToken($token, $secret)
    {
        $this->requestHandler->setToken($token, $secret);
    }

	/**
	 * Get the access token
	 * @return string
	 */

	public function getAccessToken($oauth_verifier)
	{

		$this->requestHandler->setToken($_SESSION['oauth_token_tumblr'], $_SESSION['oauth_token_secret_tumblr']);

    	$response = $this->requestHandler->request('GET', 'oauth/access_token', array(
			'url' => 'http://www.tumblr.com',
			'oauth_verifier' => $oauth_verifier,
		));

		$body = (string) $response->body;
		parse_str($body, $token);

		$this->setToken($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

}