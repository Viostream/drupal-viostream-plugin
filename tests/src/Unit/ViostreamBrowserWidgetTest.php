<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\viostream\Client\ViostreamClient;
use Drupal\viostream\Plugin\Field\FieldWidget\ViostreamBrowserWidget;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\viostream\Plugin\Field\FieldWidget\ViostreamBrowserWidget
 * @group viostream
 */
class ViostreamBrowserWidgetTest extends TestCase {

  /**
   * The mock Viostream client.
   */
  protected MockObject $viostreamClient;

  /**
   * The mock field definition.
   */
  protected MockObject $fieldDefinition;

  /**
   * The widget under test.
   */
  protected ViostreamBrowserWidget $widget;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->viostreamClient = $this->createMock(ViostreamClient::class);
    $this->fieldDefinition = $this->createMock(FieldDefinitionInterface::class);

    $this->widget = new ViostreamBrowserWidget(
      'viostream_browser',
      ['id' => 'viostream_browser', 'provider' => 'viostream'],
      $this->fieldDefinition,
      [],
      [],
      $this->viostreamClient
    );

    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn(TranslatableMarkup $m) => $m->getUntranslatedString());
    $this->widget->setStringTranslation($translation);
  }

  /**
   * Helper to call a protected method via reflection.
   */
  protected function callProtected(string $method, ...$args): mixed {
    $ref = new \ReflectionMethod($this->widget, $method);
    $ref->setAccessible(TRUE);
    return $ref->invoke($this->widget, ...$args);
  }

  /**
   * @covers ::extractKeyFromUrl
   * @dataProvider extractKeyFromUrlProvider
   */
  public function testExtractKeyFromUrl(string $input, ?string $expected): void {
    $result = $this->callProtected('extractKeyFromUrl', $input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for extractKeyFromUrl.
   */
  public static function extractKeyFromUrlProvider(): array {
    return [
      'share URL' => [
        'https://share.viostream.com/abc123',
        'abc123',
      ],
      'http share URL' => [
        'http://share.viostream.com/xyz789',
        'xyz789',
      ],
      'share URL with trailing path' => [
        'https://share.viostream.com/abc123/extra',
        'abc123',
      ],
      'bare key alphanumeric' => [
        'abc123',
        'abc123',
      ],
      'bare key with hyphen and underscore' => [
        'abc-123_def',
        'abc-123_def',
      ],
      'invalid URL' => [
        'https://www.example.com/abc123',
        NULL,
      ],
      'empty string' => [
        '',
        NULL,
      ],
      'string with spaces' => [
        'abc 123',
        NULL,
      ],
      'string with special chars' => [
        'abc@123',
        NULL,
      ],
    ];
  }

  /**
   * @covers ::defaultSettings
   */
  public function testDefaultSettings(): void {
    $settings = ViostreamBrowserWidget::defaultSettings();
    $this->assertIsArray($settings);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementStringFieldEmptyValue(): void {
    $this->fieldDefinition->method('getType')->willReturn('string');
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $item = new \stdClass();
    $item->value = '';

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('offsetExists')->willReturn(TRUE);
    $items->method('offsetGet')->willReturn($item);

    $formState = $this->createMock(FormStateInterface::class);
    $form = [];

    $element = $this->widget->formElement($items, 0, [], $form, $formState);

    $this->assertSame('container', $element['#type']);
    $this->assertArrayHasKey('value', $element);
    $this->assertSame('hidden', $element['value']['#type']);
    $this->assertArrayHasKey('preview', $element);
    $this->assertArrayHasKey('empty', $element['preview']);
    $this->assertArrayHasKey('actions', $element);
    $this->assertArrayHasKey('browse_button', $element['actions']);
    // No clear button when empty.
    $this->assertArrayNotHasKey('clear_button', $element['actions']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementStringFieldWithValue(): void {
    $this->fieldDefinition->method('getType')->willReturn('string');
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'title' => 'My Video',
      'thumbnail' => 'https://example.com/thumb.jpg',
    ]);

    $item = new \stdClass();
    $item->value = 'https://share.viostream.com/abc123';

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('offsetExists')->willReturn(TRUE);
    $items->method('offsetGet')->willReturn($item);

    $formState = $this->createMock(FormStateInterface::class);
    $form = [];

    $element = $this->widget->formElement($items, 0, [], $form, $formState);

    $this->assertSame('hidden', $element['value']['#type']);
    $this->assertSame('https://share.viostream.com/abc123', $element['value']['#default_value']);
    $this->assertArrayHasKey('content', $element['preview']);
    // Clear button should be present.
    $this->assertArrayHasKey('clear_button', $element['actions']);
    // Attached library.
    $this->assertSame(['viostream/widget'], $element['#attached']['library']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementLinkField(): void {
    $this->fieldDefinition->method('getType')->willReturn('link');
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $item = new \stdClass();
    $item->uri = 'https://share.viostream.com/vid456';

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('offsetExists')->willReturn(TRUE);
    $items->method('offsetGet')->willReturn($item);

    $formState = $this->createMock(FormStateInterface::class);
    $form = [];

    $element = $this->widget->formElement($items, 0, [], $form, $formState);

    // Link fields use 'uri' key.
    $this->assertArrayHasKey('uri', $element);
    $this->assertSame('hidden', $element['uri']['#type']);
    $this->assertSame('https://share.viostream.com/vid456', $element['uri']['#default_value']);
    // Should show preview with value since it's not empty.
    $this->assertArrayHasKey('content', $element['preview']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementWithValueButNoApiDetail(): void {
    $this->fieldDefinition->method('getType')->willReturn('string');
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn(NULL);

    $item = new \stdClass();
    $item->value = 'https://share.viostream.com/abc';

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('offsetExists')->willReturn(TRUE);
    $items->method('offsetGet')->willReturn($item);

    $formState = $this->createMock(FormStateInterface::class);
    $form = [];

    $element = $this->widget->formElement($items, 0, [], $form, $formState);

    // Has a value but no title resolved from API - should show raw value.
    $this->assertArrayHasKey('content', $element['preview']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementWithValueTitleButNoThumbnail(): void {
    $this->fieldDefinition->method('getType')->willReturn('string');
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getMediaDetail')->willReturn([
      'title' => 'Audio Track',
    ]);

    $item = new \stdClass();
    $item->value = 'https://share.viostream.com/audio1';

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('offsetExists')->willReturn(TRUE);
    $items->method('offsetGet')->willReturn($item);

    $formState = $this->createMock(FormStateInterface::class);
    $form = [];

    $element = $this->widget->formElement($items, 0, [], $form, $formState);

    // Preview should show title content but no thumbnail.
    $this->assertArrayHasKey('content', $element['preview']);
    $context = $element['preview']['content']['#context'] ?? [];
    $this->assertSame('Audio Track', $context['title'] ?? '');
    $this->assertArrayNotHasKey('thumbnail', $context);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementDrupalSettings(): void {
    $this->fieldDefinition->method('getType')->willReturn('string');
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $item = new \stdClass();
    $item->value = '';

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('offsetExists')->willReturn(TRUE);
    $items->method('offsetGet')->willReturn($item);

    $formState = $this->createMock(FormStateInterface::class);
    $form = [];

    $element = $this->widget->formElement($items, 0, [], $form, $formState);

    $this->assertArrayHasKey('drupalSettings', $element['#attached']);
    $this->assertArrayHasKey('viostream', $element['#attached']['drupalSettings']);
    $this->assertArrayHasKey('searchUrl', $element['#attached']['drupalSettings']['viostream']);
    $this->assertArrayHasKey('detailUrlBase', $element['#attached']['drupalSettings']['viostream']);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $this->assertInstanceOf(ViostreamBrowserWidget::class, $this->widget);
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
      'settings' => [],
      'third_party_settings' => [],
    ];

    $widget = ViostreamBrowserWidget::create($container, $configuration, 'viostream_browser', ['id' => 'viostream_browser', 'provider' => 'viostream']);

    $this->assertInstanceOf(ViostreamBrowserWidget::class, $widget);
  }

}
