<?php

namespace Drupal\viostream\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 plugin for Viostream video embedding.
 *
 * Provides dynamic configuration to pass the Viostream media browser
 * API endpoints to the CKEditor 5 JavaScript plugin.
 */
class ViostreamVideo extends CKEditor5PluginDefault {

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return [
      'viostreamVideo' => [
        'searchUrl' => Url::fromRoute('viostream.media_browser.search')->toString(),
        'detailUrlBase' => Url::fromRoute('viostream.media_browser.detail', ['media_id' => '__MEDIA_ID__'])->toString(),
      ],
    ];
  }

}
