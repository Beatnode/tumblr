<?php namespace Beatnode\Tumblr;

class RequestHandler extends \Tumblr\Api\RequestHandler{

    private $consumer;
    private $token;
    private $signatureMethod;

    /**
     * Instantiate a new RequestHandler
     */
    public function __construct()
    {
        $this->signatureMethod = new \Eher\OAuth\HmacSha1();
        $this->client = new \Guzzle\Http\Client(null, array(
            'redirect.disable' => true
        ));
    }

    /**
     * Set the consumer for this request handler
     *
     * @param string $key    the consumer key
     * @param string $secret the consumer secret
     */
    public function setConsumer($key, $secret)
    {
        $this->consumer = new \Eher\OAuth\Consumer($key, $secret);
    }

    /**
     * Set the token for this request handler
     *
     * @param string $token  the oauth token
     * @param string $secret the oauth secret
     */
    public function setToken($token, $secret)
    {
        $this->token = new \Eher\OAuth\Token($token, $secret);
    }

    public function request($method, $path, $options)
    {
        // Ensure we have options
        $options ?: array();

        // Take off the data param, we'll add it back after signing
        $file = isset($options['data']) ? $options['data'] : false;
        unset($options['data']);

        // Check for a URL option, if it exists then we'll override
        $url = "http://api.tumblr.com/$path";
        if(isset($options['url']))
        {
            $url = "{$options['url']}/$path";
            unset($options['url']);
        }

        $oauth = \Eher\OAuth\Request::from_consumer_and_token(
            $this->consumer, $this->token,
            $method, $url, $options
        );
        $oauth->sign_request($this->signatureMethod, $this->consumer, $this->token);
        $authHeader = $oauth->to_header();
        $pieces = explode(' ', $authHeader, 2);
        $authString = $pieces[1];

        if ($method === 'GET') {
            // GET requests get the params in the query string
            $request = $this->client->get($url, null);
            $request->addHeader('Authorization', $authString);
            $request->getQuery()->merge($options);
        } else {
            // POST requests get the params in the body, with the files added
            // and as multipart if appropriate
            $request = $this->client->post($url, null, $options);
            $request->addHeader('Authorization', $authString);
            if ($file) {
                $request->addPostFiles(array('data' => $file));
            }
        }

        // Guzzle throws errors, but we collapse them and just grab the
        // response, since we deal with this at the \Tumblr\Client level
        try {
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $response = $request->getResponse();
        }

        // Construct the object that the Client expects to see, and return it
        $obj = new \stdClass;
        $obj->status = $response->getStatusCode();
        $obj->body = $response->getBody();
        $obj->headers = $response->getHeaders();

        return $obj;
    }

}
