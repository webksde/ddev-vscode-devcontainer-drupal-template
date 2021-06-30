<?php

namespace Drupal\stage_file_proxy;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Fetch manager.
 */
class FetchManager implements FetchManagerInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $client, FileSystemInterface $file_system, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->client = $client;
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($server, $remote_file_dir, $relative_path, array $options) {
    try {
      // Fetch remote file.
      $url = $server . '/' . UrlHelper::encodePath($remote_file_dir . '/' . $relative_path);
      $options['Connection'] = 'close';
      $response = $this->client->get($url, $options);

      $result = $response->getStatusCode();
      if ($result != 200) {
        $this->logger->warning('HTTP error @errorcode occurred when trying to fetch @remote.', [
          '@errorcode' => $result,
          '@remote' => $url,
        ]);
        return FALSE;
      }

      // Prepare local target directory and save downloaded file.
      $file_dir = $this->filePublicPath();
      $destination = $file_dir . '/' . dirname($relative_path);
      if (!$this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->logger->error('Unable to prepare local directory @path.', ['@path' => $destination]);
        return FALSE;
      }

      $destination = str_replace('///', '//', "$destination/") . $this->fileSystem->basename($relative_path);

      $response_headers = $response->getHeaders();
      $content_length = array_shift($response_headers['Content-Length']);
      $response_data = $response->getBody()->getContents();
      if (isset($content_length) && strlen($response_data) != $content_length) {
        $this->logger->error('Incomplete download. Was expecting @content-length bytes, actually got @data-length.', [
          '@content-length' => $content_length,
          '@data-length' => $content_length,
        ]);
        return FALSE;
      }

      if ($this->writeFile($destination, $response_data)) {
        return TRUE;
      }
      $this->logger->error('@remote could not be saved to @path.', ['@remote' => $url, '@path' => $destination]);
      return FALSE;
    }
    catch (GuzzleException $e) {
      // Do nothing.
    }
    $this->logger->error('Stage File Proxy encountered an unknown error by retrieving file @file', ['@file' => $server . '/' . UrlHelper::encodePath($remote_file_dir . '/' . $relative_path)]);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function filePublicPath() {
    return PublicStream::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public function styleOriginalPath($uri, $style_only = TRUE) {
    $scheme = StreamWrapperManager::getScheme($uri);
    if ($scheme) {
      $path = StreamWrapperManager::getTarget($uri);
    }
    else {
      $path = $uri;
      $scheme = $this->configFactory->get('system.file')->get('default_scheme');
    }

    // It is a styles path, so we extract the different parts.
    if (strpos($path, 'styles') === 0) {
      // Then the path is like styles/[style_name]/[schema]/[original_path].
      return preg_replace('/styles\/.*\/(.*)\/(.*)/U', '$1://$2', $path);
    }
    // Else it seems to be the original.
    elseif ($style_only == FALSE) {
      return "$scheme://$path";
    }
    else {
      return FALSE;
    }
  }

  /**
   * Use write & rename instead of write.
   *
   * Perform the replace operation. Since there could be multiple processes
   * writing to the same file, the best option is to create a temporary file in
   * the same directory and then rename it to the destination. A temporary file
   * is needed if the directory is mounted on a separate machine; thus ensuring
   * the rename command stays local.
   *
   * @param string $destination
   *   A string containing the destination location.
   * @param string $data
   *   A string containing the contents of the file.
   *
   * @return bool
   *   True if write was successful. False if write or rename failed.
   */
  protected function writeFile($destination, $data) {
    // Get a temporary filename in the destination directory.
    $dir = $this->fileSystem->dirname($destination) . '/';
    $temporary_file = $this->fileSystem->tempnam($dir, 'stage_file_proxy_');
    $temporary_file_copy = $temporary_file;

    // Get the extension of the original filename and append it to the temp file
    // name. Preserves the mime type in different stream wrapper
    // implementations.
    $parts = pathinfo($destination);
    $extension = '.' . $parts['extension'];
    if ($extension === '.gz') {
      $parts = pathinfo($parts['filename']);
      $extension = '.' . $parts['extension'] . $extension;
    }
    // Move temp file into the destination dir if not in there.
    // Add the extension on as well.
    $temporary_file = str_replace(substr($temporary_file, 0, strpos($temporary_file, 'stage_file_proxy_')), $dir, $temporary_file) . $extension;

    // Preform the rename, adding the extension to the temp file.
    if (!@rename($temporary_file_copy, $temporary_file)) {
      // Remove if rename failed.
      @unlink($temporary_file_copy);
      return FALSE;
    }

    // Save to temporary filename in the destination directory.
    $filepath = $this->fileSystem->saveData($data, $temporary_file, FileSystemInterface::EXISTS_REPLACE);

    // Perform the rename operation if the write succeeded.
    if ($filepath) {
      if (!@rename($filepath, $destination)) {
        // Unlink and try again for windows. Rename on windows does not replace
        // the file if it already exists.
        @unlink($destination);
        if (!@rename($filepath, $destination)) {
          // Remove temporary_file if rename failed.
          @unlink($filepath);
        }
      }
    }

    // Final check; make sure file exists & is not empty.
    $result = FALSE;
    if (file_exists($destination) & filesize($destination) != 0) {
      $result = TRUE;
    }
    return $result;
  }

}
