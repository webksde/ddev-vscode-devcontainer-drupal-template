<?php

namespace Drupal\Tests\examples\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Validate requirements for config YAML.
 *
 * YAML in modules' config/ directory should not have a uuid: key. We'll use
 * this test to check whether that's the case.
 *
 * @group examples
 */
class YamlValidationTest extends TestCase {

  /**
   * Find all the config YAML files and provide them to the test.
   *
   * @return array[]
   *   An array of arrays of strings, suitable as a data provider. Strings are
   *   paths to YAML files in config directories.
   */
  public function provideYamls() {
    $yaml_paths = [];

    $examples_project_path = realpath(__DIR__ . '/../../..');

    $paths = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($examples_project_path, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS));
    foreach ($paths as $path) {
      $pathname = $path->getPathname();
      if (strpos($pathname, '.yml') !== FALSE) {
        if (strpos($pathname, '/config/') !== FALSE) {
          $yaml_paths[] = [$pathname];
        }
      }
    }
    return $yaml_paths;
  }

  /**
   * @dataProvider provideYamls
   */
  public function testNoUuidsInConfig($yaml_path) {
    $yaml = Yaml::parse(file_get_contents($yaml_path));
    $this->assertArrayNotHasKey('uuid', $yaml, "YAML in this file contains a uuid key: $yaml_path");
  }

}
