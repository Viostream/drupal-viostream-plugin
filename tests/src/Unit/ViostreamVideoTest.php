<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\editor\EditorInterface;
use Drupal\viostream\Plugin\CKEditor5Plugin\ViostreamVideo;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\viostream\Plugin\CKEditor5Plugin\ViostreamVideo
 * @group viostream
 */
class ViostreamVideoTest extends TestCase {

  /**
   * @covers ::getDynamicPluginConfig
   */
  public function testGetDynamicPluginConfig(): void {
    // CKEditor5PluginDefault's constructor needs specific params.
    // Let's check what we need.
    $ref = new \ReflectionClass(CKEditor5PluginDefault::class);
    $ctor = $ref->getConstructor();
    $params = $ctor->getParameters();

    // Create the plugin with the required constructor args.
    // CKEditor5PluginDefault extends CKEditor5PluginBase extends PluginBase.
    // Constructor: (array $configuration, $plugin_id, $plugin_definition)
    $plugin = new ViostreamVideo(
      [],
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream', 'ckeditor5' => ['plugins' => []]]
    );

    $editor = $this->createMock(EditorInterface::class);

    $config = $plugin->getDynamicPluginConfig([], $editor);

    $this->assertArrayHasKey('viostreamVideo', $config);
    $this->assertArrayHasKey('searchUrl', $config['viostreamVideo']);
    $this->assertArrayHasKey('detailUrlBase', $config['viostreamVideo']);

    // The URLs should contain the route paths.
    $this->assertIsString($config['viostreamVideo']['searchUrl']);
    $this->assertIsString($config['viostreamVideo']['detailUrlBase']);

    // Detail URL should contain the placeholder.
    $this->assertStringContainsString('__MEDIA_ID__', $config['viostreamVideo']['detailUrlBase']);
  }

  /**
   * @covers ::getDynamicPluginConfig
   */
  public function testGetDynamicPluginConfigReturnsStaticConfig(): void {
    $plugin = new ViostreamVideo(
      [],
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream', 'ckeditor5' => ['plugins' => []]]
    );

    $editor = $this->createMock(EditorInterface::class);

    // Passing static config should still work (it's not used in this impl).
    $staticConfig = ['some' => 'config'];
    $config = $plugin->getDynamicPluginConfig($staticConfig, $editor);

    $this->assertArrayHasKey('viostreamVideo', $config);
  }

}
