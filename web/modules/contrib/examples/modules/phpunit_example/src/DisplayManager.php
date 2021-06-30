<?php

namespace Drupal\phpunit_example;

/**
 * An example class to demonstrate unit testing.
 *
 * Think of this class as a class that collects DisplayInfoInterface
 * objects, because that's what it is. It also might go on to one day
 * display lists of info about these info objects.
 *
 * But it never will, because it's just an example class.
 *
 * Part of the PHPUnit Example module.
 *
 * @ingroup phpunit_example
 */
class DisplayManager {

  /**
   * DisplayInfoInterface items.
   *
   * @var array
   */
  protected $items;

  /**
   * Add a displayable item.
   *
   * @param DisplayInfoInterface $item
   *   The item to add.
   */
  public function addDisplayableItem(DisplayInfoInterface $item) {
    $this->items[$item->getDisplayName()] = $item;
  }

  /**
   * A count of how many items exist.
   *
   * @return int
   *   The number of items that exist.
   */
  public function countDisplayableItems() {
    return count($this->items);
  }

  /**
   * All displayable items.
   *
   * @return array
   *   The displayable items.
   */
  public function displayableItems() {
    return $this->items;
  }

  /**
   * Find an item by its name.
   *
   * @param string $name
   *   The name to find.
   *
   * @return DisplayInfoInterface|null
   *   The found item, or NULL if none is found.
   */
  public function item($name) {
    if (isset($this->items[$name])) {
      return $this->items[$name];
    }
    return NULL;
  }

}
