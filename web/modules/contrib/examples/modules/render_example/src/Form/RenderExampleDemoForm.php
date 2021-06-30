<?php

namespace Drupal\render_example\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the form for toggling module features on and off.
 *
 * @ingroup render_example
 */
class RenderExampleDemoForm extends ConfigFormBase {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'render_example_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('render_example.settings');

    $form['description'] = [
      '#markup' => $this->t('This example shows what render arrays look like in the building of a page. It will not work unless the user running it has the "access devel information" privilege. It shows both the actual arrays used to build a page or block, and examples of altering the page late in its lifecycle.'),
    ];

    $form['show_arrays'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Show render arrays'),
      'render_example_show_block' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show block render arrays'),
        '#default_value' => $config->get('show_block'),
        // Only enable this option if the Devel module is enabled.
        '#access' => $this->moduleHandler->moduleExists('devel'),
      ],
      'render_example_show_page' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show page render arrays'),
        '#default_value' => $config->get('show_page'),
        // Only enable this option if the Devel module is enabled.
        '#access' => $this->moduleHandler->moduleExists('devel'),
      ],
      'render_example_devel' => [
        '#markup' => $this->t('Install the Devel module (https://www.drupal.org/project/devel) to enable additional demonstration features.'),
        // Only display this if the Devel module is not already installed.
        '#access' => !$this->moduleHandler->moduleExists('devel'),
      ],
    ];

    $form['page_fiddling'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Make changes on all pages via hook_preprocess_page()'),
      '#description' => $this->t('Theses changes are all made via the function render_example_preprocess_page()'),
      'render_example_move_breadcrumbs' => [
        '#title' => $this->t('Move the breadcrumbs to the top of the content area'),
        '#description' => $this->t('Uses hook_preprocess_page() to move the breadcrumbs into another region.'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('move_breadcrumbs'),
      ],
      'render_example_reverse_sidebar' => [
        '#title' => $this->t('Reverse ordering of sidebar_first elements (if it exists)'),
        '#description' => $this->t('Uses hook_preprocess_page() to reverse the ordering of items in sidebar_first'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('reverse_sidebar'),
      ],
      'render_example_wrap_blocks' => [
        '#title' => $this->t('Use #prefix and #suffix to wrap a div around every block'),
        '#description' => $this->t('Uses hook_block_view_alter() to wrap all blocks with a div using #prefix and #suffix'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('wrap_blocks'),
      ],
    ];

    $form['tabledrag'] = [
      '#type' => 'table',
      '#id' => 'draggable-table',
      '#caption' => $this->t('Our favorite colors.'),
      '#header' => [
        $this->t('Name'),
        $this->t('Favorite color'),
        $this->t('Weight'),
      ],
      // #tabledrag and be used on #table elements in the context of a form.
      // When enabled, the table will be rendered with a drag & drop interface
      // that can be used to re-order elements within the table. Any changes you
      // make to the order will be made available to your validation and submit
      // handlers via values in $form_state->getValues().
      //
      // The #tabledrag property contains an array of options passed to the
      // drupal_attach_tabledrag() function. These options are used to generate
      // the necessary JavaScript settings to configure the tableDrag behavior
      // for this table.
      //
      // For more in-depth documentation of the options below see
      // drupal_attach_tabledrag().
      '#tabledrag' => [
        [
          // The HTML ID of the table to make draggable. See #id above.
          'table_id' => 'draggable-table',
          // The action to be done on the form item. Either 'match' 'depth', or
          // 'order'.
          'action' => 'order',
          // String describing where the "action" option should be performed.
          // Either 'parent', 'sibling', 'group', or 'self'.
          'relationship' => 'sibling',
          // Class name applied on all related form elements for this action.
          // See below.
          'group' => 'table-order-weight',
        ],
      ],
      // Rather than defining the values to insert into the table using the
      // #rows property you can define each row as a sub element of the table
      // render array. And each column in the row as a sub element of the row
      // array.
      [
        // Apply the 'draggable' class to each row in the table that you want to
        // be made draggable.
        '#attributes' => ['class' => ['draggable']],
        // The first two columsn are render arrays that display a string of
        // text.
        'name' => ['#plain_text' => $this->t('Amber')],
        'color' => ['#plain_text' => $this->t('teal')],
        // The third column is a #weight form field.
        'weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          // Set the default value to whatever the current weight, or order, of
          // the element that this row represents is.
          '#default_value' => 1,
          // Set a class on each field that represents the value to manipulate
          // when the table is reordered. The name of this class should match
          // the value used for the 'group' argument in the #tabledrag property
          // above.
          '#attributes' => ['class' => ['table-order-weight']],
        ],
      ],
      // The rest of this array is additional rows so there is some data in the
      // table to drag around.
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Addi')],
        'color' => ['#plain_text' => $this->t('green')],
        'weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => 2,
          '#attributes' => ['class' => ['table-order-weight']],
        ],
      ],
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Blake')],
        'color' => ['#plain_text' => $this->t('#063')],
        'weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => 3,
          '#attributes' => ['class' => ['table-order-weight']],
        ],
      ],
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Enid')],
        'color' => ['#plain_text' => $this->t('indigo')],
        'weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => 4,
          '#attributes' => ['class' => ['table-order-weight']],
        ],
      ],
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Joe')],
        'color' => ['#plain_text' => $this->t('green')],
        'weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => 5,
          '#attributes' => ['class' => ['table-order-weight']],
        ],
      ],
    ];

    $form['tableselect'] = [
      '#type' => 'table',
      '#caption' => $this->t('Our favorite colors.'),
      '#header' => [
        $this->t('Name'),
        $this->t('Favorite color'),
      ],
      '#tableselect' => TRUE,
      // Rather than defining the values to insert into the table using the
      // #rows property you can define each row as a sub element of the table
      // render array. And each column in the row as a sub element of the row
      // array.
      [
        'name' => ['#plain_text' => $this->t('Amber')],
        'color' => ['#plain_text' => $this->t('teal')],
      ],
      // The rest of this array is additional rows so there is some data in the
      // table.
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Addi')],
        'color' => ['#plain_text' => $this->t('green')],
      ],
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Blake')],
        'color' => ['#plain_text' => $this->t('#063')],
      ],
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Enid')],
        'color' => ['#plain_text' => $this->t('indigo')],
      ],
      [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t('Joe')],
        'color' => ['#plain_text' => $this->t('green')],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['render_example.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('render_example.settings');
    $config->set('show_block', $values['render_example_show_block'])->save();
    $config->set('show_page', $values['render_example_show_page'])->save();
    $config->set('move_breadcrumbs', $values['render_example_move_breadcrumbs'])->save();
    $config->set('reverse_sidebar', $values['render_example_reverse_sidebar'])->save();
    $config->set('wrap_blocks', $values['render_example_wrap_blocks'])->save();

    parent::submitForm($form, $form_state);
  }

}
