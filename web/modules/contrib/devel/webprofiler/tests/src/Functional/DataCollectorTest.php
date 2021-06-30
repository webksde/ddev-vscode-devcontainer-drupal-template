<?php

namespace Drupal\Tests\webprofiler\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\webprofiler\DataCollector\BlocksDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the DataCollector functions of webprofiler.
 *
 * @group webprofiler
 */
class DataCollectorTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webprofiler', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The data collector for blocks.
   *
   * @var \Drupal\webprofiler\DataCollector\BlocksDataCollector
   */
  private $blocksDataCollector;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->blocksDataCollector = new BlocksDataCollector($entity_type_manager);
  }

  /**
   * Tests the Blocks data collector.
   */
  public function testBlocksDataCollector() {
    // This test was added to ensure we do not have any regression faults such
    // as happened with the blocksDataCollector.
    // @see https://www.drupal.org/project/devel/issues/3106747
    $this->drupalPlaceBlock('page_title_block', ['id' => 'page-title']);
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'local-tasks']);

    $this->drupalLogin($this->rootUser);

    // What is the right way to collect the block data? The blocks are being
    // placed OK above but the following does not work (obviously).
    // $data has a 'blocks' key and that array has 'loaded' and 'rendered' keys
    // but the values are empty.
    $request = Request::create('', 'GET');
    $response = Response::create();
    $this->blocksDataCollector->collect($request, $response);
    $data = $this->blocksDataCollector->getData();
    $this->assertCount(1, $data);
  }

}
