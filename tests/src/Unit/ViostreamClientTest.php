<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\viostream\Client\ViostreamClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\viostream\Client\ViostreamClient
 * @group viostream
 */
class ViostreamClientTest extends TestCase {

  /**
   * The mock HTTP client.
   */
  protected ClientInterface&MockObject $httpClient;

  /**
   * The mock config factory.
   */
  protected ConfigFactoryInterface&MockObject $configFactory;

  /**
   * The mock logger channel factory.
   */
  protected LoggerChannelFactoryInterface&MockObject $loggerFactory;

  /**
   * The mock logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * The mock immutable config.
   */
  protected ImmutableConfig&MockObject $config;

  /**
   * The client under test.
   */
  protected ViostreamClient $client;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->loggerFactory->method('get')
      ->with('viostream')
      ->willReturn($this->logger);

    $this->configFactory->method('get')
      ->with('viostream.settings')
      ->willReturn($this->config);

    $this->client = new ViostreamClient(
      $this->httpClient,
      $this->configFactory,
      $this->loggerFactory
    );
  }

  /**
   * Sets up the config mock to return the given credentials.
   */
  protected function setCredentials(string $accessKey = 'VC-test123', string $apiKey = 'secret-key'): void {
    $this->config->method('get')
      ->willReturnMap([
        ['access_key', $accessKey],
        ['api_key', $apiKey],
      ]);
  }

  /**
   * Creates a mock response.
   */
  protected function createMockResponse(int $statusCode, ?array $body = NULL): ResponseInterface&MockObject {
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn($statusCode);

    if ($body !== NULL) {
      $stream = $this->createMock(StreamInterface::class);
      $stream->method('getContents')->willReturn(json_encode($body));
      $response->method('getBody')->willReturn($stream);
    }

    return $response;
  }

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $this->assertInstanceOf(ViostreamClient::class, $this->client);
  }

  /**
   * @covers ::isConfigured
   */
  public function testIsConfiguredReturnsTrueWhenBothKeysSet(): void {
    $this->setCredentials();
    $this->assertTrue($this->client->isConfigured());
  }

  /**
   * @covers ::isConfigured
   */
  public function testIsConfiguredReturnsFalseWhenAccessKeyEmpty(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['access_key', ''],
        ['api_key', 'secret-key'],
      ]);
    $this->assertFalse($this->client->isConfigured());
  }

  /**
   * @covers ::isConfigured
   */
  public function testIsConfiguredReturnsFalseWhenApiKeyEmpty(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['access_key', 'VC-test123'],
        ['api_key', ''],
      ]);
    $this->assertFalse($this->client->isConfigured());
  }

  /**
   * @covers ::isConfigured
   */
  public function testIsConfiguredReturnsFalseWhenBothKeysEmpty(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['access_key', ''],
        ['api_key', ''],
      ]);
    $this->assertFalse($this->client->isConfigured());
  }

  /**
   * @covers ::isConfigured
   */
  public function testIsConfiguredReturnsFalseWhenKeysNull(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['access_key', NULL],
        ['api_key', NULL],
      ]);
    $this->assertFalse($this->client->isConfigured());
  }

  /**
   * @covers ::isAuthError
   */
  public function testIsAuthErrorDefaultsFalse(): void {
    $this->assertFalse($this->client->isAuthError());
  }

  /**
   * @covers ::isAuthError
   * @covers ::get
   */
  public function testIsAuthErrorTrueAfter401Response(): void {
    $this->setCredentials();

    $response = $this->createMockResponse(401);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->client->getAccountInfo();
    $this->assertTrue($this->client->isAuthError());
  }

  /**
   * @covers ::isAuthError
   * @covers ::get
   */
  public function testIsAuthErrorFalseAfterNon401ErrorResponse(): void {
    $this->setCredentials();

    $response = $this->createMockResponse(500);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->client->getAccountInfo();
    $this->assertFalse($this->client->isAuthError());
  }

  /**
   * @covers ::isAuthError
   * @covers ::get
   */
  public function testIsAuthErrorTrueAfter401GuzzleException(): void {
    $this->setCredentials();

    $request = $this->createMock(RequestInterface::class);
    $response = $this->createMockResponse(401);
    $exception = new RequestException('Unauthorized', $request, $response);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->client->getAccountInfo();
    $this->assertTrue($this->client->isAuthError());
  }

  /**
   * @covers ::isAuthError
   * @covers ::get
   */
  public function testIsAuthErrorFalseAfterNon401GuzzleException(): void {
    $this->setCredentials();

    $request = $this->createMock(RequestInterface::class);
    $exception = new RequestException('Connection failed', $request);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->client->getAccountInfo();
    $this->assertFalse($this->client->isAuthError());
  }

  /**
   * @covers ::isAuthError
   * @covers ::get
   */
  public function testIsAuthErrorResetsOnSubsequentRequest(): void {
    $this->setCredentials();

    // First request returns 401.
    $response401 = $this->createMockResponse(401);
    $expected = ['id' => 'uuid-123'];
    $response200 = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->exactly(2))
      ->method('request')
      ->willReturnOnConsecutiveCalls($response401, $response200);

    $this->client->getAccountInfo();
    $this->assertTrue($this->client->isAuthError());

    // Second request succeeds — flag should reset.
    $this->client->getAccountInfo();
    $this->assertFalse($this->client->isAuthError());
  }

  /**
   * @covers ::getAccountInfo
   * @covers ::get
   * @covers ::buildRequestOptions
   * @covers ::getConfig
   */
  public function testGetAccountInfoSuccess(): void {
    $this->setCredentials();

    $expected = ['id' => 'uuid-123', 'publicKey' => 'pk-abc', 'title' => 'Test Account'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/account/info',
        $this->callback(function (array $options) {
          return $options['auth'] === ['VC-test123', 'secret-key']
            && $options['headers']['Accept'] === 'application/json'
            && $options['timeout'] === 30
            && !isset($options['query']);
        })
      )
      ->willReturn($response);

    $result = $this->client->getAccountInfo();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getAccountInfo
   * @covers ::get
   */
  public function testGetAccountInfoNon200Status(): void {
    $this->setCredentials();

    $response = $this->createMockResponse(401);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->client->getAccountInfo();
    $this->assertNull($result);
  }

  /**
   * @covers ::getAccountInfo
   * @covers ::get
   */
  public function testGetAccountInfoGuzzleException(): void {
    $this->setCredentials();

    $request = $this->createMock(RequestInterface::class);
    $exception = new RequestException('Connection failed', $request);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->client->getAccountInfo();
    $this->assertNull($result);
  }

  /**
   * @covers ::listMedia
   * @covers ::get
   * @covers ::buildRequestOptions
   */
  public function testListMediaNoParams(): void {
    $this->setCredentials();

    $expected = ['listResult' => ['items' => [], 'totalItems' => 0]];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media',
        $this->callback(fn(array $o) => !isset($o['query']))
      )
      ->willReturn($response);

    $result = $this->client->listMedia();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listMedia
   * @covers ::get
   * @covers ::buildRequestOptions
   */
  public function testListMediaWithParams(): void {
    $this->setCredentials();

    $expected = ['listResult' => ['items' => [['id' => '1']], 'totalItems' => 1]];
    $response = $this->createMockResponse(200, $expected);

    $params = ['SearchTerm' => 'test', 'PageSize' => 10, 'PageNumber' => 2];

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media',
        $this->callback(fn(array $o) => isset($o['query']) && $o['query'] === $params)
      )
      ->willReturn($response);

    $result = $this->client->listMedia($params);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listMediaByIds
   */
  public function testListMediaByIds(): void {
    $this->setCredentials();

    $expected = ['items' => [['id' => 'a'], ['id' => 'b']]];
    $response = $this->createMockResponse(200, $expected);
    $mediaIds = ['uuid-a', 'uuid-b'];

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media/listbyids',
        $this->callback(fn(array $o) => $o['query'] === ['mediaIds' => $mediaIds])
      )
      ->willReturn($response);

    $result = $this->client->listMediaByIds($mediaIds);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getMediaDetail
   */
  public function testGetMediaDetailWithoutExpand(): void {
    $this->setCredentials();

    $expected = ['id' => 'abc', 'title' => 'My Video'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media/abc/detail',
        $this->callback(fn(array $o) => !isset($o['query']))
      )
      ->willReturn($response);

    $result = $this->client->getMediaDetail('abc');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getMediaDetail
   */
  public function testGetMediaDetailWithExpand(): void {
    $this->setCredentials();

    $expected = ['id' => 'abc', 'title' => 'My Video', 'download' => []];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media/abc/detail',
        $this->callback(fn(array $o) => $o['query'] === ['Expand' => 'downloads'])
      )
      ->willReturn($response);

    $result = $this->client->getMediaDetail('abc', 'downloads');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getMediaDetail
   */
  public function testGetMediaDetailUrlEncodesId(): void {
    $this->setCredentials();

    $response = $this->createMockResponse(200, ['id' => 'a/b']);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media/a%2Fb/detail',
        $this->anything()
      )
      ->willReturn($response);

    $this->client->getMediaDetail('a/b');
  }

  /**
   * @covers ::createMediaIngest
   * @covers ::post
   */
  public function testCreateMediaIngestWithoutReferenceId(): void {
    $this->setCredentials();

    $expected = ['ingestId' => 'ingest-123'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        ViostreamClient::API_BASE_URL . '/media/new',
        $this->callback(function (array $o) {
          return $o['json'] === [
            'sourceUrl' => 'https://example.com/video.mp4',
            'filename' => 'video',
            'extension' => '.mp4',
          ];
        })
      )
      ->willReturn($response);

    $result = $this->client->createMediaIngest('https://example.com/video.mp4', 'video', '.mp4');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::createMediaIngest
   * @covers ::post
   */
  public function testCreateMediaIngestWithReferenceId(): void {
    $this->setCredentials();

    $expected = ['ingestId' => 'ingest-456'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        ViostreamClient::API_BASE_URL . '/media/new',
        $this->callback(function (array $o) {
          return $o['json'] === [
            'sourceUrl' => 'https://example.com/video.mp4',
            'filename' => 'video',
            'extension' => '.mp4',
            'referenceId' => 'ref-001',
          ];
        })
      )
      ->willReturn($response);

    $result = $this->client->createMediaIngest('https://example.com/video.mp4', 'video', '.mp4', 'ref-001');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::post
   */
  public function testPostNon200Status(): void {
    $this->setCredentials();

    $response = $this->createMockResponse(400);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->client->createMediaIngest('https://example.com/v.mp4', 'v', '.mp4');
    $this->assertNull($result);
  }

  /**
   * @covers ::post
   */
  public function testPostGuzzleException(): void {
    $this->setCredentials();

    $request = $this->createMock(RequestInterface::class);
    $exception = new RequestException('Server error', $request);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->client->createMediaIngest('https://example.com/v.mp4', 'v', '.mp4');
    $this->assertNull($result);
  }

  /**
   * @covers ::getIngestStatus
   */
  public function testGetIngestStatus(): void {
    $this->setCredentials();

    $expected = ['ingestId' => 'ingest-123', 'status' => 'complete'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/media/new/status/ingest-123',
        $this->anything()
      )
      ->willReturn($response);

    $result = $this->client->getIngestStatus('ingest-123');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listChannels
   */
  public function testListChannels(): void {
    $this->setCredentials();

    $expected = ['listResult' => ['items' => []]];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', ViostreamClient::API_BASE_URL . '/channels', $this->anything())
      ->willReturn($response);

    $result = $this->client->listChannels();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listChannels
   */
  public function testListChannelsWithParams(): void {
    $this->setCredentials();

    $expected = ['listResult' => ['items' => [['id' => 'ch1']]]];
    $response = $this->createMockResponse(200, $expected);

    $params = ['PageSize' => 10];

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/channels',
        $this->callback(fn(array $o) => $o['query'] === $params)
      )
      ->willReturn($response);

    $result = $this->client->listChannels($params);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listChannelsByIds
   */
  public function testListChannelsByIds(): void {
    $this->setCredentials();

    $expected = ['items' => []];
    $response = $this->createMockResponse(200, $expected);
    $channelIds = ['ch-1', 'ch-2'];

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/channels/listbyids',
        $this->callback(fn(array $o) => $o['query'] === ['channelIds' => $channelIds])
      )
      ->willReturn($response);

    $result = $this->client->listChannelsByIds($channelIds);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getChannelDetail
   */
  public function testGetChannelDetailWithoutParams(): void {
    $this->setCredentials();

    $expected = ['id' => 'ch-1', 'title' => 'Channel 1'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/channels/ch-1/detail',
        $this->callback(fn(array $o) => !isset($o['query']))
      )
      ->willReturn($response);

    $result = $this->client->getChannelDetail('ch-1');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getChannelDetail
   */
  public function testGetChannelDetailWithParams(): void {
    $this->setCredentials();

    $expected = ['id' => 'ch-1', 'media' => []];
    $response = $this->createMockResponse(200, $expected);

    $params = ['PageSize' => 5];

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/channels/ch-1/detail',
        $this->callback(fn(array $o) => $o['query'] === $params)
      )
      ->willReturn($response);

    $result = $this->client->getChannelDetail('ch-1', $params);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listTags
   */
  public function testListTags(): void {
    $this->setCredentials();

    $expected = ['listResult' => ['items' => [['value' => 'tag1']]]];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', ViostreamClient::API_BASE_URL . '/tags', $this->anything())
      ->willReturn($response);

    $result = $this->client->listTags();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listTagsWithUsage
   */
  public function testListTagsWithUsage(): void {
    $this->setCredentials();

    $expected = ['listResult' => ['items' => [['value' => 'tag1', 'count' => 5]]]];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', ViostreamClient::API_BASE_URL . '/tags/usage', $this->anything())
      ->willReturn($response);

    $result = $this->client->listTagsWithUsage();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::listWhitelists
   */
  public function testListWhitelists(): void {
    $this->setCredentials();

    $expected = ['whitelists' => [['id' => 'wl-1']]];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', ViostreamClient::API_BASE_URL . '/whitelist', $this->anything())
      ->willReturn($response);

    $result = $this->client->listWhitelists();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getWhitelistDetail
   */
  public function testGetWhitelistDetail(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-1', 'title' => 'My Whitelist'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        ViostreamClient::API_BASE_URL . '/whitelist/wl-1/detail',
        $this->anything()
      )
      ->willReturn($response);

    $result = $this->client->getWhitelistDetail('wl-1');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::createWhitelist
   * @covers ::post
   */
  public function testCreateWhitelistWithoutDomains(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-new'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        ViostreamClient::API_BASE_URL . '/whitelist/create',
        $this->callback(fn(array $o) => $o['json'] === ['title' => 'Test WL'])
      )
      ->willReturn($response);

    $result = $this->client->createWhitelist('Test WL');
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::createWhitelist
   */
  public function testCreateWhitelistWithDomains(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-new'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'POST',
        ViostreamClient::API_BASE_URL . '/whitelist/create',
        $this->callback(fn(array $o) => $o['json'] === [
          'title' => 'Test WL',
          'domains' => ['example.com', 'test.com'],
        ])
      )
      ->willReturn($response);

    $result = $this->client->createWhitelist('Test WL', ['example.com', 'test.com']);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::addDomainsToWhitelist
   * @covers ::put
   */
  public function testAddDomainsToWhitelist(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-1', 'domains' => ['example.com']];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'PUT',
        ViostreamClient::API_BASE_URL . '/whitelist/wl-1/adddomains',
        $this->callback(fn(array $o) => $o['json'] === ['domains' => ['example.com']])
      )
      ->willReturn($response);

    $result = $this->client->addDomainsToWhitelist('wl-1', ['example.com']);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::removeDomainsFromWhitelist
   * @covers ::put
   */
  public function testRemoveDomainsFromWhitelist(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-1', 'domains' => []];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'PUT',
        ViostreamClient::API_BASE_URL . '/whitelist/wl-1/removedomains',
        $this->callback(fn(array $o) => $o['json'] === ['domains' => ['example.com']])
      )
      ->willReturn($response);

    $result = $this->client->removeDomainsFromWhitelist('wl-1', ['example.com']);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::addMediaToWhitelist
   * @covers ::put
   */
  public function testAddMediaToWhitelist(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-1'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'PUT',
        ViostreamClient::API_BASE_URL . '/whitelist/wl-1/addmedia',
        $this->callback(fn(array $o) => $o['json'] === ['mediaPublicKeys' => ['pk-1', 'pk-2']])
      )
      ->willReturn($response);

    $result = $this->client->addMediaToWhitelist('wl-1', ['pk-1', 'pk-2']);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::removeMediaFromWhitelist
   * @covers ::put
   */
  public function testRemoveMediaFromWhitelist(): void {
    $this->setCredentials();

    $expected = ['id' => 'wl-1'];
    $response = $this->createMockResponse(200, $expected);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'PUT',
        ViostreamClient::API_BASE_URL . '/whitelist/wl-1/removemedia',
        $this->callback(fn(array $o) => $o['json'] === ['mediaPublicKeys' => ['pk-1']])
      )
      ->willReturn($response);

    $result = $this->client->removeMediaFromWhitelist('wl-1', ['pk-1']);
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::put
   */
  public function testPutNon200Status(): void {
    $this->setCredentials();

    $response = $this->createMockResponse(500);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->client->addDomainsToWhitelist('wl-1', ['test.com']);
    $this->assertNull($result);
  }

  /**
   * @covers ::put
   */
  public function testPutGuzzleException(): void {
    $this->setCredentials();

    $request = $this->createMock(RequestInterface::class);
    $exception = new RequestException('Timeout', $request);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->client->addDomainsToWhitelist('wl-1', ['test.com']);
    $this->assertNull($result);
  }

  /**
   * @covers ::getAccountInfo
   */
  public function testApiBaseUrlConstant(): void {
    $this->assertSame('https://api.app.viostream.com/v3/api', ViostreamClient::API_BASE_URL);
  }

}
