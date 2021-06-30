<?php

namespace Drupal\stage_file_proxy\Commands;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\stage_file_proxy\FetchManagerInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Drush commands for Stage File Proxy.
 */
class StageFileProxyCommands extends DrushCommands {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The stage_file_proxy.fetch_manager service.
   *
   * @var \Drupal\stage_file_proxy\FetchManagerInterface
   */
  protected $fetchManager;

  /**
   * The logger.channel.stage_file_proxy service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The module config.
   *
   * Not called "config": name is used by Drush to store a DrushConfig instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $moduleConfig;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * StageFileProxyCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\stage_file_proxy\FetchManagerInterface $fetchManager
   *   The stage_file_proxy.fetch_manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.channel.stage_file_proxy service.
   * @param string $root
   *   The app root.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    Connection $database,
    FetchManagerInterface $fetchManager,
    LoggerInterface $logger,
    string $root
  ) {
    parent::__construct();

    $this->moduleConfig = $configFactory->get('stage_file_proxy.settings');
    $this->database = $database;
    $this->fetchManager = $fetchManager;
    $this->logger = $logger;
    $this->root = $root;
  }

  /**
   * Download all managed files from the origin.
   *
   * @command stage_file_proxy:dl
   * @aliases stage-file-proxy-dl,sfdl
   * @option skip-progress-bar Skip displaying a progress bar.
   */
  public function dl(array $command_options = ['skip-progress-bar' => FALSE]) {
    $logger = $this->logger();
    $server = $this->moduleConfig->get('origin');
    if (empty($server)) {
      throw new \Exception('Configure stage_file_proxy.settings.origin in your settings.php (see INSTALL.txt).');
    }

    $query = $this->database->select('file_managed', 'fm');
    $results = $query->fields('fm', ['uri'])
      ->orderBy('fm.fid', 'DESC')
      ->execute()
      ->fetchCol();

    $fileDir = $this->fetchManager->filePublicPath();
    $remoteFileDir = trim($this->moduleConfig->get('origin_dir'));
    if (!$remoteFileDir) {
      $remoteFileDir = $fileDir;
    }

    $gotFilesNumber = 0;
    $errorFilesNumber = 0;
    $notPublicFilesNumber = 0;
    $results_number = count($results);

    $publicPrefix = 'public://';
    $logger->notice('Downloading {count} files.', [
      'count' => $results_number,
    ]);
    $options = [
      'verify' => $this->moduleConfig->get('verify'),
    ];

    $progress_bar = NULL;
    if (!$command_options['skip-progress-bar']) {
      $progress_bar = new ProgressBar($this->output(), $results_number);
    }
    foreach ($results as $uri) {
      if (strpos($uri, $publicPrefix) !== 0) {
        $notPublicFilesNumber++;
        if ($progress_bar) {
          $progress_bar->advance();
        }
        continue;
      }

      $relativePath = mb_substr($uri, mb_strlen($publicPrefix));

      if (file_exists("{$this->root}/{$fileDir}/{$relativePath}")) {
        if ($progress_bar) {
          $progress_bar->advance();
        }
        continue;
      }

      try {
        if ($this->fetchManager->fetch($server, $remoteFileDir, $relativePath, $options)) {
          $gotFilesNumber++;
        }
        else {
          $errorFilesNumber++;
          $logger->error('Stage File Proxy encountered an unknown error by retrieving file {file}', [
            'file' => $server . '/' . UrlHelper::encodePath("{$remoteFileDir}/{$relativePath}"),
          ]);
        }
      }
      catch (ClientException $e) {
        $errorFilesNumber++;
        $logger->error($e->getMessage());
      }

      if ($progress_bar) {
        $progress_bar->advance();
      }
    }

    if ($progress_bar) {
      $progress_bar->finish();
    }

    $logger->notice('{gotFilesNumber} downloaded files.', [
      'gotFilesNumber' => $gotFilesNumber,
    ]);

    if ($errorFilesNumber) {
      $logger->error('{count} file(s) having an error, see log.', [
        'count' => $errorFilesNumber,
      ]);
    }

    if ($notPublicFilesNumber) {
      $logger->error('{count} file(s) not in public directory.', [
        'count' => $notPublicFilesNumber,
      ]);
    }
  }

}
