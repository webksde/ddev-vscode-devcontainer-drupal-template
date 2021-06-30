<?php

namespace Drupal\Tests\batch_example\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Batch Example module.
 *
 * @group batch_example
 *
 * @ingroup batch_example
 */
class BatchExampleWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'batch_example'];

  /**
   * Login user and test both batch examples.
   */
  public function testBatchExampleBasic() {
    // Login the admin user.
    $web_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($web_user);

    // Launch Batch 1.
    $this->drupalPostForm('examples/batch_example', ['batch' => 'batch_1'], 'Go');
    // Check that 1000 operations were performed.
    $this->assertText('1000 results processed');

    // Launch Batch 2.
    $this->drupalPostForm('examples/batch_example', ['batch' => 'batch_2'], 'Go');
    // Check that 600 operations were performed.
    $this->assertText('600 results processed');
  }

}
