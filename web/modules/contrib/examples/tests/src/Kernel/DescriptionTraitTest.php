<?php

namespace Drupal\Tests\examples\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\examples_description_test\Controller\SampleExampleController;

/**
 * Test of the Description Trait.
 *
 * @group examples
 */
class DescriptionTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['examples', 'examples_description_test'];

  /**
   * Make sure that the trait finds the template file and renders it.
   */
  public function testTemplateFile() {
    $sample_controller = SampleExampleController::create($this->container);
    // We want to test ::getDescriptionTemplatePath(), which is a protected
    // method. Use a little of the Old Black Reflection Magic.
    $ref_get_path = new \ReflectionMethod($sample_controller, 'getDescriptionTemplatePath');
    $ref_get_path->setAccessible(TRUE);
    $this->assertFileExists($ref_get_path->invoke($sample_controller));
    // And get our render output.
    $render_array = $sample_controller->description();
    // We cast to string, since renderPlain() returns a markup object.
    $output = (string) $this->container->get('renderer')->renderPlain($render_array);
    // Did the template load?
    $this->assertStringContainsString('Template loaded!', $output);
    // Were the variables resolved correctly?
    $this->assertStringContainsString('Used in module: examples_description_test.', $output);
    $this->assertStringContainsString('Our slogan for today: We aim to please.', $output);
  }

}
