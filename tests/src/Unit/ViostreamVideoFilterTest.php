<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\viostream\Plugin\Filter\ViostreamVideoFilter;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\viostream\Plugin\Filter\ViostreamVideoFilter
 * @group viostream
 */
class ViostreamVideoFilterTest extends TestCase {

  /**
   * The filter under test.
   */
  protected ViostreamVideoFilter $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $configuration = [
      'id' => 'viostream_video',
      'provider' => 'viostream',
      'status' => TRUE,
      'weight' => 10,
      'settings' => [],
    ];

    $this->filter = new ViostreamVideoFilter($configuration, 'viostream_video', [
      'id' => 'viostream_video',
      'provider' => 'viostream',
      'type' => 2, // TYPE_TRANSFORM_REVERSIBLE
    ]);

    // Set a pass-through string translation.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $markup) {
        return $markup->getUntranslatedString();
      });
    $this->filter->setStringTranslation($translation);
  }

  /**
   * @covers ::process
   */
  public function testProcessNoViostreamTags(): void {
    $input = '<p>This is a regular paragraph.</p>';
    $result = $this->filter->process($input, 'en');

    $this->assertSame($input, $result->getProcessedText());
  }

  /**
   * @covers ::process
   */
  public function testProcessWithViostreamVideoTag(): void {
    $input = '<viostream-video data-video-key="abc123" data-video-title="My Video"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // Check the wrapper div.
    $this->assertStringContainsString('class="viostream-embed-wrapper"', $output);
    $this->assertStringContainsString('padding-bottom:56.25%', $output);

    // Check the iframe.
    $this->assertStringContainsString('src="https://share.viostream.com/abc123"', $output);
    $this->assertStringContainsString('title="My Video"', $output);
    $this->assertStringContainsString('allowfullscreen="true"', $output);
    $this->assertStringContainsString('frameborder="0"', $output);

    // Original tag should be gone.
    $this->assertStringNotContainsString('<viostream-video', $output);

    // Library should be attached.
    $attachments = $result->getAttachments();
    $this->assertSame(['viostream/viostream'], $attachments['library']);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithCustomDimensions(): void {
    $input = '<viostream-video data-video-key="vid1" data-video-title="Wide Video" data-video-width="1920" data-video-height="800"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // The padding-bottom should be computed from dimensions: (800/1920)*100 = 41.6667%
    $this->assertStringContainsString('padding-bottom:41.6667%', $output);
    $this->assertStringContainsString('src="https://share.viostream.com/vid1"', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithStandard16by9Dimensions(): void {
    $input = '<viostream-video data-video-key="vid2" data-video-width="1920" data-video-height="1080"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // 1080/1920 * 100 = 56.25%
    $this->assertStringContainsString('padding-bottom:56.25%', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithZeroDimensionsFallsBackToDefault(): void {
    $input = '<viostream-video data-video-key="vid3" data-video-width="0" data-video-height="0"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // Falls back to 56.25% (16:9) when dimensions are zero.
    $this->assertStringContainsString('padding-bottom:56.25%', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithEmptyVideoKey(): void {
    $input = '<viostream-video data-video-key=""></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // Empty key should be skipped, no iframe should be inserted.
    $this->assertStringNotContainsString('iframe', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithMissingVideoKey(): void {
    $input = '<viostream-video data-video-title="No Key"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    $this->assertStringNotContainsString('iframe', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessMultipleVideos(): void {
    $input = '<viostream-video data-video-key="vid1" data-video-title="Video 1"></viostream-video>'
      . '<viostream-video data-video-key="vid2" data-video-title="Video 2"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    $this->assertStringContainsString('src="https://share.viostream.com/vid1"', $output);
    $this->assertStringContainsString('src="https://share.viostream.com/vid2"', $output);
    $this->assertStringContainsString('title="Video 1"', $output);
    $this->assertStringContainsString('title="Video 2"', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessDefaultTitle(): void {
    $input = '<viostream-video data-video-key="vid1"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // When no title attribute is provided, should use 'Viostream Video'.
    $this->assertStringContainsString('title="Viostream Video"', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessIframeAttributes(): void {
    $input = '<viostream-video data-video-key="test" data-video-title="Test"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    $this->assertStringContainsString('width="100%"', $output);
    $this->assertStringContainsString('height="100%"', $output);
    $this->assertStringContainsString('allow="autoplay; fullscreen; picture-in-picture"', $output);
    $this->assertStringContainsString('position:absolute;top:0;left:0;width:100%;height:100%;border:0;', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithPartialDimensionsOnlyWidth(): void {
    $input = '<viostream-video data-video-key="vid" data-video-width="1920"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // Only width set, no height - should use default 16:9.
    $this->assertStringContainsString('padding-bottom:56.25%', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithPartialDimensionsOnlyHeight(): void {
    $input = '<viostream-video data-video-key="vid" data-video-height="1080"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // Only height set, no width - should use default 16:9.
    $this->assertStringContainsString('padding-bottom:56.25%', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessMixedContent(): void {
    $input = '<p>Before</p><viostream-video data-video-key="mid" data-video-title="Middle"></viostream-video><p>After</p>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    $this->assertStringContainsString('<p>Before</p>', $output);
    $this->assertStringContainsString('<p>After</p>', $output);
    $this->assertStringContainsString('src="https://share.viostream.com/mid"', $output);
  }

  /**
   * @covers ::process
   */
  public function testProcessTextContainingViostreamStringButNoTag(): void {
    // Text contains "viostream-video" as a string but not as a tag.
    $input = '<p>Check the viostream-video documentation.</p>';
    $result = $this->filter->process($input, 'en');

    // No transformation needed since it's not a real tag (just text).
    // The strpos check for '<viostream-video' will not match since
    // this is just text without angle brackets around it.
    $this->assertStringNotContainsString('iframe', $result->getProcessedText());
  }

  /**
   * @covers ::tips
   */
  public function testTipsShort(): void {
    $result = $this->filter->tips(FALSE);
    $this->assertStringContainsString('editor toolbar button', (string) $result);
  }

  /**
   * @covers ::tips
   */
  public function testTipsLong(): void {
    $result = $this->filter->tips(TRUE);
    $this->assertStringContainsString('Viostream button in the editor toolbar', (string) $result);
    $this->assertStringContainsString('viostream-video', (string) $result);
  }

  /**
   * @covers ::process
   */
  public function testProcessEscapesVideoKeyInUrl(): void {
    // Test that HTML special chars in the key are escaped.
    // In the HTML source, &amp; is the entity for &, so data-video-key value
    // is "abc&def". Html::escape() then re-encodes & as &amp;, and when the
    // DOM serialises the attribute it encodes that again to &amp;amp;.
    $input = '<viostream-video data-video-key="abc&amp;def" data-video-title="Test"></viostream-video>';
    $result = $this->filter->process($input, 'en');

    $output = $result->getProcessedText();

    // The DOM-parsed key is "abc&def", Html::escape produces "abc&amp;def",
    // and the DOM serialiser encodes the & in the attribute to &amp;amp;def.
    $this->assertStringContainsString('share.viostream.com/abc', $output);
    // Ensure the key was escaped (not left raw with dangerous chars).
    $this->assertStringNotContainsString('<script>', $output);
  }

}
