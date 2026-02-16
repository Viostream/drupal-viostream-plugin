<?php

namespace Drupal\viostream\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
      '#description' => $this->t('Make the video player responsive (16:9 aspect ratio).'),
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
    // Support various Viostream URL formats:
    // - https://viostream.com/video/{id}
    // - https://play.viostream.com/{id}
    // - https://app.viostream.com/video/{id}
    // - Just the ID itself
    
    // If it's already just an ID (alphanumeric), return it.
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $url)) {
      return $url;
    }

    // Parse various URL patterns.
    $patterns = [
      '/viostream\.com\/video\/([a-zA-Z0-9_-]+)/',
      '/play\.viostream\.com\/([a-zA-Z0-9_-]+)/',
      '/app\.viostream\.com\/video\/([a-zA-Z0-9_-]+)/',
      '/viostream\.com\/.*[?&]v=([a-zA-Z0-9_-]+)/',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
      }
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

    $query = !empty($params) ? '?' . http_build_query($params) : '';
    
    // Use Viostream's embed URL format.
    return "https://play.viostream.com/{$video_id}" . $query;
  }

}
