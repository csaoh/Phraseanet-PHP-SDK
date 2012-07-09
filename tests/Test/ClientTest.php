<?php

namespace Test;

use Guzzle;
use PhraseanetSDK\Client;
use PhraseanetSDK\Response;
use \PhraseanetSDK\HttpAdapter\Guzzle as GuzzleAdapter;
use Symfony\Component\HttpFoundation\Request;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $logger;

    public function setUp()
    {
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler(new \Monolog\Handler\NullHandler());

        $this->logger = $logger;
    }

    /**
     * @covers PhraseanetSDK\Client::__construct
     */
    public function testConstructor()
    {
        new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
    }

    /**
     * @covers PhraseanetSDK\Client::getAccessToken
     */
    public function testGetAccessToken()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $this->assertNull($client->getAccessToken());
    }

    /**
     * @covers PhraseanetSDK\Client::getHttpClient
     */
    public function testGetHttpClient()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $expected = $this->getGuzzleAdapter();
        $client->setHttpClient($expected);
        $this->assertEquals($expected, $client->getHttpClient());
    }

    /**
     * @covers PhraseanetSDK\Client::setAccessToken
     */
    public function testSetAccessToken()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $expected = '123456789';
        $client->setAccessToken($expected);
        $this->assertEquals($expected, $client->getAccessToken());
    }

    /**
     * @covers PhraseanetSDK\Client::setHttpClient
     */
    public function testSetHttpClient()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $expected = new GuzzleAdapter(new Guzzle\Http\Client(
                    'http://my.domain2.tld/',
                    array('version' => 2)
            ));
        $client->setHttpClient($expected);
        $this->assertEquals($expected, $client->getHttpClient());
    }

    /**
     * @covers PhraseanetSDK\Client::setGrantType
     * @covers PhraseanetSDK\Exception\InvalidArgumentException
     * @covers PhraseanetSDK\Exception\ExceptionInterface
     * @expectedException PhraseanetSDK\Exception\InvalidArgumentException
     */
    public function testSetGrantTypeException()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $client->setGrantType('badGrantType');
    }

    /**
     * @covers PhraseanetSDK\Client::getAuthorizationUrl
     * @covers PhraseanetSDK\Exception\RuntimeException
     * @expectedException PhraseanetSDK\Exception\RuntimeException
     */
    public function testgetAuthorizationUrlException()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $client->getAuthorizationUrl();
    }

    /**
     * @covers PhraseanetSDK\Client::getAuthorizationUrl
     * @covers PhraseanetSDK\Client::setGrantType
     * @covers PhraseanetSDK\Client::getGrantType
     * @covers PhraseanetSDK\Client::getGrantInformations
     * @covers PhraseanetSDK\Client::getUrlWithoutOauth2Parameters
     */
    public function testSetGrantTypeAndGetAuthorizationUrl()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);

        $host = 'dev.phrasea.net';
        $query = '/test.php';
        $params = array('key'   => 'value', 'scope' => 'scope_test');

        $request = new Request($params, array(), array(), array(), array(), array('SERVER_PORT'  => 80, 'HTTP_HOST'    => $host, 'REQUEST_URI'  => $query, 'QUERY_STRING' => 'key=value'));

        $client->setGrantType(Client::GRANT_TYPE_AUTHORIZATION, null, $request);

        $this->assertEquals(Client::GRANT_TYPE_AUTHORIZATION, $client->getGrantType());

        $grantInfos = $client->getGrantInformations();

        $this->assertArrayHasKey('redirect_uri', $grantInfos);
        $this->assertRegExp('/' . str_replace(array('.'), array('\.'), $host) . '\w*' . str_replace(array('.', '?', '/'), array('\.', '\?', '\\/'), $query) . '/', $grantInfos['redirect_uri']);

        $url = $client->getAuthorizationUrl(array('admin', 'superadmin'));

        $this->assertEquals('http://my.domain.tld/api/oauthv2/authorize?response_type=code&client_id=123456&redirect_uri=http%3A%2F%2Fdev.phrasea.net%2Ftest.php%3Fkey%3Dvalue&scope=admin+superadmin', $url);

        $this->assertEquals(Client::GRANT_TYPE_AUTHORIZATION, $client->getGrantType());
        $this->assertTrue(is_array($client->getGrantInformations()));
        $this->assertArrayHasKey('scope', $client->getGrantInformations());
        $this->assertArrayHasKey('redirect_uri', $client->getGrantInformations());
    }

    /**
     * @covers PhraseanetSDK\Client::retrieveAccessToken
     */
    public function testRetrieveAccessToken()
    {
        $request = new Request(array('code' => '123456789'));

        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('access_token')), $this->logger);
        $client->setGrantType(Client::GRANT_TYPE_AUTHORIZATION, array(), $request);
        $client->retrieveAccessToken($request);
        $this->assertEquals('987654321123456789', $client->getAccessToken());
    }

    /**
     * @covers PhraseanetSDK\Client::retrieveAccessToken
     * @covers PhraseanetSDK\Exception\AuthenticationException
     * @expectedException PhraseanetSDK\Exception\AuthenticationException
     */
    public function testRetrieveAccessTokenError()
    {
        $request = new Request(array('error' => 'invalid_uri'));

        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('access_token')), $this->logger);
        $client->setGrantType(Client::GRANT_TYPE_AUTHORIZATION);
        $client->retrieveAccessToken($request);
    }

    /**
     * @covers PhraseanetSDK\Client::retrieveAccessToken
     */
    public function testRetrieveAccessTokenNoCode()
    {
        $request = new Request();

        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('access_token')), $this->logger);
        $client->setGrantType(Client::GRANT_TYPE_AUTHORIZATION);
        $token = $client->retrieveAccessToken($request);
        $this->assertNull($token);
    }

    /**
     * @covers PhraseanetSDK\Client::logout
     */
    public function testLogout()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapter(), $this->logger);
        $client->setAccessToken('hello');
        $client->logout();
        $this->assertNull($client->getAccessToken());
    }

    /**
     * @covers PhraseanetSDK\Client::call
     */
    public function testPOSTCall200()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('200')), $this->logger);
        $client->setAccessToken("123456789");
        $response = $client->call('/path/to/ressource');

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(200, $response->getHttpStatusCode());
    }

    /**
     * @covers PhraseanetSDK\Client::call
     */
    public function testGETCall200()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('200')), $this->logger);
        $response = $client->call('/path/to/ressource', array('key' => 'value'), 'GET');

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(200, $response->getHttpStatusCode());
    }

    /**
     * @covers PhraseanetSDK\Client::call
     * @dataProvider methodProvider
     * @covers PhraseanetSDK\Exception\BadRequestException
     * @expectedException PhraseanetSDK\Exception\BadRequestException
     */
    public function testBadRequestException($method)
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('200')), $this->logger);
        $client->call('/path/to/ressource', array(), $method);
    }

    /**
     * @covers PhraseanetSDK\Client::call
     * @covers PhraseanetSDK\Exception\BadResponseException
     * @dataProvider httpCodeProvider
     * @expectedException PhraseanetSDK\Exception\BadResponseException
     */
    public function testBadResponseException($httpCode)
    {
        $plugin = new Guzzle\Http\Plugin\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response($httpCode));

        $httpClient = new Guzzle\Http\Client(
                'http://my.domain.tld',
                array('version' => 1)
        );
        $httpClient->getEventDispatcher()->addSubscriber($plugin);

        $client = new Client('123456', '654321', new GuzzleAdapter($httpClient), $this->logger);
        $client->call('/path/to/ressource');
    }

    /**
     * @covers PhraseanetSDK\Client::call
     */
    public function testForceNoException()
    {
        $client = new Client('123456', '654321', $this->getGuzzleAdapterWithResponse($this->getSampleResponse('401')), $this->logger);
        $response = $client->call('/path/to/ressource', array(), 'GET', false);

        $this->assertTrue($response instanceof Response);
    }

    public function methodProvider()
    {
        return array(
            array('HEAD'),
            array('DELETE'),
            array('PUT'),
            array('BLABLA'),
            array('OPTIONS'),
            array('TRACE')
        );
    }

    public function httpCodeProvider()
    {
        return array(
            array('400'),
            array('401'),
            array('403'),
            array('404'),
            array('405'),
            array('500'),
            array('503')
        );
    }

    private function getSampleResponse($filename)
    {
        $filename = __DIR__ . '/../ressources/response_samples/' . $filename . '.json';

        return file_get_contents($filename);
    }

    private function getGuzzleClient()
    {
        return new Guzzle\Http\Client(
                'http://my.domain.tld/',
                array('version' => 1)
        );
    }

    private function getGuzzleAdapter()
    {
        return new GuzzleAdapter($this->getGuzzleClient());
    }

    private function getGuzzleAdapterWithResponse($response)
    {
        $plugin = new Guzzle\Http\Plugin\MockPlugin();
        $plugin->addResponse(new Guzzle\Http\Message\Response(
                200
                , null
                , $response
            )
        );

        $clientHttp = $this->getGuzzleClient();
        $clientHttp->getEventDispatcher()->addSubscriber($plugin);

        return new GuzzleAdapter($clientHttp);
    }
}
