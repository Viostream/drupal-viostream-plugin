<?php

namespace Drupal\Tests\viostream\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\viostream\Client\ViostreamClient;
use Drupal\viostream\Form\ViostreamSettingsForm;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\viostream\Form\ViostreamSettingsForm
 * @group viostream
 */
class ViostreamSettingsFormTest extends TestCase {

  /**
   * The mock Viostream client.
   */
  protected MockObject $viostreamClient;

  /**
   * The mock config factory.
   */
  protected MockObject $configFactory;

  /**
   * The mock config object.
   */
  protected MockObject $config;

  /**
   * The form under test.
   */
  protected ViostreamSettingsForm $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->viostreamClient = $this->createMock(ViostreamClient::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);

    $this->configFactory->method('getEditable')
      ->with('viostream.settings')
      ->willReturn($this->config);

    $this->configFactory->method('get')
      ->with('viostream.settings')
      ->willReturn($this->config);

    $this->form = new ViostreamSettingsForm($this->viostreamClient);

    // Inject the config factory via reflection (normally done by parent::__construct).
    $ref = new \ReflectionProperty('Drupal\Core\Form\ConfigFormBase', 'configFactory');
    $ref->setAccessible(TRUE);
    $ref->setValue($this->form, $this->configFactory);

    // Set a pass-through string translation.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn(TranslatableMarkup $m) => $m->getUntranslatedString());
    $this->form->setStringTranslation($translation);
  }

  /**
   * @covers ::getFormId
   */
  public function testGetFormId(): void {
    $this->assertSame('viostream_settings', $this->form->getFormId());
  }

  /**
   * @covers ::getEditableConfigNames
   */
  public function testGetEditableConfigNames(): void {
    $ref = new \ReflectionMethod($this->form, 'getEditableConfigNames');
    $ref->setAccessible(TRUE);
    $result = $ref->invoke($this->form);

    $this->assertSame(['viostream.settings'], $result);
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildFormNotConfigured(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(FALSE);

    $this->config->method('get')
      ->willReturnMap([
        ['access_key', 'VC-existing'],
        ['api_key', 'existing-key'],
      ]);

    $formState = $this->createMock(FormStateInterface::class);
    $form = $this->form->buildForm([], $formState);

    $this->assertArrayHasKey('connection', $form);
    $this->assertArrayHasKey('access_key', $form['connection']);
    $this->assertArrayHasKey('api_key', $form['connection']);
    $this->assertArrayHasKey('test_connection', $form['connection']);
    $this->assertArrayHasKey('connection_status', $form['connection']);

    $this->assertSame('textfield', $form['connection']['access_key']['#type']);
    $this->assertSame('textfield', $form['connection']['api_key']['#type']);
    $this->assertSame('button', $form['connection']['test_connection']['#type']);
    $this->assertTrue($form['connection']['access_key']['#required']);
    $this->assertTrue($form['connection']['api_key']['#required']);
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildFormWithConnectedStatus(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getAccountInfo')->willReturn([
      'title' => 'My Account',
      'id' => 'acc-123',
    ]);

    $this->config->method('get')
      ->willReturnMap([
        ['access_key', 'VC-test'],
        ['api_key', 'key-123'],
      ]);

    $formState = $this->createMock(FormStateInterface::class);
    $form = $this->form->buildForm([], $formState);

    // Check that the status message is shown.
    $this->assertArrayHasKey('status', $form['connection']['connection_status']);
    $markup = $form['connection']['connection_status']['status']['#markup'];
    $this->assertStringContainsString('My Account', $markup);
    $this->assertStringContainsString('messages--status', $markup);
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildFormWithFailedConnection(): void {
    $this->viostreamClient->method('isConfigured')->willReturn(TRUE);
    $this->viostreamClient->method('getAccountInfo')->willReturn(NULL);

    $this->config->method('get')
      ->willReturnMap([
        ['access_key', 'VC-test'],
        ['api_key', 'key-123'],
      ]);

    $formState = $this->createMock(FormStateInterface::class);
    $form = $this->form->buildForm([], $formState);

    $markup = $form['connection']['connection_status']['status']['#markup'];
    $this->assertStringContainsString('messages--warning', $markup);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormValidAccessKey(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, 'VC-valid'],
        ['api_key', NULL, 'some-key'],
      ]);

    $formState->expects($this->never())
      ->method('setErrorByName');

    $form = [];
    $this->form->validateForm($form, $formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormInvalidAccessKey(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, 'INVALID-KEY'],
        ['api_key', NULL, 'some-key'],
      ]);

    $formState->expects($this->once())
      ->method('setErrorByName')
      ->with('access_key', $this->anything());

    $form = [];
    $this->form->validateForm($form, $formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormEmptyAccessKey(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, ''],
        ['api_key', NULL, 'some-key'],
      ]);

    // Empty access key should not trigger the VC- validation error.
    $formState->expects($this->never())
      ->method('setErrorByName');

    $form = [];
    $this->form->validateForm($form, $formState);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitForm(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, 'VC-new-key'],
        ['api_key', NULL, 'new-secret'],
      ]);

    $this->config->expects($this->exactly(2))
      ->method('set')
      ->willReturnSelf();

    $this->config->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    $form = [];
    $this->form->submitForm($form, $formState);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $this->assertInstanceOf(ViostreamSettingsForm::class, $this->form);
  }

  /**
   * @covers ::testConnectionAjax
   */
  public function testTestConnectionAjaxEmptyCredentials(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, ''],
        ['api_key', NULL, ''],
      ]);

    $form = [];
    $result = $this->form->testConnectionAjax($form, $formState);

    $this->assertSame('container', $result['#type']);
    $this->assertArrayHasKey('status', $result);
    $this->assertStringContainsString('messages--error', $result['status']['#markup']);
    $this->assertStringContainsString('Please enter both', $result['status']['#markup']);
  }

  /**
   * @covers ::testConnectionAjax
   */
  public function testTestConnectionAjaxSuccess(): void {
    // Set up a mock HTTP client that returns 200 with account data.
    $body = $this->createMock(StreamInterface::class);
    $body->method('getContents')
      ->willReturn(json_encode(['title' => 'My Account', 'id' => 'acc-1']));

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(200);
    $response->method('getBody')->willReturn($body);

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->with('GET', $this->stringContains('/account/info'), $this->callback(function ($options) {
        return $options['auth'] === ['VC-testkey', 'api-secret'];
      }))
      ->willReturn($response);

    // Replace the http_client in the container.
    \Drupal::getContainer()->set('http_client', $httpClient);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, 'VC-testkey'],
        ['api_key', NULL, 'api-secret'],
      ]);

    $form = [];
    $result = $this->form->testConnectionAjax($form, $formState);

    $this->assertSame('container', $result['#type']);
    $this->assertArrayHasKey('status', $result);
    $this->assertStringContainsString('messages--status', $result['status']['#markup']);
    $this->assertStringContainsString('My Account', $result['status']['#markup']);
    $this->assertStringContainsString('acc-1', $result['status']['#markup']);
  }

  /**
   * @covers ::testConnectionAjax
   */
  public function testTestConnectionAjaxNon200Response(): void {
    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(401);

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->method('request')->willReturn($response);

    \Drupal::getContainer()->set('http_client', $httpClient);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, 'VC-bad'],
        ['api_key', NULL, 'wrong-key'],
      ]);

    $form = [];
    $result = $this->form->testConnectionAjax($form, $formState);

    $this->assertStringContainsString('messages--error', $result['status']['#markup']);
    $this->assertStringContainsString('401', $result['status']['#markup']);
  }

  /**
   * @covers ::testConnectionAjax
   */
  public function testTestConnectionAjaxException(): void {
    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->method('request')
      ->willThrowException(new \Exception('Connection timed out'));

    \Drupal::getContainer()->set('http_client', $httpClient);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->method('getValue')
      ->willReturnMap([
        ['access_key', NULL, 'VC-test'],
        ['api_key', NULL, 'key-123'],
      ]);

    $form = [];
    $result = $this->form->testConnectionAjax($form, $formState);

    $this->assertStringContainsString('messages--error', $result['status']['#markup']);
    $this->assertStringContainsString('Connection timed out', $result['status']['#markup']);
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

    $form = ViostreamSettingsForm::create($container);

    $this->assertInstanceOf(ViostreamSettingsForm::class, $form);
  }

}
