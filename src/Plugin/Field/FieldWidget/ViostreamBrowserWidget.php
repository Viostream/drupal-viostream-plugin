<?php

namespace Drupal\viostream\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\viostream\Client\ViostreamClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'viostream_browser' widget.
 *
 * Provides a field widget that lets content editors search and select
 * Viostream videos via an inline media browser.
 *
 * @FieldWidget(
 *   id = "viostream_browser",
 *   label = @Translation("Viostream Browser"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "link"
 *   }
 * )
 */
class ViostreamBrowserWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Viostream API client.
   *
   * @var \Drupal\viostream\Client\ViostreamClient
   */
  protected $viostreamClient;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ViostreamClient $viostream_client) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings'],
      $container->get('viostream.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_type = $this->fieldDefinition->getType();

    // Get the current value.
    if ($field_type === 'link') {
      $current_value = $items[$delta]->uri ?? '';
    }
    else {
      $current_value = $items[$delta]->value ?? '';
    }

    // Try to resolve video info if we have a value.
    $video_title = '';
    $video_thumbnail = '';
    if (!empty($current_value) && $this->viostreamClient->isConfigured()) {
      $video_id = $this->extractKeyFromUrl($current_value);
      if ($video_id) {
        $detail = $this->viostreamClient->getMediaDetail($video_id);
        if ($detail) {
          $video_title = $detail['title'] ?? '';
          $video_thumbnail = $detail['thumbnail'] ?? '';
        }
      }
    }

    $wrapper_id = 'viostream-browser-widget-' . $delta;

    $element['#type'] = 'container';
    $element['#attributes'] = [
      'class' => ['viostream-browser-widget'],
      'id' => $wrapper_id,
    ];

    // The hidden field that stores the actual value (share URL).
    if ($field_type === 'link') {
      $element['uri'] = [
        '#type' => 'hidden',
        '#default_value' => $current_value,
        '#attributes' => [
          'class' => ['viostream-selected-value'],
        ],
      ];
    }
    else {
      $element['value'] = [
        '#type' => 'hidden',
        '#default_value' => $current_value,
        '#attributes' => [
          'class' => ['viostream-selected-value'],
        ],
      ];
    }

    // Preview area.
    $element['preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['viostream-preview'],
      ],
    ];

    if (!empty($current_value) && !empty($video_title)) {
      $template = '<div class="viostream-preview-content">';
      $context = [];
      if (!empty($video_thumbnail)) {
        $template .= '<img src="{{ thumbnail }}" alt="" class="viostream-preview-thumb" />';
        $context['thumbnail'] = $video_thumbnail;
      }
      $template .= '<span class="viostream-preview-title">{{ title }}</span></div>';
      $context['title'] = $video_title;
      $element['preview']['content'] = [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $context,
      ];
    }
    elseif (!empty($current_value)) {
      $element['preview']['content'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="viostream-preview-content"><span class="viostream-preview-title">{{ value }}</span></div>',
        '#context' => [
          'value' => $current_value,
        ],
      ];
    }
    else {
      $element['preview']['empty'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="viostream-preview-empty">{{ message }}</div>',
        '#context' => [
          'message' => $this->t('No video selected'),
        ],
      ];
    }

    // Action buttons using Form API button type.
    $element['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['viostream-actions'],
      ],
    ];

    $element['actions']['browse_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Browse Viostream'),
      '#attributes' => [
        'class' => ['viostream-browse-btn'],
      ],
      // Prevent form submission -- this is handled by JS.
      '#limit_validation_errors' => [],
      '#executes_submit_callback' => FALSE,
    ];

    if (!empty($current_value)) {
      $element['actions']['clear_button'] = [
        '#type' => 'button',
        '#value' => $this->t('Clear'),
        '#attributes' => [
          'class' => ['viostream-clear-btn'],
        ],
        '#limit_validation_errors' => [],
        '#executes_submit_callback' => FALSE,
      ];
    }

    // Pass URLs and config to JS via drupalSettings.
    $element['#attached'] = [
      'library' => ['viostream/widget'],
      'drupalSettings' => [
        'viostream' => [
          'searchUrl' => Url::fromRoute('viostream.media_browser.search')->toString(),
          'detailUrlBase' => Url::fromRoute('viostream.media_browser.detail', ['media_id' => '__MEDIA_ID__'])->toString(),
        ],
      ],
    ];

    return $element;
  }

  /**
   * Extracts the video key from a share URL.
   *
   * @param string $url
   *   A Viostream share URL or bare key.
   *
   * @return string|null
   *   The video key, or NULL.
   */
  protected function extractKeyFromUrl($url) {
    if (preg_match('/https?:\/\/share\.viostream\.com\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
      return $matches[1];
    }
    // Could be a bare key.
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $url)) {
      return $url;
    }
    return NULL;
  }

}
