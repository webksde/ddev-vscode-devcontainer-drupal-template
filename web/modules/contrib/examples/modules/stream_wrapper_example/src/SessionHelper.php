<?php

namespace Drupal\stream_wrapper_example;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Helper to manage file wrapper data stored in the session object.
 */
class SessionHelper {

  /**
   * Keep the top-level "file system" area in one place.
   */
  const SESSION_BASE_ATTRIBUTE = 'stream_wrapper_example';

  /**
   * Representation of the current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * This is the current location in our store.
   *
   * @var string
   */
  protected $storePath;

  /**
   * Construct our helper object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   An object used to read data from the current HTTP request.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->storePath = '';
  }

  /**
   * Get a fresh session object.
   *
   * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
   *   A session object.
   */
  protected function getSession() {
    return $this->requestStack->getCurrentRequest()->getSession();
  }

  /**
   * Get the contents of the session filesystem.
   *
   * @return array
   *   An associated array where scalar data represents a file, and arrays
   *   represent directories.
   */
  protected function getStore() {
    $session = $this->getSession();
    $store = $session->get(static::SESSION_BASE_ATTRIBUTE, []);
    return $store;
  }

  /**
   * Set the contents of our session filesystem.
   *
   * @param array $store
   *   The whole filesystem represented as an array.
   */
  protected function setStore(array $store) {
    $session = $this->getSession();
    $session->set(static::SESSION_BASE_ATTRIBUTE, $store);
  }

  /**
   * Turn a path into the arrays we use internally.
   *
   * @param string $path
   *   Path into the store.
   * @param bool $is_dir
   *   Path will be used as a container. Otherwise, path is a scalar.
   *
   * @return array|bool
   *   Return an array containing the "bottom" and "tip" of a directory
   *   hierarchy.  You will want to save the 'bottom' array, but you may
   *   need to manipulate an object at the very tip of the hierarchy
   *   as defined in the path. The tip will be a string if we are scalar
   *   and an array otherwise.  Since we don't want to create new
   *   sub arrays as a side effect, we return FALSE the intervening path
   *   does not exist.
   */
  public function processPath($path, $is_dir = FALSE) {
    // We need to create a reference into the store for the point
    // the of the path, so get a copy of the store.
    $store = $this->getStore();

    if (empty($path)) {
      return ['store' => &$store, 'tip' => &$store];
    }
    $hierarchy = explode('/', $path);
    if (empty($hierarchy) or empty($hierarchy[0])) {
      return ['store' => &$store, 'tip' => &$store];
    }
    $bottom =& $store;
    $tip = array_pop($hierarchy);

    foreach ($hierarchy as $dir) {
      if (!isset($bottom[$dir])) {
        // If the path does not exist, DO NOT create it.
        // That is handled by the stream wrapper code.
        return FALSE;
      }
      $new_tip =& $bottom[$dir];
      $bottom =& $new_tip;
    }
    // If the hierarchy was empty, just point to the object.
    $new_tip =& $bottom[$tip];
    $bottom =& $new_tip;
    return ['store' => &$store, 'tip' => &$bottom];
  }

  /**
   * The equivalent to dirname() and basename() for a path.
   *
   * @param string $path
   *   A file-system like path string.
   *
   * @return array
   *   Associative array defining an interal path of our data store.   .
   */
  public function getParentPath($path) {
    $dirs = explode('/', $path);
    $tip = array_pop($dirs);
    $parent = implode('/', $dirs);
    return ['dirname' => $parent, 'basename' => $tip];
  }

  /**
   * Clear a path into our store.
   *
   * @param string $path
   *   The path portion of a URI (i.e., without the SCHEME://).
   */
  public function clearPath($path) {

    $this->getStore();
    if ($this->checkPath($path)) {
      $path_info = $this->getParentPath($path);
      $store_info = $this->processPath($path_info['dirname']);
      if ($store_info === FALSE) {
        // The path was not found, nothing to do.
        return;

      }
      // We want to clear the key at the tip, so...
      unset($store_info['tip'][$path_info['basename']]);
      // Write back to the store.
      $this->setStore($store_info['store']);
    }

  }

  /**
   * Get a path.
   *
   * @param string $path
   *   A URI with the SCHEME:// part removed.
   *
   * @return mixed
   *   Return the stored value at this "node" of the store.
   */
  public function getPath($path) {
    $path_info = $this->getParentPath($path);
    $store_info = $this->processPath(($path_info['dirname']));
    $leaf = $path_info['basename'];
    if ($store_info === FALSE) {
      return NULL;
    }
    if ($store_info['store'] === $store_info['tip']) {
      // We are at the top of the hierarchy; return the store itself.
      if (empty($path_info['basename'])) {
        return $store_info['store'];
      }
      if (!isset($store_info['store'][$leaf])) {
        return NULL;
      }
    }
    return $store_info['tip'][$leaf];
  }

  /**
   * Set a path.
   *
   * @param string $path
   *   Path into the store.
   * @param string|array $value
   *   Set a value.
   */
  public function setPath($path, $value) {
    $path_info = $this->getParentPath($path);
    $store_info = $this->processPath(($path_info['dirname']));
    if ($store_info !== FALSE) {
      $store_info['tip'][$path_info['basename']] = $value;
    }
    $this->setStore($store_info['store']);
  }

  /**
   * Does path exist?
   *
   * @param string $path
   *   Path into the store.
   *
   * @return bool
   *   Existed or not.
   */
  public function checkPath($path) {
    $path_info = $this->getParentPath($path);
    $store_info = $this->processPath($path_info['dirname']);
    if (empty($store_info)) {
      // Containing directory did not exist.
      return FALSE;
    }
    // Check if we are at the root of a directory.
    if ($path_info['basename'] === '') {
      return TRUE;
    }
    return isset($store_info['tip'][$path_info['basename']]);
  }

  /**
   * Zero out the store.
   */
  public function cleanUpStore() {
    $session = $this->getSession();
    $session->remove(static::SESSION_BASE_ATTRIBUTE);
  }

}
