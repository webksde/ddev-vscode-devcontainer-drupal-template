<?php

namespace Drupal\Tests\stream_wrapper_example\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\stream_wrapper_example\Traits\MockSessionTrait;

/**
 * Test of the Session Stream Wrapper Class.
 *
 * This test covers the PHP-level (i.e., not Drupal-specific) functions of the
 * SessionStreamWrapper class. It's not directly loaded here because it loads in
 * background automatically as soon as the stream_wrapper_example module loads.
 *
 * The tests invoke the stream wrapper's functionality indirectly by calling
 * PHP's file functions.
 *
 * @ingroup stream_wrapper_example
 * @group stream_wrapper_example
 * @group examples
 */
class StreamWrapperTest extends KernelTestBase {

  use MockSessionTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['stream_wrapper_example', 'file', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // We use a mock session here so that our session-based stream wrapper is
    // able to operate. Kernel tests don't normally have a logged-in user, so
    // we mock one.
    $this->container->set('request_stack', $this->createSessionMock());
  }

  /**
   * Test if the session scheme was actually registered.
   */
  public function testSchemeRegistered() {
    $have_session_scheme = $this->container
      ->get('stream_wrapper_manager')
      ->isValidScheme('session');
    $this->assertTrue($have_session_scheme, "System knows about our stream wrapper");
  }

  /**
   * Test functions on a URI.
   */
  public function testReadWrite() {
    $this->resetStore();

    $uri = 'session://drupal.txt';

    $this->assertFileNotExists($uri, "File $uri should not exist yet.");
    $handle = fopen($uri, 'wb');
    $this->assertNotEmpty($handle, "Handle for $uri should be non-empty.");
    $buffer = "Ain't seen nothin' yet!\n";

    // Original session class gets an error here,
    // "...stream_write wrote 10 bytes more data than requested".
    // Does not matter for our demo, so repress error reporting here.".
    $old = error_reporting(E_ERROR);
    $bytes_written = @fwrite($handle, $buffer);
    error_reporting($old);
    $this->assertNotFalse($bytes_written, "Write to $uri succeeded.");

    $result = fclose($handle);
    $this->assertNotFalse($result, "Closed $uri.");
    $this->assertFileExists($uri, "File $uri should now exist.");
    $this->assertDirectoryNotExists($uri, "$uri is not a directory.");
    $this->assertTrue(is_file($uri), "$uri is a file.");

    $contents = file_get_contents($uri);
    // The example implementation calls HTML::escape() on output. We reverse it
    // well enough for our sample data (this code is not I18n safe).
    $contents = Html::decodeEntities($contents);
    $this->assertEquals($buffer, $contents, "Data for $uri should make the round trip.");
  }

  /**
   * Directory creation.
   */
  public function testDirectories() {
    $this->resetStore();
    $dir_uri = 'session://directory1/directory2';
    $sample_file = 'file.txt';
    $content = "Wrote this as a file?\n";

    $dir = dirname($dir_uri);

    $this->assertFileNotExists($dir, "The outer dir $dir should not exist yet.");
    // We don't care about mode, since we don't support it.
    $worked = mkdir($dir);
    $this->assertDirectoryExists($dir, "Directory $dir was created.");
    $first_file_content = 'This one is in the first directory.';
    $uri = $dir . "/" . $sample_file;
    $bytes = file_put_contents($uri, $first_file_content);
    $this->assertNotFalse($bytes, "Wrote to $uri.\n");
    $this->assertFileExists($uri, "File $uri actually exists.");
    $got_back = file_get_contents($uri);
    $got_back = Html::decodeEntities($got_back);
    $this->assertSame($first_file_content, $got_back, 'Data in subdir made round trip.');

    // Now try down down nested.
    $result = mkdir($dir_uri);
    $this->assertTrue($result, 'Nested dir got created.');
    $file_in_sub = $dir_uri . "/" . $sample_file;
    $bytes = file_put_contents($file_in_sub, $content);
    $this->assertNotFalse($bytes, 'File in nested dirs got written to.');
    $got_back = file_get_contents($file_in_sub);
    $got_back = Html::decodeEntities($got_back);
    $this->assertSame($content, $got_back, 'Data in subdir made round trip.');
    $worked = unlink($file_in_sub);
    $this->assertTrue($worked, 'Deleted file in subdir.');
    $this->assertFileNotExists($file_in_sub, 'File in subdir should not exist.');
  }

  /**
   * Get the contents of the complete array stored in the session.
   */
  protected function getCurrentStore() {
    $handle = $this->getSessionHelper();
    return $handle->getPath('');
  }

  /**
   * Clear the session storage area.
   */
  protected function resetStore() {
    $handle = $this->getSessionHelper();
    $handle->cleanUpStore();
  }

}
