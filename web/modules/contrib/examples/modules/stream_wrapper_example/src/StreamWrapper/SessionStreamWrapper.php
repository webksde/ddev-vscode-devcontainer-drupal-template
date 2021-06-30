<?php

namespace Drupal\stream_wrapper_example\StreamWrapper;

use Drupal\Component\Utility\Html;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Example stream wrapper class to handle session:// streams.
 *
 * This is just an example, as it could have horrible results if much
 * information were placed in the session object. However, it does
 * demonstrate both the read and write implementation of a stream wrapper.
 * You should *never* do this on any website accessable on the open
 * Internet.
 *
 * A "stream" is an important Unix concept for the reading and writing of
 * files and other devices. Reading or writing a "stream" just means that you
 * open some device, file, internet site, or whatever, and you don't have to
 * know at all what it is. All the functions that deal with it are the same.
 * You can read/write more from/to the stream, seek a position in the stream,
 * or anything else without the code that does it even knowing what kind
 * of device it is talking to. This Unix idea is extended into PHP's
 * mindset.
 *
 * The idea of "stream wrapper" is that this can be extended indefinitely.
 * The classic example is HTTP: With PHP you can do a
 * file_get_contents("http://drupal.org/projects") as if it were a file,
 * because the scheme "http" is supported natively in PHP. So Drupal adds
 * the public:// and private:// schemes, and contrib modules can add any
 * scheme they want to. This example adds the session:// scheme, which allows
 * reading and writing the 'stream_wrapper_example' key of the session object as
 * if it were a file.
 *
 * Drupal makes use of this concept to implement custom URI types like
 * "private://" and "public://".  To implement a stream wrapper, reading
 * the implementation of these stream wrappers is a very good way to get
 * started.
 *
 * To implement a stream wrapper in Drupal, you should do the following:
 *
 *  1. Create a class that implements the StreamWrapperInterface
 *     (Drupal\Core\StreamWrapper\StreamWrapperInterface).
 *
 *  2. Register the class with Drupal.  The best way to do this is to
 *     define a service in your MY_MODULE.services.yml file.  The
 *     service needs to be "tagged" with the scheme you want to implement,
 *     and, as so:
 *
 * @code
 *         tags:
 *           - { name: stream_wrapper, scheme: session }
 * @endcode
 *      See stream_wrapper_example.services.yml for an example.
 *
 *  3. (Optional) If you want to be able to access your files over the web,
 *     you need to add a route that handles, and implement hook_file_download().
 *     See stream_wrapper_example.routing.yml for an example of this, and
 *     file.module for the hook implementation.
 *
 * Note that because this implementation uses simple PHP arrays it is limited to
 * string values, so binary files will not work correctly. Only text files can
 * be used.
 *
 * Also, experienced Drupal coders will notice that we are violating
 * one of Drupal's coding standards here: normally, you should use "camelCase"
 * for the names of your public functions. We cannot do this here, since PHP
 * itself defines the interface used to interact with stream wrappers. Since PHP
 * uses names_like_this we are required to do the same here. We've turned off
 * PHPCS for those method names in our implementation using the
 * 'codingStandardsIgnore' annotation.
 *
 * @ingroup stream_wrapper_example
 */
class SessionStreamWrapper implements StreamWrapperInterface {

  use StringTranslationTrait;

  /**
   * The session helper service.
   *
   * @var \Drupal\stream_wrapper_example\SessionHelper
   */
  protected $sessionHelper;

  /**
   * Instance URI (stream).
   *
   * These streams will be references as 'session://example_target'
   *
   * @var string
   */
  protected $uri;

  /**
   * The content of the stream.
   *
   * Since this trivial example uses the session object, this is a reference to
   * the the session object's 'stream_wrapper_example' key.
   *
   * @var array
   */
  protected $sessionContent;

  /**
   * Pointer to where we are in a directory read.
   *
   * @var int
   */
  protected $directoryPointer;

  /**
   * List of keys in a given directory.
   *
   * @var string[]
   */
  protected $directoryKeys;

  /**
   * The pointer to the next read or write within the session variable.
   *
   * @var int
   */
  protected $streamPointer;

  /**
   * The mode we are currently in.
   *
   * Possible values are FALSE, 'r', 'w'.
   *
   * @var mixed
   */
  protected $streamMode;

