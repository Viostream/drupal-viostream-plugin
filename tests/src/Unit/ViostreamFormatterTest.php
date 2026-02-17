<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\viostream\Client\ViostreamClient;
use Drupal\viostream\Plugin\Field\FieldFormatter\ViostreamFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\viostream\Plugin\Field\FieldFormatter\ViostreamFormatter
 * @group viostream
 */
class ViostreamFormatterTest extends TestCase {

  /**
   * The mock Viostream client.
   */
  protected MockObject $viostreamClient;

  /**
   * The mock field definition.
   */
  protected MockObject $fieldDefinition;

  /**
   * The formatter under test.
   */
  protected ViostreamFormatter $formatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->viostreamClient = $this->createMock(ViostreamClient::class);
    $this->fieldDefinition = $this->createMock(FieldDefinitionInterface::class);

    $settings = ViostreamFormatter::defaultSettings();

    $this->formatter = new ViostreamFormatter(
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream'],
      $this->fieldDefinition,
      $settings,
      'above',
      'full',
      [],
      $this->viostreamClient
    );

    // Set a pass-through string translation.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(function (TranslatableMarkup $markup) {
        return $markup->getUntranslatedString();
      });
    $this->formatter->setStringTranslation($translation);
  }

  /**
   * Helper to call a protected method via reflection.
   */
  protected function callProtected(string $method, ...$args): mixed {
    $ref = new \ReflectionMethod($this->formatter, $method);
    $ref->setAccessible(TRUE);
    return $ref->invoke($this->formatter, ...$args);
  }

  /**
   * @covers ::defaultSettings
   */
  public function testDefaultSettings(): void {
    $settings = ViostreamFormatter::defaultSettings();

    $this->assertSame('100%', $settings['width']);
    $this->assertSame('400', $settings['height']);
    $this->assertFalse($settings['autoplay']);
    $this->assertFalse($settings['muted']);
    $this->assertTrue($settings['controls']);
    $this->assertTrue($settings['responsive']);
  }

  /**
   * @covers ::extractVideoId
   * @dataProvider extractVideoIdProvider
   */
  public function testExtractVideoId(string $url, ?string $expected): void {
    $result = $this->callProtected('extractVideoId', $url);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for extractVideoId.
   */
  public static function extractVideoIdProvider(): array {
    return [
      'share.viostream.com URL' => [
        'https://share.viostream.com/abc123',
        'abc123',
      ],
      'share.viostream.com with trailing slash' => [
        'https://share.viostream.com/abc123/',
        'abc123',
      ],
      'share.viostream.com with query params' => [
        'https://share.viostream.com/abc123?autoplay=1',
        'abc123',
      ],
      'http share URL' => [
        'http://share.viostream.com/xyz789',
        'xyz789',
      ],
      'ID with hyphens and underscores' => [
        'https://share.viostream.com/abc-123_def',
        'abc-123_def',
      ],
      'invalid URL' => [
        'https://www.youtube.com/watch?v=abc123',
        NULL,
      ],
      'empty string' => [
        '',
        NULL,
      ],
      'random string' => [
        'not a url at all',
        NULL,
      ],
      'other domain (unsupported)' => [
        'https://example.com/abc123',
        NULL,
      ],
    ];
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlBasic(): void {
    $result = $this->callProtected('buildEmbedUrl', 'abc123');
    $this->assertStringContainsString('https://share.viostream.com/abc123', $result);
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlEmptyId(): void {
    $result = $this->callProtected('buildEmbedUrl', '');
    $this->assertSame('', $result);
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlSanitizesId(): void {
    // Special characters should be stripped from the ID.
    $result = $this->callProtected('buildEmbedUrl', 'abc<script>123');
    $this->assertStringContainsString('abcscript123', $result);
    $this->assertStringNotContainsString('<', $result);
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlOnlyInvalidCharsResultsInEmpty(): void {
    $result = $this->callProtected('buildEmbedUrl', '!!!@@@###');
    $this->assertSame('', $result);
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlWithAutoplay(): void {
    // Create a formatter with autoplay enabled.
    $formatter = new ViostreamFormatter(
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream'],
      $this->fieldDefinition,
      ['autoplay' => TRUE, 'muted' => FALSE, 'controls' => TRUE, 'width' => '100%', 'height' => '400', 'responsive' => TRUE],
      'above',
      'full',
      [],
      $this->viostreamClient
    );

    $ref = new \ReflectionMethod($formatter, 'buildEmbedUrl');
    $ref->setAccessible(TRUE);
    $result = $ref->invoke($formatter, 'vid1');

    $this->assertStringContainsString('autoplay=1', $result);
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlWithMuted(): void {
    $formatter = new ViostreamFormatter(
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream'],
      $this->fieldDefinition,
      ['autoplay' => FALSE, 'muted' => TRUE, 'controls' => TRUE, 'width' => '100%', 'height' => '400', 'responsive' => TRUE],
      'above',
      'full',
      [],
      $this->viostreamClient
    );

    $ref = new \ReflectionMethod($formatter, 'buildEmbedUrl');
    $ref->setAccessible(TRUE);
    $result = $ref->invoke($formatter, 'vid1');

    $this->assertStringContainsString('muted=1', $result);
  }

  /**
   * @covers ::buildEmbedUrl
   */
  public function testBuildEmbedUrlWithControlsDisabled(): void {
    $formatter = new ViostreamFormatter(
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream'],
      $this->fieldDefinition,
      ['autoplay' => FALSE, 'muted' => FALSE, 'controls' => FALSE, 'width' => '100%', 'height' => '400', 'responsive' => TRUE],
      'above',
      'full',
      [],
      $this->viostreamClient
    );

    $ref = new \ReflectionMethod($formatter, 'buildEmbedUrl');
    $ref->setAccessible(TRUE);
    $result = $ref->invoke($formatter, 'vid1');

    $this->assertStringContainsString('controls=0', $result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioNotConfigured(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertNull($result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioApiReturnsNull(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn(NULL);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertNull($result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioApiReturnsEmpty(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertNull($result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioFromDownloadDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'download' => ['width' => 1920, 'height' => 1080],
    ]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertSame('1920 / 1080', $result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioFromProgressiveStreams(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'progressive' => [
        ['width' => 640, 'height' => 360],
        ['width' => 1280, 'height' => 720],
        ['width' => 1920, 'height' => 1080],
      ],
    ]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    // Should pick the highest resolution (1920x1080).
    $this->assertSame('1920 / 1080', $result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioProgressiveWithMissingDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'progressive' => [
        ['width' => 640, 'height' => 360],
        ['url' => 'https://example.com/stream'],  // No dimensions.
      ],
    ]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertSame('640 / 360', $result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioEmptyProgressiveStreams(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'progressive' => [],
    ]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertNull($result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioProgressiveAllWithoutDimensions(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'progressive' => [
        ['url' => 'https://example.com/stream1'],
        ['url' => 'https://example.com/stream2'],
      ],
    ]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    $this->assertNull($result);
  }

  /**
   * @covers ::getAspectRatio
   */
  public function testGetAspectRatioDownloadPrefersOverProgressive(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'download' => ['width' => 3840, 'height' => 2160],
      'progressive' => [
        ['width' => 1920, 'height' => 1080],
      ],
    ]);

    $result = $this->callProtected('getAspectRatio', 'vid1');
    // Should prefer download dimensions.
    $this->assertSame('3840 / 2160', $result);
  }

  /**
   * @covers ::settingsForm
   */
  public function testSettingsForm(): void {
    $formState = $this->createMock(\Drupal\Core\Form\FormStateInterface::class);
    $form = $this->formatter->settingsForm([], $formState);

    $this->assertArrayHasKey('width', $form);
    $this->assertArrayHasKey('height', $form);
    $this->assertArrayHasKey('responsive', $form);
    $this->assertArrayHasKey('autoplay', $form);
    $this->assertArrayHasKey('muted', $form);
    $this->assertArrayHasKey('controls', $form);

    $this->assertSame('textfield', $form['width']['#type']);
    $this->assertSame('textfield', $form['height']['#type']);
    $this->assertSame('checkbox', $form['responsive']['#type']);
    $this->assertSame('checkbox', $form['autoplay']['#type']);
    $this->assertSame('checkbox', $form['muted']['#type']);
    $this->assertSame('checkbox', $form['controls']['#type']);
  }

  /**
   * @covers ::settingsSummary
   */
  public function testSettingsSummary(): void {
    $summary = $this->formatter->settingsSummary();

    $this->assertNotEmpty($summary);
    // Default settings: controls enabled, responsive enabled.
    $summaryString = implode(' | ', array_map('strval', $summary));
    $this->assertStringContainsString('Width:', $summaryString);
    $this->assertStringContainsString('Height:', $summaryString);
  }

  /**
   * @covers ::settingsSummary
   */
  public function testSettingsSummaryWithResponsive(): void {
    $summary = $this->formatter->settingsSummary();
    $summaryString = implode(' | ', array_map('strval', $summary));

    // Responsive is TRUE by default.
    $this->assertStringContainsString('Responsive: Yes', $summaryString);
  }

  /**
   * @covers ::settingsSummary
   */
  public function testSettingsSummaryWithOptions(): void {
    // Create formatter with autoplay and muted enabled.
    $formatter = new ViostreamFormatter(
      'viostream_video',
      ['id' => 'viostream_video', 'provider' => 'viostream'],
      $this->fieldDefinition,
      ['autoplay' => TRUE, 'muted' => TRUE, 'controls' => TRUE, 'width' => '640px', 'height' => '360', 'responsive' => FALSE],
      'above',
      'full',
      [],
      $this->viostreamClient
    );

    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn(TranslatableMarkup $m) => $m->getUntranslatedString());
    $formatter->setStringTranslation($translation);

    $summary = $formatter->settingsSummary();
    $summaryString = implode(' | ', array_map('strval', $summary));

    $this->assertStringContainsString('Autoplay', $summaryString);
    $this->assertStringContainsString('Muted', $summaryString);
    $this->assertStringContainsString('Controls', $summaryString);
    $this->assertStringNotContainsString('Responsive', $summaryString);
  }

  /**
   * Creates a mock FieldItemListInterface that iterates over the given items.
   *
   * PHPUnit generates FieldItemListInterface mocks as Iterator (not
   * IteratorAggregate), so we configure the Iterator methods to walk through
   * the items array.
   *
   * @param array $items
   *   Array of field item mocks.
   *
   * @return FieldItemListInterface
   *   A mock field item list that is iterable.
   */
  protected function createItemList(array $items): FieldItemListInterface {
    $mock = $this->createStub(FieldItemListInterface::class);
    $index = 0;
    $mock->method('rewind')->willReturnCallback(function () use (&$index) {
      $index = 0;
    });
    $mock->method('valid')->willReturnCallback(function () use (&$index, $items) {
      return isset($items[$index]);
    });
    $mock->method('current')->willReturnCallback(function () use (&$index, $items) {
      return $items[$index] ?? NULL;
    });
    $mock->method('key')->willReturnCallback(function () use (&$index) {
      return $index;
    });
    $mock->method('next')->willReturnCallback(function () use (&$index) {
      $index++;
    });
    return $mock;
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElementsWithStringField(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'download' => ['width' => 1920, 'height' => 1080],
    ]);

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('string');

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('__get')->willReturnMap([
      ['value', 'https://share.viostream.com/abc123'],
    ]);
    $item->method('getFieldDefinition')->willReturn($fieldDef);

    $items = $this->createItemList([$item]);

    $elements = $this->formatter->viewElements($items, 'en');

    $this->assertCount(1, $elements);
    $this->assertSame('viostream_video', $elements[0]['#theme']);
    $this->assertSame('abc123', $elements[0]['#video_id']);
    $this->assertStringContainsString('https://share.viostream.com/abc123', $elements[0]['#embed_url']);
    $this->assertSame('1920 / 1080', $elements[0]['#aspect_ratio']);
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElementsWithLinkField(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn(NULL);

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('link');

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('__get')->willReturnMap([
      ['uri', 'https://share.viostream.com/def456'],
    ]);
    $item->method('getFieldDefinition')->willReturn($fieldDef);

    $items = $this->createItemList([$item]);

    $elements = $this->formatter->viewElements($items, 'en');

    $this->assertCount(1, $elements);
    $this->assertSame('def456', $elements[0]['#video_id']);
    $this->assertNull($elements[0]['#aspect_ratio']);
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElementsWithEmptyValue(): void {
    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('string');

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('__get')->willReturnMap([
      ['value', ''],
    ]);
    $item->method('getFieldDefinition')->willReturn($fieldDef);

    $items = $this->createItemList([$item]);

    $elements = $this->formatter->viewElements($items, 'en');

    $this->assertEmpty($elements);
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElementsWithInvalidUrl(): void {
    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('string');

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('__get')->willReturnMap([
      ['value', 'https://www.youtube.com/watch?v=abc'],
    ]);
    $item->method('getFieldDefinition')->willReturn($fieldDef);

    $items = $this->createItemList([$item]);

    $elements = $this->formatter->viewElements($items, 'en');

    $this->assertEmpty($elements);
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElementsNoItems(): void {
    $items = $this->createItemList([]);

    $elements = $this->formatter->viewElements($items, 'en');

    $this->assertEmpty($elements);
  }

  /**
   * @covers ::viewElements
   */
  public function testViewElementsIncludesAttachedLibrary(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('string');

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('__get')->willReturnMap([
      ['value', 'https://share.viostream.com/test'],
    ]);
    $item->method('getFieldDefinition')->willReturn($fieldDef);

    $items = $this->createItemList([$item]);

    $elements = $this->formatter->viewElements($items, 'en');

    $this->assertCount(1, $elements);
    $this->assertSame(['viostream/viostream'], $elements[0]['#attached']['library']);
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

    $configuration = [
      'field_definition' => $this->fieldDefinition,
      'settings' => ViostreamFormatter::defaultSettings(),
      'label' => 'above',
      'view_mode' => 'full',
      'third_party_settings' => [],
    ];

    $formatter = ViostreamFormatter::create($container, $configuration, 'viostream_video', ['id' => 'viostream_video', 'provider' => 'viostream']);

    $this->assertInstanceOf(ViostreamFormatter::class, $formatter);
  }

}
