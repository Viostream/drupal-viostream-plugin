<?php

namespace Drupal\viostream\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\viostream\Client\ViostreamClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'viostream_video' formatter.
 *
 * @FieldFormatter(
 *   id = "viostream_video",
 *   label = @Translation("Viostream Video"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long"
 *   }
 * )
 */
class ViostreamFormatter extends FormatterBase {

  /**
   * The Viostream API client.
   *
   * @var \Drupal\viostream\Client\ViostreamClient
   */
  protected $viostreamClient;

  /**
   * Constructs a ViostreamFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\viostream\Client\ViostreamClient $viostream_client
   *   The Viostream API client.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ViostreamClient $viostream_client
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->viostreamClient = $viostream_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('viostream.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '100%',
      'height' => '400',
      'autoplay' => FALSE,
      'muted' => FALSE,
      'controls' => TRUE,
      'responsive' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#description' => $this->t('Width of the video player (e.g., 100% or 640px).'),
      '#required' => TRUE,
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#description' => $this->t('Height of the video player in pixels (e.g., 400).'),
      '#required' => TRUE,
    ];

    $elements['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive'),
      '#default_value' => $this->getSetting('responsive'),
      '#description' => $this->t('Make the video player responsive, preserving the native aspect ratio of each video.'),
    ];

    $elements['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $this->getSetting('autoplay'),
      '#description' => $this->t('Automatically start playing the video when the page loads.'),
    ];

    $elements['muted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Muted'),
      '#default_value' => $this->getSetting('muted'),
      '#description' => $this->t('Mute the video by default.'),
    ];

    $elements['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show controls'),
      '#default_value' => $this->getSetting('controls'),
      '#description' => $this->t('Display video player controls.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Width: @width, Height: @height', [
      '@width' => $this->getSetting('width'),
      '@height' => $this->getSetting('height'),
    ]);

    if ($this->getSetting('responsive')) {
      $summary[] = $this->t('Responsive: Yes');
    }

    $options = [];
    if ($this->getSetting('autoplay')) {
      $options[] = $this->t('Autoplay');
    }
    if ($this->getSetting('muted')) {
      $options[] = $this->t('Muted');
    }
    if ($this->getSetting('controls')) {
      $options[] = $this->t('Controls');
    }

    if (!empty($options)) {
      $summary[] = implode(', ', $options);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $video_url = NULL;

      // Handle different field types.
      if ($item->getFieldDefinition()->getType() === 'link') {
        $video_url = $item->uri;
      }
      else {
        $video_url = $item->value;
      }

      if (empty($video_url)) {
        continue;
      }

      // Extract video ID from Viostream URL.
      $video_id = $this->extractVideoId($video_url);

      if (!$video_id) {
        continue;
      }

      // Build embed URL.
      $embed_url = $this->buildEmbedUrl($video_id);

      // Skip if URL building failed.
      if (empty($embed_url)) {
        continue;
      }

      $elements[$delta] = [
        '#theme' => 'viostream_video',
        '#video_id' => $video_id,
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
        '#autoplay' => $this->getSetting('autoplay'),
        '#muted' => $this->getSetting('muted'),
        '#controls' => $this->getSetting('controls'),
        '#responsive' => $this->getSetting('responsive'),
        '#embed_url' => $embed_url,
        '#aspect_ratio' => $this->getAspectRatio($video_id),
        '#attached' => [
          'library' => ['viostream/viostream'],
        ],
        '#cache' => [
          'contexts' => ['url'],
        ],
      ];
    }

    return $elements;
  }

  /**
   * Extract video ID from Viostream URL.
   *
   * @param string $url
   *   The Viostream video URL.
   *
   * @return string|null
   *   The video ID or NULL if not found.
   */
  protected function extractVideoId($url) {
    // Only support share.viostream.com URLs:
    // - https://share.viostream.com/{id}
    // - http://share.viostream.com/{id}

    // Parse share.viostream.com URL pattern.
    if (preg_match('/https?:\/\/share\.viostream\.com\/([a-zA-Z0-9_-]+)(?:\/|\?|$)/', $url, $matches)) {
      return $matches[1];
    }

    return NULL;
  }

  /**
   * Build the embed URL for a Viostream video.
   *
   * @param string $video_id
   *   The video ID.
   *
   * @return string
   *   The embed URL.
   */
  protected function buildEmbedUrl($video_id) {
    // Sanitize the video ID - only allow alphanumeric, hyphens, and underscores.
    $sanitized_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $video_id);

    if (empty($sanitized_id)) {
      return '';
    }

    $params = [];

    if ($this->getSetting('autoplay')) {
      $params['autoplay'] = '1';
    }

    if ($this->getSetting('muted')) {
      $params['muted'] = '1';
    }

    if (!$this->getSetting('controls')) {
      $params['controls'] = '0';
    }

    // Build embed URL for share.viostream.com using Drupal's Url class.
    try {
      $url = Url::fromUri("https://share.viostream.com/{$sanitized_id}", [
        'query' => $params,
      ]);
      return $url->toString();
    }
    catch (\Exception $e) {
      // If URL building fails, return empty string.
      return '';
    }
  }

  /**
   * Gets the aspect ratio string for a video from the Viostream API.
   *
   * Returns a "width / height" CSS aspect-ratio value (e.g. "16 / 9"),
   * or NULL if dimensions cannot be determined.
   *
   * @param string $video_id
   *   The video public key.
   *
   * @return string|null
   *   The CSS aspect-ratio value, or NULL.
   */
  protected function getAspectRatio($video_id) {
    if (!$this->viostreamClient->isConfigured()) {
      return NULL;
    }

    $detail = $this->viostreamClient->getMediaDetail($video_id);
    if (empty($detail)) {
      return NULL;
    }

    // Try download dimensions first.
    if (!empty($detail['download']['width']) && !empty($detail['download']['height'])) {
      return (int) $detail['download']['width'] . ' / ' . (int) $detail['download']['height'];
    }

    // Fall back to the highest-resolution progressive stream.
    if (!empty($detail['progressive'])) {
      $best = NULL;
      foreach ($detail['progressive'] as $stream) {
        if (!empty($stream['width']) && !empty($stream['height'])) {
          if ($best === NULL || $stream['width'] > $best['width']) {
            $best = $stream;
          }
        }
      }
      if ($best) {
        return (int) $best['width'] . ' / ' . (int) $best['height'];
      }
    }

    return NULL;
  }

}
