<?php

namespace Drupal\Tests\tour_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\tour\Functional\TourTestBasic;

/**
 * Regression tests for the tour_example module.
 *
 * We use TourTestBasic to get some built-in tour tip testing assertions.
 *
 * @ingroup tour_example
 *
 * @group tour_example
 * @group examples
 */
class TourExampleTest extends TourTestBasic {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tour_example'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Main test.
   *
   * Make sure the Tour Example link is on the front page. Make sure all the
   * tour tips exist on the page. Make sure all the corresponding target
   * elements exist for tour tips that have targets.
   */
  public function testTourExample() {
    $assert = $this->assertSession();

    // Create a user with the permissions we need in order to display the
    // toolbar and run a tour from it.
    $this->drupalLogin($this->createUser([
      'access content',
      'access toolbar',
      'access tour',
    ]));

    // Test for a link to the tour_example in the Tools menu.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert->statusCodeEquals(200);
    $assert->linkByHrefExists('examples/tour-example');

    // Verify anonymous user can successfully access the tour_examples page.
    $this->drupalGet(Url::fromRoute('tour_example.description'));
    $assert->statusCodeEquals(200);

    // Get all the tour elements. These are the IDs of each tour tip. See them
    // in config/install/tour.tour.tour-example.yml.
    $tip_ids = [
      'introduction',
      'first-item',
      'second-item',
      'third-item',
      'fourth-item',
    ];

    // Ensure that we have the same number of tour tips that we expect.
    $this->assertCount(count($tip_ids), $this->xpath("//ol[@id = \"tour\"]//li"));

    // Ensure each item exists.
    foreach ($tip_ids as $tip_id) {
      $this->assertNotEmpty(
        $this->xpath("//ol[@id = \"tour\"]//li[contains(@class, \"tip-$tip_id\")]"),
        "Tip id: $tip_id"
      );
    }

    // Verify that existing tour tips have corresponding target page elements.
    $this->assertTourTips();
  }

}
