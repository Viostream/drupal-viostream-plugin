<?php

namespace Drupal\viostream\Client;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Viostream API client service.
 *
 * Provides methods for interacting with the Viostream v3 REST API
 * using HTTP Basic authentication.
 */
class ViostreamClient {

  /**
   * The base URL for the Viostream API.
   */
  const API_BASE_URL = 'https://api.app.viostream.com/v3/api';

  /**
   * Whether the last API request failed due to authentication.
   *
   * @var bool
   */
  protected $lastRequestWasAuthError = FALSE;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ViostreamClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('viostream');
  }

  /**
   * Gets the API configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The viostream API settings config.
   */
  protected function getConfig() {
    return $this->configFactory->get('viostream.settings');
  }

  /**
   * Checks whether API credentials are configured.
   *
   * @return bool
   *   TRUE if both access key and API key are set.
   */
  public function isConfigured() {
    $config = $this->getConfig();
    $access_key = $config->get('access_key');
    $api_key = $config->get('api_key');
    return !empty($access_key) && !empty($api_key);
  }

  /**
   * Checks whether the last API request failed due to authentication.
   *
   * @return bool
   *   TRUE if the last request returned a 401 Unauthorized response.
   */
  public function isAuthError() {
    return $this->lastRequestWasAuthError;
  }

  /**
   * Builds request options with Basic auth headers.
   *
   * @param array $query
   *   Optional query parameters.
   *
   * @return array
   *   Guzzle request options array.
   */
  protected function buildRequestOptions(array $query = []) {
    $config = $this->getConfig();
    $options = [
      'auth' => [
        $config->get('access_key'),
        $config->get('api_key'),
      ],
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ],
      'timeout' => 30,
    ];

    if (!empty($query)) {
      $options['query'] = $query;
    }

    return $options;
  }

  /**
   * Makes a GET request to the Viostream API.
   *
   * @param string $endpoint
   *   The API endpoint path (e.g., '/account/info').
   * @param array $query
   *   Optional query parameters.
   *
   * @return array|null
   *   The decoded JSON response, or NULL on failure.
   */
  protected function get($endpoint, array $query = []) {
    $this->lastRequestWasAuthError = FALSE;
    try {
      $url = self::API_BASE_URL . $endpoint;
      $options = $this->buildRequestOptions($query);
      $response = $this->httpClient->request('GET', $url, $options);

      $status = $response->getStatusCode();
      if ($status === 200) {
        return json_decode($response->getBody()->getContents(), TRUE);
      }

      if ($status === 401 || $status === 403) {
        $this->lastRequestWasAuthError = TRUE;
      }

      $this->logger->warning('Viostream API returned status @status for GET @endpoint', [
        '@status' => $status,
        '@endpoint' => $endpoint,
      ]);
      return NULL;
    }
    catch (GuzzleException $e) {
      // Guzzle throws RequestException on 4xx/5xx when http_errors is
      // enabled (the default). Check if the response was a 401.
if ($e instanceof \GuzzleHttp\Exception\RequestException
          && $e->getResponse()
          && in_array($e->getResponse()->getStatusCode(), [401, 403], TRUE)) {
        $this->lastRequestWasAuthError = TRUE;
      }
      $this->logger->error('Viostream API request failed for GET @endpoint: @message', [
        '@endpoint' => $endpoint,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Makes a POST request to the Viostream API.
   *
   * @param string $endpoint
   *   The API endpoint path.
   * @param array $body
   *   The request body data.
   *
   * @return array|null
   *   The decoded JSON response, or NULL on failure.
   */
  protected function post($endpoint, array $body = []) {
    try {
      $url = self::API_BASE_URL . $endpoint;
      $options = $this->buildRequestOptions();
      $options['json'] = $body;
      $response = $this->httpClient->request('POST', $url, $options);

      if ($response->getStatusCode() === 200) {
        return json_decode($response->getBody()->getContents(), TRUE);
      }

      $this->logger->warning('Viostream API returned status @status for POST @endpoint', [
        '@status' => $response->getStatusCode(),
        '@endpoint' => $endpoint,
      ]);
      return NULL;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Viostream API request failed for POST @endpoint: @message', [
        '@endpoint' => $endpoint,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Makes a PUT request to the Viostream API.
   *
   * @param string $endpoint
   *   The API endpoint path.
   * @param array $body
   *   The request body data.
   *
   * @return array|null
   *   The decoded JSON response, or NULL on failure.
   */
  protected function put($endpoint, array $body = []) {
    try {
      $url = self::API_BASE_URL . $endpoint;
      $options = $this->buildRequestOptions();
      $options['json'] = $body;
      $response = $this->httpClient->request('PUT', $url, $options);

      if ($response->getStatusCode() === 200) {
        return json_decode($response->getBody()->getContents(), TRUE);
      }

      $this->logger->warning('Viostream API returned status @status for PUT @endpoint', [
        '@status' => $response->getStatusCode(),
        '@endpoint' => $endpoint,
      ]);
      return NULL;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Viostream API request failed for PUT @endpoint: @message', [
        '@endpoint' => $endpoint,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  // =========================================================================
  // Account endpoints.
  // =========================================================================

  /**
   * Gets account information.
   *
   * @return array|null
   *   Account info with keys: id, publicKey, title. Or NULL on failure.
   */
  public function getAccountInfo() {
    return $this->get('/account/info');
  }

  // =========================================================================
  // Media endpoints.
  // =========================================================================

  /**
   * Lists media items with optional search and pagination.
   *
   * @param array $params
   *   Optional parameters:
   *   - SearchTerm: (string) Search term to filter results.
   *   - SortColumn: (string) 'Title' or 'CreatedDate'.
   *   - SortOrder: (string) Sort direction.
   *   - PageSize: (int) Number of items per page (max 100).
   *   - PageNumber: (int) Page number.
   *   - Expand: (string) Additional info to include.
   *
   * @return array|null
   *   Media list response with 'listResult' key, or NULL on failure.
   */
  public function listMedia(array $params = []) {
    return $this->get('/media', $params);
  }

  /**
   * Lists media items by their IDs.
   *
   * @param array $media_ids
   *   Array of media UUIDs.
   *
   * @return array|null
   *   Response with 'items' key, or NULL on failure.
   */
  public function listMediaByIds(array $media_ids) {
    return $this->get('/media/listbyids', ['mediaIds' => $media_ids]);
  }

  /**
   * Gets detailed information about a specific media item.
   *
   * @param string $media_id
   *   The media ID (UUID or key).
   * @param string|null $expand
   *   Optional expand parameter for additional details.
   *
   * @return array|null
   *   Media detail response, or NULL on failure.
   */
  public function getMediaDetail($media_id, $expand = NULL) {
    $params = [];
    if ($expand) {
      $params['Expand'] = $expand;
    }
    return $this->get('/media/' . urlencode($media_id) . '/detail', $params);
  }

  /**
   * Creates a new media ingest.
   *
   * @param string $source_url
   *   The source URL of the media.
   * @param string $filename
   *   The filename.
   * @param string $extension
   *   The file extension (e.g., '.mp4').
   * @param string|null $reference_id
   *   Optional reference ID.
   *
   * @return array|null
   *   Response with 'ingestId' key, or NULL on failure.
   */
  public function createMediaIngest($source_url, $filename, $extension, $reference_id = NULL) {
    $body = [
      'sourceUrl' => $source_url,
      'filename' => $filename,
      'extension' => $extension,
    ];
    if ($reference_id !== NULL) {
      $body['referenceId'] = $reference_id;
    }
    return $this->post('/media/new', $body);
  }

  /**
   * Gets the status of a media ingest.
   *
   * @param string $ingest_id
   *   The ingest UUID.
   *
   * @return array|null
   *   Response with 'ingestId', 'status', and optional 'error' keys.
   */
  public function getIngestStatus($ingest_id) {
    return $this->get('/media/new/status/' . urlencode($ingest_id));
  }

  // =========================================================================
  // Channel endpoints.
  // =========================================================================

  /**
   * Lists channels with optional search and pagination.
   *
   * @param array $params
   *   Optional parameters:
   *   - SearchTerm: (string) Search term.
   *   - SortColumn: (string) 'Title' or 'CreatedDate'.
   *   - SortOrder: (string) Sort direction.
   *   - PageSize: (int) Items per page (max 100).
   *   - PageNumber: (int) Page number.
   *
   * @return array|null
   *   Channel list response, or NULL on failure.
   */
  public function listChannels(array $params = []) {
    return $this->get('/channels', $params);
  }

  /**
   * Lists channels by their IDs.
   *
   * @param array $channel_ids
   *   Array of channel UUIDs.
   *
   * @return array|null
   *   Response with 'items' key, or NULL on failure.
   */
  public function listChannelsByIds(array $channel_ids) {
    return $this->get('/channels/listbyids', ['channelIds' => $channel_ids]);
  }

  /**
   * Gets detailed information about a specific channel.
   *
   * @param string $channel_id
   *   The channel UUID.
   * @param array $params
   *   Optional parameters for media pagination within the channel:
   *   - SearchTerm, SortColumn, SortOrder, PageSize, PageNumber.
   *
   * @return array|null
   *   Channel detail response, or NULL on failure.
   */
  public function getChannelDetail($channel_id, array $params = []) {
    return $this->get('/channels/' . urlencode($channel_id) . '/detail', $params);
  }

  // =========================================================================
  // Tag endpoints.
  // =========================================================================

  /**
   * Lists tags with optional pagination.
   *
   * @param array $params
   *   Optional parameters:
   *   - SortColumn: (string) 'Value' or 'Count'.
   *   - SortOrder: (string) Sort direction.
   *   - PageSize: (int) Items per page (max 100).
   *   - PageNumber: (int) Page number.
   *
   * @return array|null
   *   Tag list response, or NULL on failure.
   */
  public function listTags(array $params = []) {
    return $this->get('/tags', $params);
  }

  /**
   * Lists tags with usage counts.
   *
   * @param array $params
   *   Optional parameters (same as listTags).
   *
   * @return array|null
   *   Tag usage list response, or NULL on failure.
   */
  public function listTagsWithUsage(array $params = []) {
    return $this->get('/tags/usage', $params);
  }

  // =========================================================================
  // Whitelist endpoints.
  // =========================================================================

  /**
   * Lists all domain whitelists.
   *
   * @return array|null
   *   Response with 'whitelists' key, or NULL on failure.
   */
  public function listWhitelists() {
    return $this->get('/whitelist');
  }

  /**
   * Gets detail for a specific whitelist.
   *
   * @param string $whitelist_id
   *   The whitelist UUID.
   *
   * @return array|null
   *   Whitelist detail response, or NULL on failure.
   */
  public function getWhitelistDetail($whitelist_id) {
    return $this->get('/whitelist/' . urlencode($whitelist_id) . '/detail');
  }

  /**
   * Creates a new domain whitelist.
   *
   * @param string $title
   *   The whitelist title (max 50 characters).
   * @param array $domains
   *   Optional array of domain names.
   *
   * @return array|null
   *   Response with 'id' key, or NULL on failure.
   */
  public function createWhitelist($title, array $domains = []) {
    $body = ['title' => $title];
    if (!empty($domains)) {
      $body['domains'] = $domains;
    }
    return $this->post('/whitelist/create', $body);
  }

  /**
   * Adds domains to a whitelist.
   *
   * @param string $whitelist_id
   *   The whitelist UUID.
   * @param array $domains
   *   Array of domain names to add.
   *
   * @return array|null
   *   Updated whitelist detail, or NULL on failure.
   */
  public function addDomainsToWhitelist($whitelist_id, array $domains) {
    return $this->put('/whitelist/' . urlencode($whitelist_id) . '/adddomains', [
      'domains' => $domains,
    ]);
  }

  /**
   * Removes domains from a whitelist.
   *
   * @param string $whitelist_id
   *   The whitelist UUID.
   * @param array $domains
   *   Array of domain names to remove.
   *
   * @return array|null
   *   Updated whitelist detail, or NULL on failure.
   */
  public function removeDomainsFromWhitelist($whitelist_id, array $domains) {
    return $this->put('/whitelist/' . urlencode($whitelist_id) . '/removedomains', [
      'domains' => $domains,
    ]);
  }

  /**
   * Adds media items to a whitelist.
   *
   * @param string $whitelist_id
   *   The whitelist UUID.
   * @param array $media_public_keys
   *   Array of media public keys (max 10).
   *
   * @return array|null
   *   Updated whitelist detail, or NULL on failure.
   */
  public function addMediaToWhitelist($whitelist_id, array $media_public_keys) {
    return $this->put('/whitelist/' . urlencode($whitelist_id) . '/addmedia', [
      'mediaPublicKeys' => $media_public_keys,
    ]);
  }

  /**
   * Removes media items from a whitelist.
   *
   * @param string $whitelist_id
   *   The whitelist UUID.
   * @param array $media_public_keys
   *   Array of media public keys (max 10).
   *
   * @return array|null
   *   Updated whitelist detail, or NULL on failure.
   */
  public function removeMediaFromWhitelist($whitelist_id, array $media_public_keys) {
    return $this->put('/whitelist/' . urlencode($whitelist_id) . '/removemedia', [
      'mediaPublicKeys' => $media_public_keys,
    ]);
  }

}
