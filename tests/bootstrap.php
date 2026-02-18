<?php

/**
 * @file
 * PHPUnit bootstrap for the Viostream module.
 *
 * Registers PSR-4 namespaces for Drupal module classes that are not
 * covered by the Composer autoloader (filter, ckeditor5, editor modules),
 * and sets up a minimal Drupal container with stub services so that
 * Url::fromRoute(), Url::fromUri(), and \Drupal::messenger() work in tests.
 */

$autoloader = require __DIR__ . '/../vendor/autoload.php';

// Register Drupal module namespaces that aren't in composer's autoload map.
$drupal_core = __DIR__ . '/../vendor/drupal/core';

$module_namespaces = [
  'Drupal\\filter\\' => $drupal_core . '/modules/filter/src',
  'Drupal\\editor\\' => $drupal_core . '/modules/editor/src',
  'Drupal\\ckeditor5\\' => $drupal_core . '/modules/ckeditor5/src',
];

foreach ($module_namespaces as $namespace => $path) {
  if (is_dir($path)) {
    $autoloader->addPsr4($namespace, $path);
  }
}

// Define the DRUPAL_ROOT constant if not already defined.
if (!defined('DRUPAL_ROOT')) {
  define('DRUPAL_ROOT', $drupal_core);
}

// ---------------------------------------------------------------------------
// Set up a minimal Drupal container so Url::fromRoute(), Url::fromUri(),
// and \Drupal::messenger() work in unit tests.
// ---------------------------------------------------------------------------

use Symfony\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();

// --- URL generator stub (for Url::fromRoute()->toString()) ----------------
$urlGenerator = new class implements \Drupal\Core\Routing\UrlGeneratorInterface {

  public function setContext(\Symfony\Component\Routing\RequestContext $context): void {}

  public function getContext(): \Symfony\Component\Routing\RequestContext {
    return new \Symfony\Component\Routing\RequestContext();
  }

  public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string {
    return $this->generateFromRoute($name, $parameters);
  }

  public function getPathFromRoute($name, $parameters = []): string {
    return $this->buildPath($name, $parameters);
  }

  public function generateFromRoute(string $name, array $parameters = [], array $options = [], bool $collect_bubbleable_metadata = FALSE): string|\Drupal\Core\GeneratedUrl {
    $path = $this->buildPath($name, $parameters);
    $url = $path;
    if (!empty($options['query'])) {
      $url .= '?' . http_build_query($options['query']);
    }
    if ($collect_bubbleable_metadata) {
      $generated = new \Drupal\Core\GeneratedUrl();
      $generated->setGeneratedUrl($url);
      return $generated;
    }
    return $url;
  }

  private function buildPath(string $name, array $parameters = []): string {
    // Map known route names to paths.
    $routes = [
      'viostream.media_browser.search' => '/viostream/browser/search',
      'viostream.media_browser.detail' => '/viostream/browser/detail/{media_id}',
      'viostream.settings' => '/admin/config/media/viostream',
    ];
    $path = $routes[$name] ?? '/' . str_replace('.', '/', $name);
    // Replace placeholders with parameter values.
    foreach ($parameters as $key => $value) {
      $path = str_replace('{' . $key . '}', $value, $path);
    }
    return $path;
  }

};

$container->set('url_generator', $urlGenerator);

// --- Unrouted URL assembler stub (for Url::fromUri()->toString()) ---------
$unroutedUrlAssembler = new class implements \Drupal\Core\Utility\UnroutedUrlAssemblerInterface {

  public function assemble($uri, array $options = [], $collect_bubbleable_metadata = FALSE): string|\Drupal\Core\GeneratedUrl {
    $url = $uri;
    if (!empty($options['query'])) {
      $url .= '?' . http_build_query($options['query']);
    }
    if (!empty($options['fragment'])) {
      $url .= '#' . $options['fragment'];
    }
    if ($collect_bubbleable_metadata) {
      $generated = new \Drupal\Core\GeneratedUrl();
      $generated->setGeneratedUrl($url);
      return $generated;
    }
    return $url;
  }

};

$container->set('unrouted_url_assembler', $unroutedUrlAssembler);

// --- Messenger stub (for \Drupal::messenger()) ----------------------------
$messenger = new class implements \Drupal\Core\Messenger\MessengerInterface {

  private array $messages = [];

  public function addMessage($message, $type = self::TYPE_STATUS, $repeat = FALSE): self {
    $this->messages[$type][] = (string) $message;
    return $this;
  }

  public function addStatus($message, $repeat = FALSE): self {
    return $this->addMessage($message, self::TYPE_STATUS, $repeat);
  }

  public function addError($message, $repeat = FALSE): self {
    return $this->addMessage($message, self::TYPE_ERROR, $repeat);
  }

  public function addWarning($message, $repeat = FALSE): self {
    return $this->addMessage($message, self::TYPE_WARNING, $repeat);
  }

  public function all(): array {
    return $this->messages;
  }

  public function messagesByType($type): array {
    return $this->messages[$type] ?? [];
  }

  public function deleteAll(): array {
    $messages = $this->messages;
    $this->messages = [];
    return $messages;
  }

  public function deleteByType($type): array {
    $messages = $this->messages[$type] ?? [];
    unset($this->messages[$type]);
    return $messages;
  }

};

$container->set('messenger', $messenger);

// --- HTTP client stub (for \Drupal::httpClient()) -------------------------
// This is a configurable stub; tests can replace it with a mock via
// \Drupal::getContainer()->set('http_client', $mock).
$httpClient = new class implements \GuzzleHttp\ClientInterface {

  public function send(\Psr\Http\Message\RequestInterface $request, array $options = []): \Psr\Http\Message\ResponseInterface {
    throw new \RuntimeException('Not implemented in test stub');
  }

  public function sendAsync(\Psr\Http\Message\RequestInterface $request, array $options = []): \GuzzleHttp\Promise\PromiseInterface {
    throw new \RuntimeException('Not implemented in test stub');
  }

  public function request(string $method, $uri = '', array $options = []): \Psr\Http\Message\ResponseInterface {
    throw new \RuntimeException('Not implemented in test stub');
  }

  public function requestAsync(string $method, $uri = '', array $options = []): \GuzzleHttp\Promise\PromiseInterface {
    throw new \RuntimeException('Not implemented in test stub');
  }

  public function getConfig(?string $option = NULL): mixed {
    return NULL;
  }

};

$container->set('http_client', $httpClient);

// --- Logger factory stub (for \Drupal::logger()) --------------------------
$loggerFactory = new class implements \Drupal\Core\Logger\LoggerChannelFactoryInterface {

  public function get($channel): \Psr\Log\LoggerInterface {
    return new class extends \Psr\Log\AbstractLogger {

      public function log($level, $message, array $context = []): void {
        // No-op in test stub.
      }

    };
  }

  public function addLogger(\Psr\Log\LoggerInterface $logger, $priority = 0): void {}

};

$container->set('logger.factory', $loggerFactory);

// Register the container with Drupal.
\Drupal::setContainer($container);
