<?php

namespace Drupal\Tests\stage_file_proxy\Kernel;

use GuzzleHttp\Client;
use Drupal\stage_file_proxy\FetchManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test stage file proxy module.
 *
 * @coversDefaultClass \Drupal\stage_file_proxy\FetchManager
 *
 * @group stage_file_proxy
 */
class FetchManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'file'];

  /**
   * FetchManager object.
   *
   * @var \Drupal\stage_file_proxy\FetchManager
   */
  protected $fetchManager;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The file logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Filesystem interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Before a test method is run, setUp() is invoked.
   *
   * Create new fetchManager object.
   */
  public function setUp() {
    parent::setUp();

    $this->fileSystem = $this->container->get('file_system');
    $this->config('system.file')->set('default_scheme', 'public')->save();
    $this->client = new Client();
    $this->logger = \Drupal::logger('test_logger');
    $this->configFactory = $this->container->get('config.factory');

    $this->fetchManager = new FetchManager($this->client, $this->fileSystem, $this->logger, $this->configFactory);
  }

  /**
   * @covers Drupal\stage_file_proxy\FetchManager::styleOriginalPath
   */
  public function testStyleOriginalPath() {
    // Test image style path assuming public file scheme.
    $this->assertEquals('public://example.jpg', $this->fetchManager->styleOriginalPath('styles/icon_50x50_/public/example.jpg'));
  }

  /**
   * Clean up.
   *
   * Once test method has finished running, whether it succeeded or failed,
   * tearDown() will be invoked. Unset the $fetchManager object.
   */
  public function tearDown() {
    unset($this->fetchManager);
  }

}