  /**
   * Returns the type of stream wrapper.
   *
   * @return int
   *   See StreamWrapperInterface for permissible values.
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * Constructor method.
   *
   * Note this cannot take any arguments; PHP's stream wrapper users
   * do not know how to supply them.
   *
   * @todo Refactor helper injection after https://www.drupal.org/node/3048126
   */
  public function __construct() {
    // Dependency injection will not work here, since PHP doesn't give us a
    // chance to perform the injection. PHP creates the stream wrapper objects
    // automatically when certain file functions are called. Therefore we'll use
    // the \Drupal service locator.
    // phpcs:ignore
    $this->sessionHelper = \Drupal::service('stream_wrapper_example.session_helper');
    $this->sessionHelper->setPath('.isadir.txt', TRUE);
    $this->streamMode = FALSE;
  }

  /**
   * Returns the name of the stream wrapper for use in the UI.
   *
   * @return string
   *   The stream wrapper name.
   */
  public function getName() {
    return $this->t('Session stream wrapper example files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Simulated file system using your session storage. Not for real use!');
  }

  /**
   * Implements setUri().
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Implements getUri().
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Overrides getExternalUrl().
   *
   * We have set up a helper function and menu entry to provide access to this
   * key via HTTP; normally it would be accessible some other way.
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getLocalPath());
    return Url::fromRoute('stream_wrapper_example.files.session', [
      'filepath' => $path,
      'scheme' => 'session',
    ], ['absolute' => TRUE])
      ->toString(FALSE);
  }

  /**
   * Returns canonical, absolute path of the resource.
   *
   * Implementation placeholder. PHP's realpath() does not support stream
   * wrappers. We provide this as a default so that individual wrappers may
   * implement their own solutions.
   *
   * @return string
   *   Returns a string with absolute pathname on success (implemented
   *   by core wrappers), or FALSE on failure or if the registered
   *   wrapper does not provide an implementation.
   */
  public function realpath() {
    return 'session://' . $this->getLocalPath();
  }

  /**
   * Returns the local path.
   *
   * In our case, the local path is the URI minus the wrapper type. So a URI
   * like 'session://one/two/three.txt' becomes 'one/two/three.txt'.
   *
   * @param string $uri
   *   Optional URI, supplied when doing a move or rename.
   *
   * @return string
   *   The local path.
   */
  protected function getLocalPath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    $path = str_replace('session://', '', $uri);
    $path = trim($path, '/');
    return $path;
  }

  /**
   * Opens a stream, as for fopen(), file_get_contents(), file_put_contents().
   *
   * @param string $uri
   *   A string containing the URI to the file to open.
   * @param string $mode
   *   The file mode ("r", "wb" etc.).
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string &$opened_path
   *   A string containing the path actually opened.
   *
   * @return bool
   *   Returns TRUE if file was opened successfully. (Always returns TRUE).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
// @codingStandardsIgnoreStart
  public function stream_open($uri, $mode, $options, &$opened_path) {
// @codingStandardsIgnoreEnd
    $this->uri = $uri;
    $path = $this->getLocalPath($uri);
    // We will support two modes only, 'r' and 'w'.  If the key is 'r',
    // check to make sure the file is there.
    if (stristr($mode, 'r') !== FALSE) {
      if (!$this->sessionHelper->checkPath($path)) {
        return FALSE;
      }
      else {
        $buffer = $this->sessionHelper->getPath($path);
        if (!is_string($buffer)) {
          return FALSE;
        }
        $this->sessionContent = $buffer;
      }
      $this->streamMode = 'r';
    }
    else {
      $this->sessionContent = '';
      $this->streamMode = 'w';
    }
    // Reset the stream pointer since this is an open.
    $this->streamPointer = 0;
    return TRUE;
  }

  /**
   * Retrieve the underlying stream resource.
   *
   * This method is called in response to stream_select().
   *
   * @param int $cast_as
   *   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling
   *   stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for
   *   other uses.
   *
   * @return resource|false
   *   The underlying stream resource or FALSE if stream_select() is not
   *   supported.
   *
   * @see stream_select()
   * @see http://php.net/manual/streamwrapper.stream-cast.php
   */
