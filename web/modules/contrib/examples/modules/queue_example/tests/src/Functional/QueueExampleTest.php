<?php

namespace Drupal\Tests\queue_example\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that our queue_example functions properly.
 *
 * @ingroup queue_example
 * @group queue_example
 * @group examples
 */
class QueueExampleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['queue_example'];

  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Exercise the queue manipulation form.
   */
  public function testQueueExampleBasic() {
    $this->drupalGet('examples/queue_example');
    // Load the queue with 5 items.
    for ($i = 1; $i <= 5; $i++) {
      $edit = ['queue_name' => 'queue_example_first_queue', 'string_to_add' => 'boogie' . $i];
      $this->drupalPostForm(NULL, $edit, 'Insert into queue');
      $this->assertText((string) new FormattableMarkup('There are now @number items in the queue', ['@number' => $i]));
    }
    // Claim each of the 5 items with a claim time of 0 seconds.
    for ($i = 1; $i <= 5; $i++) {
      $edit = ['queue_name' => 'queue_example_first_queue', 'claim_time' => 0];
      $this->drupalPostForm(NULL, $edit, 'Claim the next item from the queue');
      $this->assertPattern((string) new FormattableMarkup('%Claimed item id=.*string=@string for 0 seconds.%', ['@string' => 'boogie' . $i]));
    }
    $edit = ['queue_name' => 'queue_example_first_queue', 'claim_time' => 0];
    $this->drupalPostForm(NULL, $edit, 'Claim the next item from the queue');
    $this->assertText('There were no items in the queue available to claim');

    // Sleep a second so we can make sure that the timeouts actually time out.
    // Local systems work fine with this but apparently the PIFR server is so
    // fast that it needs a sleep before the cron run.
    sleep(1);

    // Run cron to release expired items.
    $this->drupalPostForm(NULL, [], 'Run cron manually to expire claims');

    // Claim and delete each of the 5 items which should now be available.
    for ($i = 1; $i <= 5; $i++) {
      $edit = ['queue_name' => 'queue_example_first_queue', 'claim_time' => 0];
      $this->drupalPostForm(NULL, $edit, 'Claim the next item and delete it');
      $this->assertPattern((string) new FormattableMarkup('%Claimed and deleted item id=.*string=@string for 0 seconds.%', ['@string' => 'boogie' . $i]));
    }
    // Verify that nothing is left to claim.
    $edit = ['queue_name' => 'queue_example_first_queue', 'claim_time' => 0];
    $this->drupalPostForm(NULL, $edit, 'Claim the next item from the queue');
    $this->assertText('There were no items in the queue available to claim');
  }

}
