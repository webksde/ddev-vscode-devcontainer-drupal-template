<?php

namespace Drupal\Tests\devel\Unit;

use Drupal\devel\Element\ClientSideFilterTable;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ClientSideFilterTable element.
 *
 * @coversDefaultClass \Drupal\devel\Element\ClientSideFilterTable
 * @group devel
 */
class DevelClientSideFilterTableTest extends UnitTestCase {

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $translation = $this->getStringTranslationStub();

    $expected_info = [
      '#filter_label' => $translation->translate('Search'),
      '#filter_placeholder' => $translation->translate('Search'),
      '#filter_description' => $translation->translate('Search'),
      '#header' => [],
      '#rows' => [],
      '#empty' => '',
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#attributes' => [],
      '#pre_render' => [
        [ClientSideFilterTable::class, 'preRenderTable'],
      ],
    ];

    $table = new ClientSideFilterTable([], 'test', 'test');
    $table->setStringTranslation($translation);
    $this->assertEquals($expected_info, $table->getInfo());
  }

  /**
   * @covers ::preRenderTable
   * @dataProvider providerPreRenderTable
   */
  public function testPreRenderTable($element, $expected) {
    $result = ClientSideFilterTable::preRenderTable($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for preRenderHtmlTag test.
   */
  public function providerPreRenderTable() {
    $data = [];

    $t = $this->getStringTranslationStub();

    $actual = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $t->translate('Label 1'),
      '#filter_placeholder' => $t->translate('Placeholder 1'),
      '#filter_description' => $t->translate('Description 1'),
      '#header' => [],
      '#rows' => [],
      '#empty' => $t->translate('Empty 1'),
      '#responsive' => TRUE,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-a-list'],
      ],
    ];

    $expected = [];
    $expected['#attached']['library'][] = 'devel/devel-table-filter';
    $expected['filters'] = [
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => ['class' => ['table-filter', 'js-show']],
      'name' => [
        '#type' => 'search',
        '#size' => 30,
        '#title' => $t->translate('Label 1'),
        '#placeholder' => $t->translate('Placeholder 1'),
        '#attributes' => [
          'class' => ['table-filter-text'],
          'data-table' => ".js-devel-table-filter",
          'autocomplete' => 'off',
          'title' => $t->translate('Description 1'),
        ],
      ],
    ];
    $expected['table'] = [
      '#type' => 'table',
      '#header' => [],
      '#rows' => [],
      '#empty' => $t->translate('Empty 1'),
      '#responsive' => TRUE,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => [
          'devel-a-list',
          'js-devel-table-filter',
          'devel-table-filter',
        ],
      ],
    ];

    $data[] = [$actual, $expected];

    $headers = ['Test1', 'Test2', 'Test3', 'Test4', 'Test5'];

    $actual = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $t->translate('Label 2'),
      '#filter_placeholder' => $t->translate('Placeholder 2'),
      '#filter_description' => $t->translate('Description 2'),
      '#header' => $headers,
      '#rows' => [
        [
          ['data' => 'test1', 'filter' => TRUE],
          ['data' => 'test2', 'filter' => TRUE, 'class' => ['test2']],
          ['data' => 'test3', 'class' => ['test3']],
          ['test4'],
          [
            'data' => 'test5',
            'filter' => TRUE,
            'class' => ['devel-event-name-header'],
            'colspan' => '3',
            'header' => TRUE,
          ],
        ],
      ],
      '#empty' => $t->translate('Empty 2'),
      '#responsive' => FALSE,
      '#sticky' => FALSE,
      '#attributes' => [
        'class' => ['devel-some-list'],
      ],
    ];

    $expected = [];
    $expected['#attached']['library'][] = 'devel/devel-table-filter';
    $expected['filters'] = [
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => ['class' => ['table-filter', 'js-show']],
      'name' => [
        '#type' => 'search',
        '#size' => 30,
        '#title' => $t->translate('Label 2'),
        '#placeholder' => $t->translate('Placeholder 2'),
        '#attributes' => [
          'class' => ['table-filter-text'],
          'data-table' => ".js-devel-table-filter--2",
          'autocomplete' => 'off',
          'title' => $t->translate('Description 2'),
        ],
      ],
    ];
    $expected['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => [
        [
          [
            'data' => 'test1',
            'filter' => TRUE,
            'class' => ['table-filter-text-source'],
          ],
          [
            'data' => 'test2',
            'filter' => TRUE,
            'class' => ['test2', 'table-filter-text-source'],
          ],
          ['data' => 'test3', 'class' => ['test3']],
          ['test4'],
          [
            'data' => 'test5',
            'filter' => TRUE,
            'class' => ['devel-event-name-header', 'table-filter-text-source'],
            'colspan' => '3',
            'header' => TRUE,
          ],
        ],
      ],
      '#empty' => $t->translate('Empty 2'),
      '#responsive' => FALSE,
      '#sticky' => FALSE,
      '#attributes' => [
        'class' => [
          'devel-some-list',
          'js-devel-table-filter--2',
          'devel-table-filter',
        ],
      ],
    ];

    $data[] = [$actual, $expected];

    return $data;
  }

}