// @codingStandardsIgnoreStart
  public function stream_cast($cast_as) {
// @codingStandardsIgnoreEnd
    return FALSE;
  }

  /**
   * Sets metadata on the stream.
   *
   * @param string $path
   *   A string containing the URI to the file to set metadata on.
   * @param int $option
   *   One of:
   *   - STREAM_META_TOUCH: The method was called in response to touch().
   *   - STREAM_META_OWNER_NAME: The method was called in response to chown()
   *     with string parameter.
   *   - STREAM_META_OWNER: The method was called in response to chown().
   *   - STREAM_META_GROUP_NAME: The method was called in response to chgrp().
   *   - STREAM_META_GROUP: The method was called in response to chgrp().
   *   - STREAM_META_ACCESS: The method was called in response to chmod().
   * @param mixed $value
   *   If option is:
   *   - STREAM_META_TOUCH: Array consisting of two arguments of the touch()
   *     function.
   *   - STREAM_META_OWNER_NAME or STREAM_META_GROUP_NAME: The name of the owner
   *     user/group as string.
   *   - STREAM_META_OWNER or STREAM_META_GROUP: The value of the owner
   *     user/group as integer.
   *   - STREAM_META_ACCESS: The argument of the chmod() as integer.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure. If $option is not
   *   implemented, FALSE should be returned.
   *
   * @see http://www.php.net/manual/streamwrapper.stream-metadata.php
   */
// @codingStandardsIgnoreStart
  public function stream_metadata($path, $option, $value) {
// @codingStandardsIgnoreEnd
    // We don't really do any of these, but we want to reassure the calling code
    // that there is no problem with chown or chgrp, even though we do not
    // actually support these.
    return TRUE;
  }

  /**
   * Change stream options.
   *
   * This method is called to set options on the stream.
   *
   * @param int $option
   *   One of:
   *   - STREAM_OPTION_BLOCKING: The method was called in response to
   *     stream_set_blocking().
   *   - STREAM_OPTION_READ_TIMEOUT: The method was called in response to
   *     stream_set_timeout().
   *   - STREAM_OPTION_WRITE_BUFFER: The method was called in response to
   *     stream_set_write_buffer().
   * @param int $arg1
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: The requested blocking mode:
   *     - 1 means blocking.
   *     - 0 means not blocking.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in seconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The buffer mode, STREAM_BUFFER_NONE or
   *     STREAM_BUFFER_FULL.
   * @param int $arg2
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: This option is not set.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in microseconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The requested buffer size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise. If $option is not implemented, FALSE
   *   should be returned.
   */
// @codingStandardsIgnoreStart
  public function stream_set_option($option, $arg1, $arg2) {
// @codingStandardsIgnoreEnd
    return FALSE;
  }

  /**
   * Truncate stream.
   *
   * Will respond to truncation; e.g., through ftruncate().
   *
   * @param int $new_size
   *   The new size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   *
   * @todo
   *   Allow truncating the stream.
   *   https://www.drupal.org/project/examples/issues/2992398
   */
// @codingStandardsIgnoreStart
  public function stream_truncate($new_size) {
// @codingStandardsIgnoreEnd
    return FALSE;
  }

  /**
   * Support for flock().
   *
   * The session object has no locking capability, so return TRUE.
   *
   * @param int $operation
   *   One of the following:
   *   - LOCK_SH to acquire a shared lock (reader).
   *   - LOCK_EX to acquire an exclusive lock (writer).
   *   - LOCK_UN to release a lock (shared or exclusive).
   *   - LOCK_NB if you don't want flock() to block while locking (not
   *     supported on Windows).
   *
   * @return bool
   *   Always returns TRUE at the present time. (no support)
   *
   * @see http://php.net/manual/en/streamwrapper.stream-lock.php
   */
// @codingStandardsIgnoreStart
  public function stream_lock($operation) {
// @codingStandardsIgnoreEnd
    return TRUE;
  }

  /**
   * Support for fread(), file_get_contents() etc.
   *
   * @param int $count
   *   Maximum number of bytes to be read.
   *
   * @return string
   *   The string that was read, or FALSE in case of an error.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-read.php
   */
// @codingStandardsIgnoreStart
  public function stream_read($count) {
// @codingStandardsIgnoreEnd
    if (is_string($this->sessionContent)) {
      $remaining_chars = strlen($this->sessionContent) - $this->streamPointer;
      $number_to_read = min($count, $remaining_chars);
      if ($remaining_chars > 0) {
        $buffer = substr($this->sessionContent, $this->streamPointer, $number_to_read);
        $this->streamPointer += $number_to_read;
        return $buffer;
      }
    }
    return FALSE;
  }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * @param string $data
   *   The string to be written.
   *
   * @return int
   *   The number of bytes written (integer).
   *
   * @see http://php.net/manual/en/streamwrapper.stream-write.php
   */
