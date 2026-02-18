<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\viostream\Client\ViostreamClient;
use Drupal\viostream\Controller\ViostreamMediaBrowserController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\viostream\Controller\ViostreamMediaBrowserController
 * @group viostream
 */
class ViostreamMediaBrowserControllerTest extends TestCase {

  /**
   * The mock Viostream client.
   */
  protected MockObject $viostreamClient;

  /**
   * The controller under test.
   */
  protected ViostreamMediaBrowserController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->viostreamClient = $this->createMock(ViostreamClient::class);
    $this->controller = new ViostreamMediaBrowserController($this->viostreamClient);

    // Set up string translation so $this->t() works.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn(TranslatableMarkup $m) => $m->getUntranslatedString());
    $this->controller->setStringTranslation($translation);
  }

  /**
   * @covers ::search
   */
  public function testSearchNotConfigured(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $request = new Request();
    $response = $this->controller->search($request);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertSame(403, $response->getStatusCode());

    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame('API not configured', $data['error']);
  }

  /**
   * @covers ::search
   */
  public function testSearchWithDefaults(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['PageSize'] === 24
          && $params['PageNumber'] === 1
          && $params['SortColumn'] === 'CreatedDate'
          && $params['SortOrder'] === 'desc'
          && !isset($params['SearchTerm']);
      }))
      ->willReturn([
        'listResult' => [
          'items' => [['id' => 'a']],
          'totalItems' => 1,
          'totalPages' => 1,
          'pageNumber' => 1,
          'pageSize' => 24,
        ],
      ]);

    $request = new Request();
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame([['id' => 'a']], $data['items']);
    $this->assertSame(1, $data['totalItems']);
  }

  /**
   * @covers ::search
   */
  public function testSearchWithParams(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['PageSize'] === 10
          && $params['PageNumber'] === 2
          && $params['SortColumn'] === 'Title'
          && $params['SortOrder'] === 'asc'
          && $params['SearchTerm'] === 'test video';
      }))
      ->willReturn([
        'listResult' => [
          'items' => [],
          'totalItems' => 0,
          'totalPages' => 0,
          'pageNumber' => 2,
          'pageSize' => 10,
        ],
      ]);

    $request = new Request([
      'page_size' => '10',
      'page' => '2',
      'sort' => 'Title',
      'order' => 'asc',
      'search' => 'test video',
    ]);
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame([], $data['items']);
    $this->assertSame(0, $data['totalItems']);
  }

  /**
   * @covers ::search
   */
  public function testSearchApiFails(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('listMedia')->willReturn(NULL);
    $this->viostreamClient->method('isAuthError')->willReturn(FALSE);

    $request = new Request();
    $response = $this->controller->search($request);

    $this->assertSame(500, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame('API request failed', $data['error']);
  }

  /**
   * @covers ::search
   */
  public function testSearchApiAuthError(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('listMedia')->willReturn(NULL);
    $this->viostreamClient->method('isAuthError')->willReturn(TRUE);

    $request = new Request();
    $response = $this->controller->search($request);

    $this->assertSame(403, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame('Invalid API credentials', $data['error']);
  }

  /**
   * @covers ::search
   */
  public function testSearchWithMissingListResult(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('listMedia')->willReturn([]);

    $request = new Request();
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame([], $data['items']);
    $this->assertSame(0, $data['totalItems']);
  }

  /**
   * @covers ::search
   */
  public function testSearchPageNumberMinimumIsOne(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['PageNumber'] === 1;
      }))
      ->willReturn([
        'listResult' => [
          'items' => [],
          'totalItems' => 0,
          'totalPages' => 0,
          'pageNumber' => 1,
          'pageSize' => 24,
        ],
      ]);

    $request = new Request(['page' => '-3']);
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::search
   */
  public function testSearchResponseItemsContainOnlyWhitelistedFields(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('listMedia')->willReturn([
      'listResult' => [
        'items' => [
          [
            'id' => 'media-1',
            'key' => 'abc123',
            'title' => 'Test Video',
            'description' => 'A video',
            'thumbnail' => 'https://example.com/thumb.jpg',
            'status' => 'active',
            'duration' => '2:30',
            'totalViews' => 42,
            'internalId' => 'secret-internal-id',
            'owner' => 'admin@example.com',
            'uploadUrl' => 'https://secret.com/upload',
          ],
        ],
        'totalItems' => 1,
        'totalPages' => 1,
        'pageNumber' => 1,
        'pageSize' => 24,
      ],
    ]);

    $request = new Request();
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertCount(1, $data['items']);

    $item = $data['items'][0];
    // Whitelisted fields are present.
    $expected_keys = ['id', 'key', 'title', 'description', 'thumbnail', 'status', 'duration', 'totalViews'];
    $this->assertSame($expected_keys, array_keys($item));

    // Non-whitelisted fields are absent.
    $this->assertArrayNotHasKey('internalId', $item);
    $this->assertArrayNotHasKey('owner', $item);
    $this->assertArrayNotHasKey('uploadUrl', $item);
  }

  /**
   * @covers ::detail
   */
  public function testDetailNotConfigured(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $response = $this->controller->detail('media-123');

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertSame(403, $response->getStatusCode());
  }

  /**
   * @covers ::detail
   */
  public function testDetailMediaNotFound(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn(NULL);
    $this->viostreamClient->method('isAuthError')->willReturn(FALSE);

    $response = $this->controller->detail('nonexistent');

    $this->assertSame(404, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame('Media not found', $data['error']);
  }

  /**
   * @covers ::detail
   */
  public function testDetailApiAuthError(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn(NULL);
    $this->viostreamClient->method('isAuthError')->willReturn(TRUE);

    $response = $this->controller->detail('media-123');

    $this->assertSame(403, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame('Invalid API credentials', $data['error']);
  }

  /**
   * @covers ::detail
   */
  public function testDetailWithDownloadDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'id' => 'media-123',
      'title' => 'Test Video',
      'download' => ['width' => 1920, 'height' => 1080],
    ]);

    $response = $this->controller->detail('media-123');

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertSame(1920, $data['videoWidth']);
    $this->assertSame(1080, $data['videoHeight']);
    $this->assertSame('media-123', $data['id']);
  }

  /**
   * @covers ::detail
   */
  public function testDetailWithProgressiveDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'id' => 'media-456',
      'progressive' => [
        ['width' => 640, 'height' => 360],
        ['width' => 1280, 'height' => 720],
      ],
    ]);

    $response = $this->controller->detail('media-456');

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    // Should pick highest resolution.
    $this->assertSame(1280, $data['videoWidth']);
    $this->assertSame(720, $data['videoHeight']);
  }

  /**
   * @covers ::detail
   */
  public function testDetailWithNoDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'id' => 'media-789',
      'title' => 'Audio Only',
    ]);

    $response = $this->controller->detail('media-789');

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertNull($data['videoWidth']);
    $this->assertNull($data['videoHeight']);
  }

  /**
   * @covers ::detail
   */
  public function testDetailProgressiveWithMissingDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'id' => 'media-abc',
      'progressive' => [
        ['url' => 'https://example.com/stream'],
      ],
    ]);

    $response = $this->controller->detail('media-abc');

    $data = json_decode($response->getContent(), TRUE);
    $this->assertNull($data['videoWidth']);
    $this->assertNull($data['videoHeight']);
  }

  /**
   * @covers ::detail
   */
  public function testDetailEmptyProgressive(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'id' => 'media-def',
      'progressive' => [],
    ]);

    $response = $this->controller->detail('media-def');

    $data = json_decode($response->getContent(), TRUE);
    $this->assertNull($data['videoWidth']);
    $this->assertNull($data['videoHeight']);
  }

  /**
   * @covers ::search
   */
  public function testSearchInvalidSortColumnFallsBackToDefault(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['SortColumn'] === 'CreatedDate';
      }))
      ->willReturn([
        'listResult' => [
          'items' => [],
          'totalItems' => 0,
          'totalPages' => 0,
          'pageNumber' => 1,
          'pageSize' => 24,
        ],
      ]);

    $request = new Request(['sort' => 'MaliciousColumn']);
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::search
   */
  public function testSearchInvalidSortOrderFallsBackToDefault(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['SortOrder'] === 'desc';
      }))
      ->willReturn([
        'listResult' => [
          'items' => [],
          'totalItems' => 0,
          'totalPages' => 0,
          'pageNumber' => 1,
          'pageSize' => 24,
        ],
      ]);

    $request = new Request(['order' => 'DROP TABLE']);
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::search
   */
  public function testSearchPageSizeCappedAt100(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['PageSize'] === 100;
      }))
      ->willReturn([
        'listResult' => [
          'items' => [],
          'totalItems' => 0,
          'totalPages' => 0,
          'pageNumber' => 1,
          'pageSize' => 100,
        ],
      ]);

    $request = new Request(['page_size' => '9999']);
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::search
   */
  public function testSearchPageSizeMinimumIsOne(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['PageSize'] === 1;
      }))
      ->willReturn([
        'listResult' => [
          'items' => [],
          'totalItems' => 0,
          'totalPages' => 0,
          'pageNumber' => 1,
          'pageSize' => 1,
        ],
      ]);

    $request = new Request(['page_size' => '-5']);
    $response = $this->controller->search($request);

    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::detail
   */
  public function testDetailResponseContainsOnlyWhitelistedFields(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'id' => 'media-wl',
      'key' => 'abc123',
      'title' => 'My Video',
      'description' => 'A test video',
      'thumbnail' => 'https://example.com/thumb.jpg',
      'status' => 'active',
      'duration' => 120,
      'download' => ['width' => 1920, 'height' => 1080, 'url' => 'https://secret.com/download'],
      'progressive' => [['width' => 640, 'height' => 360]],
      'internalField' => 'should-not-appear',
      'owner' => 'admin@example.com',
    ]);

    $response = $this->controller->detail('media-wl');

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);

    // Whitelisted fields are present.
    $expected_keys = ['id', 'key', 'title', 'description', 'thumbnail', 'status', 'duration', 'videoWidth', 'videoHeight'];
    $this->assertSame($expected_keys, array_keys($data));

    // Non-whitelisted fields are absent.
    $this->assertArrayNotHasKey('download', $data);
    $this->assertArrayNotHasKey('progressive', $data);
    $this->assertArrayNotHasKey('internalField', $data);
    $this->assertArrayNotHasKey('owner', $data);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $this->assertInstanceOf(ViostreamMediaBrowserController::class, $this->controller);
  }

  /**
   * @covers ::browse
   */
  public function testBrowseNotConfigured(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $result = $this->controller->browse();

    $this->assertIsArray($result);
    $this->assertTrue($result['#not_configured']);
    $this->assertSame('viostream_media_browser', $result['#theme']);
  }

  /**
   * @covers ::browse
   */
  public function testBrowseConfiguredWithResults(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->expects($this->once())
      ->method('listMedia')
      ->with($this->callback(function (array $params) {
        return $params['PageSize'] === 24
          && $params['PageNumber'] === 1
          && $params['SortColumn'] === 'CreatedDate'
          && $params['SortOrder'] === 'desc';
      }))
      ->willReturn([
        'listResult' => [
          'items' => [
            ['id' => 'media-1', 'title' => 'Video One'],
            ['id' => 'media-2', 'title' => 'Video Two'],
          ],
          'totalItems' => 50,
          'totalPages' => 3,
        ],
      ]);

    $result = $this->controller->browse();

    $this->assertSame('viostream_media_browser', $result['#theme']);
    $this->assertCount(2, $result['#items']);
    $this->assertSame(50, $result['#total_items']);
    $this->assertSame(3, $result['#total_pages']);
    $this->assertSame(1, $result['#current_page']);
    $this->assertStringContainsString('/viostream/browser/search', $result['#search_url']);
    $this->assertStringContainsString('/viostream/browser/detail/', $result['#detail_url_base']);
    $this->assertSame(['viostream/media_browser'], $result['#attached']['library']);
  }

  /**
   * @covers ::browse
   */
  public function testBrowseConfiguredApiReturnsNull(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('listMedia')->willReturn(NULL);

    $result = $this->controller->browse();

    $this->assertSame('viostream_media_browser', $result['#theme']);
    $this->assertSame([], $result['#items']);
    $this->assertSame(0, $result['#total_items']);
    $this->assertSame(0, $result['#total_pages']);
  }

  /**
   * @covers ::browse
   */
  public function testBrowseConfiguredEmptyListResult(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('listMedia')->willReturn([
      'listResult' => [],
    ]);

    $result = $this->controller->browse();

    $this->assertSame('viostream_media_browser', $result['#theme']);
    $this->assertSame([], $result['#items']);
    $this->assertSame(0, $result['#total_items']);
    $this->assertSame(0, $result['#total_pages']);
  }

  /**
   * @covers ::create
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('viostream.client')
      ->willReturn($this->viostreamClient);

    $controller = ViostreamMediaBrowserController::create($container);

    $this->assertInstanceOf(ViostreamMediaBrowserController::class, $controller);
  }

}
