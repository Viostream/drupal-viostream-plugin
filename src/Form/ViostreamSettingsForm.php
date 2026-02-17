<?php

namespace Drupal\viostream\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\viostream\Client\ViostreamClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Viostream API settings for this site.
 */
class ViostreamSettingsForm extends ConfigFormBase {

  /**
   * The Viostream API client.
   *
   * @var \Drupal\viostream\Client\ViostreamClient
   */
  protected $viostreamClient;

  /**
   * Constructs a ViostreamSettingsForm object.
   *
   * @param \Drupal\viostream\Client\ViostreamClient $viostream_client
   *   The Viostream API client.
   */
  public function __construct(ViostreamClient $viostream_client) {
    $this->viostreamClient = $viostream_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('viostream.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'viostream_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['viostream.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('viostream.settings');

    $form['connection'] = [
      '#type' => 'details',
      '#title' => $this->t('API Connection'),
      '#open' => TRUE,
    ];

    $form['connection']['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Key'),
      '#default_value' => $config->get('access_key'),
      '#description' => $this->t('Your Viostream Access Key (starts with <code>VC-</code>). This is used as the username for API authentication.'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'placeholder' => 'VC-xxxxxxxx',
      ],
    ];

    $form['connection']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Your API key generated from <strong>Settings &rarr; Developer Tools</strong> in Viostream. This is used as the password for API authentication.'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['connection']['test_connection'] = [
      '#type' => 'button',
      '#value' => $this->t('Test Connection'),
      '#ajax' => [
        'callback' => '::testConnectionAjax',
        'wrapper' => 'viostream-connection-status',
        'effect' => 'fade',
      ],
      '#limit_validation_errors' => [],
    ];

    $form['connection']['connection_status'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'viostream-connection-status',
      ],
    ];

    // Show current connection status if credentials are configured.
    if ($this->viostreamClient->isConfigured()) {
      $account_info = $this->viostreamClient->getAccountInfo();
      if ($account_info) {
        $form['connection']['connection_status']['status'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--status">'
            . $this->t('Connected to Viostream account: <strong>@title</strong>', [
              '@title' => $account_info['title'] ?? $this->t('Unknown'),
            ])
            . '</div>',
        ];
      }
      else {
        $form['connection']['connection_status']['status'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--warning">'
            . $this->t('API credentials are set but connection could not be verified. Please check your credentials.')
            . '</div>',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to test the API connection.
   */
  public function testConnectionAjax(array &$form, FormStateInterface $form_state) {
    $container = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'viostream-connection-status',
      ],
    ];

    // Use submitted values for the test, not saved config.
    $access_key = $form_state->getValue('access_key');
    $api_key = $form_state->getValue('api_key');

    if (empty($access_key) || empty($api_key)) {
      $container['status'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--error">'
          . $this->t('Please enter both an Access Key and API Key.')
          . '</div>',
      ];
      return $container;
    }

    // Temporarily test with submitted credentials.
    try {
      /** @var \GuzzleHttp\ClientInterface $http_client */
      $http_client = \Drupal::httpClient();
      $response = $http_client->request('GET', ViostreamClient::API_BASE_URL . '/account/info', [
        'auth' => [$access_key, $api_key],
        'headers' => [
          'Accept' => 'application/json',
        ],
        'timeout' => 15,
      ]);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody()->getContents(), TRUE);
        $container['status'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--status">'
            . $this->t('Connection successful! Account: <strong>@title</strong> (ID: @id)', [
              '@title' => $data['title'] ?? $this->t('Unknown'),
              '@id' => $data['id'] ?? $this->t('Unknown'),
            ])
            . '</div>',
        ];
      }
      else {
        $container['status'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--error">'
            . $this->t('Connection failed. The API returned status code @code.', [
              '@code' => $response->getStatusCode(),
            ])
            . '</div>',
        ];
      }
    }
    catch (\Exception $e) {
      $container['status'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--error">'
          . $this->t('Connection failed: @message', [
            '@message' => $e->getMessage(),
          ])
          . '</div>',
      ];
    }

    return $container;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $access_key = $form_state->getValue('access_key');
    if (!empty($access_key) && strpos($access_key, 'VC-') !== 0) {
      $form_state->setErrorByName('access_key', $this->t('The Access Key should start with <code>VC-</code>.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('viostream.settings')
      ->set('access_key', $form_state->getValue('access_key'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
