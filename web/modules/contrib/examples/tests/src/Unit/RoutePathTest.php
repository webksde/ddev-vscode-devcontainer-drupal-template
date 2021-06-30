<?php

namespace Drupal\Tests\examples\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Validate paths for routes.
 *
 * Paths in routes should start with a /.
 *
 * @group examples
 */
class RoutePathTest extends TestCase {

  /**
   * Find all the routing YAML files and provide them to the test.
   *
   * @return array[]
   *   An array of arrays of strings, suitable as a data provider. Strings are
   *   paths to routing YAML files.
   */
  public function provideYamls() {
    $yaml_paths = [];

    $examples_project_path = realpath(__DIR__ . '/../../..');

    $paths = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($examples_project_path, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS));
    foreach ($paths as $path) {
      $pathname = $path->getPathname();
      if (strpos($pathname, 'routing.yml') !== FALSE) {
        $yaml_paths[] = [$pathname];
      }
    }
    return $yaml_paths;
  }

  /**
   * @dataProvider provideYamls
   */
  public function testPathsStartWithSlash($yaml_path) {
    $routes = Yaml::parse(file_get_contents($yaml_path));

    foreach ($routes as $name => $route) {
      if (isset($route['path'])) {
        $this->assertEquals('/', $route['path'][0], "Route $name does not start with a slash '/'.");
      }
    }
  }

}
