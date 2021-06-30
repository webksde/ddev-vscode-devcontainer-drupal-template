<?php

namespace Drupal\Tests\webprofiler\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class DecoratorTest.
 *
 * @group webprofiler
 */
class DecoratorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'views'];

  /**
   * Tests the Entity Type Manager service decoration.
   *
   * @param string $service
   *   The service name.
   * @param string $original
   *   The original class.
   * @param string $decorated
   *   The decorated class.
   *
   * @dataProvider decorators
   */
  public function testEntityTypeDecorator($service, $original, $decorated) {
    $entityTypeManagerOriginal = $this->container->get($service);

    $this->assertInstanceOf($original, $entityTypeManagerOriginal);

    $this->container->get('module_installer')->install(['webprofiler']);

    $entityTypeManagerDecorated = $this->container->get($service);

    $this->assertInstanceOf($decorated, $entityTypeManagerDecorated);
  }

  /**
   * DataProvider for testEntityTypeDecorator.
   *
   * @return array
   *   The array of values to run tests on.
   */
  public function decorators() {
    return [
      ['entity_type.manager', 'Drupal\Core\Entity\EntityTypeManager', 'Drupal\webprofiler\Entity\EntityManagerWrapper'],
      ['cache_factory', 'Drupal\Core\Cache\MemoryBackendFactory', 'Drupal\webprofiler\Cache\CacheFactoryWrapper'],
      ['asset.css.collection_renderer', 'Drupal\Core\Asset\CssCollectionRenderer', 'Drupal\webprofiler\Asset\CssCollectionRendererWrapper'],
      ['asset.js.collection_renderer', 'Drupal\Core\Asset\JsCollectionRenderer', 'Drupal\webprofiler\Asset\JsCollectionRendererWrapper'],
      ['state', 'Drupal\Core\State\State', 'Drupal\webprofiler\State\StateWrapper'],
      ['views.executable', 'Drupal\views\ViewExecutableFactory', 'Drupal\webprofiler\Views\ViewExecutableFactoryWrapper'],
      ['form_builder', 'Drupal\Core\Form\FormBuilder', 'Drupal\webprofiler\Form\FormBuilderWrapper'],
      ['access_manager', 'Drupal\Core\Access\AccessManager', 'Drupal\webprofiler\Access\AccessManagerWrapper'],
      ['theme.negotiator', 'Drupal\Core\Theme\ThemeNegotiator', 'Drupal\webprofiler\Theme\ThemeNegotiatorWrapper'],
      ['config.factory', 'Drupal\Core\Config\ConfigFactory', 'Drupal\webprofiler\Config\ConfigFactoryWrapper'],
      ['string_translation', 'Drupal\Core\StringTranslation\TranslationManager', 'Drupal\webprofiler\StringTranslation\TranslationManagerWrapper'],
    ];
  }

}
