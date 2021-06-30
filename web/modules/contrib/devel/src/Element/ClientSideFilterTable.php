<?php

namespace Drupal\devel\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for filterable table data.
 *
 * Usage example:
 *
 * @code
 * $build['item'] = [
 *   '#type' => 'devel_table_filter',
 *   '#filter_label' => $this->t('Search'),
 *   '#filter_placeholder' => $this->t('Enter element name.'),
 *   '#filter_description' => $this->t('Enter a part of name to filter by.'),
 *   '#header' => $headers,
 *   '#rows' => $rows,
 *   '#empty' => $this->t('No element found.'),
 * ];
 * @endcode
 *
 * @RenderElement("devel_table_filter")
 */
class ClientSideFilterTable extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Search'),
      '#filter_description' => $this->t('Search'),
      '#header' => [],
      '#rows' => [],
      '#empty' => '',
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#attributes' => [],
      '#pre_render' => [
        [$class, 'preRenderTable'],
      ],
    ];
  }

  /**
   * Pre-render callback: Assemble render array for the filterable table.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element with prepared render array ready for rendering.
   */
  public static function preRenderTable(array $element) {
    $build['#attached']['library'][] = 'devel/devel-table-filter';
    $identifier = Html::getUniqueId('js-devel-table-filter');

    $build['filters'] = [
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $build['filters']['name'] = [
      '#type' => 'search',
      '#size' => 30,
      '#title' => $element['#filter_label'],
      '#placeholder' => $element['#filter_placeholder'],
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => ".$identifier",
        'autocomplete' => 'off',
        'title' => $element['#filter_description'],
      ],
    ];

    foreach ($element['#rows'] as &$row) {
      foreach ($row as &$cell) {
        if (isset($cell['data']) && !empty($cell['filter'])) {
          $cell['class'][] = 'table-filter-text-source';
        }
      }
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $element['#header'],
      '#rows' => $element['#rows'],
      '#empty' => $element['#empty'],
      '#sticky' => $element['#sticky'],
      '#responsive' => $element['#responsive'],
      '#attributes' => $element['#attributes'],
    ];

    $build['table']['#attributes']['class'][] = $identifier;
    $build['table']['#attributes']['class'][] = 'devel-table-filter';

    return $build;
  }

}
