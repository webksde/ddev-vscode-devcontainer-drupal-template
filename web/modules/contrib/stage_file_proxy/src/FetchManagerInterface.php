<?php

namespace Drupal\stage_file_proxy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Interface for FetchManager.
 */
interface FetchManagerInterface {

  /**
   * Constructs an FetchManager instance.
   *
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(Client $client, FileSystemInterface $file_system, LoggerInterface $logger, ConfigFactoryInterface $config_factory);

  /**
   * Downloads a remote file and saves it to the local files directory.
   *
   * @param string $server
   *   The origin server URL.
   * @param string $remote_file_dir
   *   The relative path to the files directory on the origin server.
   * @param string $relative_path
   *   The path to the requested resource relative to the files directory.
   * @param array $options
   *   Options for the request.
   *
   * @return bool
   *   Returns true if the content was downloaded, otherwise false.
   */
  public function fetch($server, $remote_file_dir, $relative_path, array $options);

  /**
   * Helper to retrieve the file directory.
   */
  public function filePublicPath();

  /**
   * Helper to retrieves original path for a styled image.
   *
   * @param string $uri
   *   A uri or path (may be prefixed with scheme).
   * @param bool $style_only
   *   Indicates if, the function should only return paths retrieved from style
   *   paths. Defaults to TRUE.
   *
   * @return bool|mixed|string
   *   A file URI pointing to the given original image.
   *   If $style_only is set to TRUE and $uri is no style-path, FALSE is
   *   returned.
   */
  public function styleOriginalPath($uri, $style_only = TRUE);

}