// @codingStandardsIgnoreStart
  public function stream_write($data) {
// @codingStandardsIgnoreEnd
    // Sanitize the data in a simple way since we're putting it into the
    // session variable.
    $data = Html::escape($data);
    $this->sessionContent = substr_replace($this->sessionContent, $data, $this->streamPointer);
    $this->streamPointer += strlen($data);
    return strlen($data);
  }

  /**
   * Support for feof().
   *
   * @return bool
   *   TRUE if end-of-file has been reached.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-eof.php
   */
// @codingStandardsIgnoreStart
  public function stream_eof() {
// @codingStandardsIgnoreEnd
    return FALSE;
  }

  /**
   * Support for fseek().
   *
   * @param int $offset
   *   The byte offset to got to.
   * @param int $whence
   *   SEEK_SET, SEEK_CUR, or SEEK_END.
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-seek.php
   */
// @codingStandardsIgnoreStart
  public function stream_seek($offset, $whence = SEEK_SET) {
// @codingStandardsIgnoreEnd
    if (strlen($this->sessionContent) >= $offset) {
      $this->streamPointer = $offset;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Support for fflush().
   *
   * @return bool
   *   TRUE if data was successfully stored (or there was no data to store).
   *   This always returns TRUE, as this example provides and needs no
   *   flush support.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-flush.php
   */
// @codingStandardsIgnoreStart
  public function stream_flush() {
// @codingStandardsIgnoreEnd
    if ($this->streamMode == 'w') {
      // Since we aren't writing directly to the session, we need to send
      // the bytes on to the store.
      $path = $this->getLocalPath($this->uri);
      $this->sessionHelper->setPath($path, $this->sessionContent);
      $this->sessionContent = '';
      $this->streamPointer = 0;
    }
    return TRUE;
  }

  /**
   * Support for ftell().
   *
   * @return int
   *   The current offset in bytes from the beginning of file.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-tell.php
   */
// @codingStandardsIgnoreStart
  public function stream_tell() {
// @codingStandardsIgnoreEnd
    return $this->streamPointer;
  }

  /**
   * Support for fstat().
   *
   * @return array
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
// @codingStandardsIgnoreStart
  public function stream_stat() {
// @codingStandardsIgnoreEnd
    return [
      'size' => strlen($this->sessionContent),
    ];
  }

  /**
   * Support for fclose().
   *
   * @return bool
   *   TRUE if stream was successfully closed.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-close.php
   */
// @codingStandardsIgnoreStart
  public function stream_close() {
// @codingStandardsIgnoreEnd
    $this->streamPointer = 0;
    // Unassign the reference.
    unset($this->sessionContent);
    return TRUE;
  }

  /**
   * Support for unlink().
   *
   * @param string $uri
   *   A string containing the uri to the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    $path = $this->getLocalPath($uri);
    $this->sessionHelper->clearPath($path);
    return TRUE;
  }

  /**
   * Support for rename().
   *
   * @param string $from_uri
   *   The uri to the file to rename.
   * @param string $to_uri
   *   The new uri for file.
   *
   * @return bool
   *   TRUE if file was successfully renamed.
   *
   * @see http://php.net/manual/en/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    // We get the old key contents, write it
    // to a new key, erase the old key.
    $from_path = $this->getLocalPath($from_uri);
    $to_path = $this->getLocalPath($to_uri);
    if (!$this->sessionHelper->checkPath($from_path)) {
      return FALSE;
    }
    $from_key = $this->sessionHelper->getPath($from_path);
    $path_info = $this->sessionHelper->getParentPath($to_path);
    $parent_path = $path_info['dirname'];

    // We will only allow writing to a non-existent file
    // in an existing directory.
    if ($this->sessionHelper->checkPath($parent_path) && !$this->sessionHelper->checkPath($to_path)) {
      $this->sessionHelper->setPath($to_path, $from_key);
      $this->sessionHelper->clearPath($from_path);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets the name of the directory from a given path.
   *
   * @param string $uri
   *   A URI.
   *
   * @return string
   *   A string containing the directory name.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    list($scheme,) = explode('://', $uri, 2);
    $target = $this->getLocalPath($uri);
    if (strpos($target, '/')) {
      $dirname = preg_replace('@/[^/]*$@', '', $target);
    }
    else {
      $dirname = '';
    }
    return $scheme . '://' . $dirname;
  }

  /**
   * Support for mkdir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to create.
   * @param int $mode
   *   Permission flags - see mkdir().
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   TRUE if directory was successfully created.
   *
   * @see http://php.net/manual/en/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    // If this already exists, then we can't mkdir.
    if (is_dir($uri) || is_file($uri)) {
      return FALSE;
    }
    $path = $this->getLocalPath($uri);
    $new_dir = ['isadir.txt' => TRUE];
    $this->sessionHelper->setPath($path, $new_dir);
    return TRUE;
  }

  /**
   * Support for rmdir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to delete.
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   TRUE if directory was successfully removed.
   *
   * @see http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    $path = $this->getLocalPath($uri);
    if (!$this->sessionHelper->checkPath($path) or !is_array($this->sessionHelper->getPath($path))) {
      return FALSE;
    }
    $this->sessionHelper->clearPath($path);
    return TRUE;
  }

  /**
   * Support for stat().
   *
   * This important function goes back to the Unix way of doing things.
   * In this example almost the entire stat array is irrelevant, but the
   * mode is very important. It tells PHP whether we have a file or a
   * directory and what the permissions are. All that is packed up in a
   * bitmask. This is not normal PHP fodder.
   *
   * @param string $uri
   *   A string containing the URI to get information about.
   * @param int $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *
   * @return array|bool
   *   An array with file status, or FALSE in case of an error - see fstat()
   *   for a description of this array.
   *
   * @see http://php.net/manual/en/streamwrapper.url-stat.php
   */
// @codingStandardsIgnoreStart
  public function url_stat($uri, $flags) {
// @codingStandardsIgnoreEnd
    $path = $this->getLocalPath($uri);
    if (!$this->sessionHelper->checkPath($path)) {
      return FALSE;
      // No file.
    }
    // Default to fail.
    $return = FALSE;
    $mode = 0;

    $key = $this->sessionHelper->getPath($path);

    // We will call an array a directory and the root is always an array.
    if (is_array($key)) {
      // S_IFDIR means it's a directory.
      $mode = 0040000;
    }
    elseif ($key !== FALSE) {
      // S_IFREG, means it's a file.
      $mode = 0100000;
    }

    if ($mode) {
      $size = 0;
      if ($mode == 0100000) {
        $size = strlen($key);
      }

      // There are no protections on this, so all writable.
      $mode |= 0777;
      $return = [
        'dev' => 0,
        'ino' => 0,
        'mode' => $mode,
        'nlink' => 0,
        'uid' => 0,
        'gid' => 0,
        'rdev' => 0,
        'size' => $size,
        'atime' => 0,
        'mtime' => 0,
        'ctime' => 0,
        'blksize' => 0,
        'blocks' => 0,
      ];
    }
    return $return;
  }

  /**
   * Support for opendir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to open.
   * @param int $options
   *   Whether or not to enforce safe_mode (0x04).
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
// @codingStandardsIgnoreStart
  public function dir_opendir($uri, $options) {
// @codingStandardsIgnoreEnd
    $path = $this->getLocalPath($uri);
    if (!$this->sessionHelper->checkPath($path)) {
      return FALSE;
    }
    $var = $this->sessionHelper->getPath($path);
    if (!is_array($var)) {
      return FALSE;
    }

    // We grab the list of key names, flip it so that .isadir.txt can easily
    // be removed, then flip it back so we can easily walk it as a list.
    $this->directoryKeys = array_flip(array_keys($var));
    unset($this->directoryKeys['.isadir.txt']);
    $this->directoryKeys = array_keys($this->directoryKeys);
    $this->directoryPointer = 0;
    return TRUE;
  }

  /**
   * Support for readdir().
   *
   * @return string|bool
   *   The next filename, or FALSE if there are no more files in the directory.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-readdir.php
   */
// @codingStandardsIgnoreStart
  public function dir_readdir() {
// @codingStandardsIgnoreEnd
    if ($this->directoryPointer < count($this->directoryKeys)) {
      $next = $this->directoryKeys[$this->directoryPointer];
      $this->directoryPointer++;
      return $next;
    }
    return FALSE;
  }

  /**
   * Support for rewinddir().
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-rewinddir.php
   */
// @codingStandardsIgnoreStart
  public function dir_rewinddir() {
// @codingStandardsIgnoreEnd
    $this->directoryPointer = 0;
    return TRUE;
  }

  /**
   * Support for closedir().
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-closedir.php
   */
// @codingStandardsIgnoreStart
  public function dir_closedir() {
// @codingStandardsIgnoreEnd
    $this->directoryPointer = 0;
    unset($this->directoryKeys);
    return TRUE;
  }

}
