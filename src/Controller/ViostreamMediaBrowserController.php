<?php

namespace Drupal\viostream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\viostream\Client\ViostreamClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Viostream media browser.
 */
class ViostreamMediaBrowserController extends ControllerBase {

  /**
   * The Viostream API client.
   *
   * @var \Drupal\viostream\Client\ViostreamClient
   */
  protected $viostreamClient;

  /**
   * Constructs a ViostreamMediaBrowserController.
   *
   * @param \Drupal\viostream\Client\ViostreamClient $viostream_client
   *   The Viostream API client.
   */
  public function __construct(ViostreamClient $viostream_client) {
    $this->viostreamClient = $viostream_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('viostream.client')
    );
  }

  /**
   * Renders the media browser page.
   *
   * @return array
   *   A render array for the media browser.
   */
  public function browse() {
    if (!$this->viostreamClient->isConfigured()) {
      $this->messenger()->addError($this->t('Viostream API credentials are not configured. <a href=":url">Configure them here</a>.', [
        ':url' => Url::fromRoute('viostream.settings')->toString(),
      ]));
      return [
        '#markup' => '',
      ];
    }

    // Load the initial page of media.
    $result = $this->viostreamClient->listMedia([
      'PageSize' => 24,
      'PageNumber' => 1,
      'SortColumn' => 'CreatedDate',
      'SortOrder' => 'desc',
    ]);

    $items = [];
    $total_items = 0;
    $total_pages = 0;
    if ($result && isset($result['listResult'])) {
      $items = $result['listResult']['items'] ?? [];
      $total_items = $result['listResult']['totalItems'] ?? 0;
      $total_pages = $result['listResult']['totalPages'] ?? 0;
    }

    return [
      '#theme' => 'viostream_media_browser',
      '#items' => $items,
      '#total_items' => $total_items,
      '#total_pages' => $total_pages,
      '#current_page' => 1,
      '#search_url' => Url::fromRoute('viostream.media_browser.search')->toString(),
      '#detail_url_base' => Url::fromRoute('viostream.media_browser.detail', ['media_id' => '__MEDIA_ID__'])->toString(),
      '#attached' => [
        'library' => ['viostream/media_browser'],
      ],
    ];
  }

  /**
   * AJAX endpoint for searching/paginating media.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with media items.
   */
  public function search(Request $request) {
    if (!$this->viostreamClient->isConfigured()) {
      return new JsonResponse(['error' => 'API not configured'], 403);
    }

    $params = [
      'PageSize' => (int) $request->query->get('page_size', 24),
      'PageNumber' => (int) $request->query->get('page', 1),
      'SortColumn' => $request->query->get('sort', 'CreatedDate'),
      'SortOrder' => $request->query->get('order', 'desc'),
    ];

    $search = $request->query->get('search', '');
    if (!empty($search)) {
      $params['SearchTerm'] = $search;
    }

    $result = $this->viostreamClient->listMedia($params);

    if ($result === NULL) {
      return new JsonResponse(['error' => 'API request failed'], 500);
    }

    $list_result = $result['listResult'] ?? [];

    return new JsonResponse([
      'items' => $list_result['items'] ?? [],
      'totalItems' => $list_result['totalItems'] ?? 0,
      'totalPages' => $list_result['totalPages'] ?? 0,
      'pageNumber' => $list_result['pageNumber'] ?? 1,
      'pageSize' => $list_result['pageSize'] ?? 24,
    ]);
  }

  /**
   * AJAX endpoint for getting media detail.
   *
   * @param string $media_id
   *   The media ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with media detail.
   */
  public function detail($media_id) {
    if (!$this->viostreamClient->isConfigured()) {
      return new JsonResponse(['error' => 'API not configured'], 403);
    }

    $result = $this->viostreamClient->getMediaDetail($media_id);

    if ($result === NULL) {
      return new JsonResponse(['error' => 'Media not found'], 404);
    }

    // Extract video dimensions from the API response.
    // Try download first, then fall back to progressive streams.
    $width = NULL;
    $height = NULL;

    if (!empty($result['download']['width']) && !empty($result['download']['height'])) {
      $width = (int) $result['download']['width'];
      $height = (int) $result['download']['height'];
    }
    elseif (!empty($result['progressive'])) {
      // Use the highest-resolution progressive stream.
      $best = NULL;
      foreach ($result['progressive'] as $stream) {
        if (!empty($stream['width']) && !empty($stream['height'])) {
          if ($best === NULL || $stream['width'] > $best['width']) {
            $best = $stream;
          }
        }
      }
      if ($best) {
        $width = (int) $best['width'];
        $height = (int) $best['height'];
      }
    }

    // Add normalised dimensions to the response for easy consumption.
    $result['videoWidth'] = $width;
    $result['videoHeight'] = $height;

    return new JsonResponse($result);
  }

}
