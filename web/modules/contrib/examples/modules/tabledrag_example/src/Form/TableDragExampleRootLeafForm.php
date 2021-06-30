<?php

namespace Drupal\tabledrag_example\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Table drag example root leaf form.
 *
 * Tabledrag rows can be marked as roots or leaves. This limits the way the user
 * can interact with them in drag-and-drop operations. We'll mark some rows this
 * way and you can try dragging them around on the page to see how they are
 * limited.
 *
 * @ingroup tabledrag_example
 */
class TableDragExampleRootLeafForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $render;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('renderer')
    );
  }

  /**
   * Construct a form.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $render
   *   The renderer.
   */
  public function __construct(Connection $database, RendererInterface $render) {
    $this->database = $database;
    $this->render = $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tabledrag_example_rootlead_form';
  }

  /**
   * Build the parent-child example form.
   *
   * Tabledrag will take care of updating the parent_id relationship on each
   * row of our table when we drag items around, but we need to build out the
   * initial tree structure ourselves. This means ordering our items such
   * that children items come directly after their parent items, and all items
   * are sorted by weight relative to their siblings.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t("Tabledrag rows can be marked as roots or leaves. This limits the way the user can interact with them in drag-and-drop operations. We'll mark some rows this way and you can try dragging them around on the page to see how they are limited."),
    ];

    $form['info'] = [
      '#markup' => '<ul>
        <li>' . $this->t("Rows with the 'tabledrag-leaf' class cannot have child rows.") . '</li>
        <li>' . $this->t("Rows with the 'tabledrag-root' class cannot be nested under a parent row.") . '</li></ul>',
    ];

    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Weight'),
        $this->t('Parent'),
      ],
      '#empty' => $this->t('Sorry, There are no items!'),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'row-pid',
          'source' => 'row-id',
          'hidden' => TRUE, /* hides the WEIGHT & PARENT tree columns below */
          'limit' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ],
      ],
    ];

    // Build the table rows and columns.
    //
    // The first nested level in the render array forms the table row, on which
    // you likely want to set #attributes and #weight.
    // Each child element on the second level represents a table column cell in
    // the respective table row, which are render elements on their own. For
    // single output elements, use the table cell itself for the render element.
    // If a cell should contain multiple elements, simply use nested sub-keys to
    // build the render element structure for the renderer service as you would
    // everywhere else.
    $results = self::getData();
    foreach ($results as $row) {
      // TableDrag: Mark the table row as draggable.
      $form['table-row'][$row->id]['#attributes']['class'][] = 'draggable';

      // We can add the 'tabledrag-root' class to a row in order to indicate
      // that the row may not be nested under a parent row.  In our sample data
      // for this example, the description for the item with id '11' flags it as
      // a 'root' item which should not be nested.
      if ($row->id == '11') {
        $form['table-row'][$row->id]['#attributes']['class'][] = 'tabledrag-root';
      }

      // We can add the 'tabledrag-leaf' class to a row in order to indicate
      // that the row may not contain child rows.  In our sample data for this
      // example, the description for the item with id '12' flags it as a 'leaf'
      // item which can not contain child items.
      if ($row->id == '12') {
        $form['table-row'][$row->id]['#attributes']['class'][] = 'tabledrag-leaf';
      }

      // TableDrag: Sort the table row according to its existing/configured
      // weight.
      $form['table-row'][$row->id]['#weight'] = $row->weight;

      // Indent item on load.
      if (isset($row->depth) && $row->depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $row->depth,
        ];
      }
      // Some table columns containing raw markup.
      $form['table-row'][$row->id]['name'] = [
        '#markup' => $row->name,
        '#prefix' => !empty($indentation) ? $this->render->render($indentation) : '',
      ];

      $form['table-row'][$row->id]['description'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $row->description,
      ];

      // This is hidden from #tabledrag array (above).
      // TableDrag: Weight column element.
      $form['table-row'][$row->id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for ID @id', ['@id' => $row->id]),
        '#title_display' => 'invisible',
        '#default_value' => $row->weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => [
          'class' => ['row-weight'],
        ],
      ];
      $form['table-row'][$row->id]['parent']['id'] = [
        '#parents' => ['table-row', $row->id, 'id'],
        '#type' => 'hidden',
        '#value' => $row->id,
        '#attributes' => [
          'class' => ['row-id'],
        ],
      ];
      $form['table-row'][$row->id]['parent']['pid'] = [
        '#parents' => ['table-row', $row->id, 'pid'],
        '#type' => 'number',
        '#size' => 3,
        '#min' => 0,
        '#title' => $this->t('Parent ID'),
        '#default_value' => $row->pid,
        '#attributes' => [
          'class' => ['row-pid'],
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save All Changes'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to TableDrag Overview'),
      ],
      '#submit' => ['::cancel'],
    ];

    return $form;
  }

  /**
   * Form submission handler for the 'Return to' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('tabledrag_example.description');
  }

  /**
   * Submit handler for the form.
   *
   * Updates the 'weight' column for each element in our table, taking into
   * account that item's new order after the drag and drop actions have been
   * performed.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Because the form elements were keyed with the item ids from the database,
    // we can simply iterate through the submitted values.
    $submissions = $form_state->getValue('table-row');
    foreach ($submissions as $id => $item) {
      $this->database->update('tabledrag_example')
        ->fields([
          'weight' => $item['weight'],
          'pid' => $item['pid'],
          'description' => $item['description'],
        ])
        ->condition('id', $id, '=')
        ->execute();
    }
  }

  /**
   * Retrieves the tree structure from database, sorts by parent/child/weight.
   *
   * The sorting should result in children items immediately following their
   * parent items, with items at the same level of the hierarchy sorted by
   * weight.
   *
   * The approach used here may be considered too database-intensive.
   * Optimization of the approach is left as an exercise for the reader. :)
   *
   * @return array
   *   An associative array storing our ordered tree structure.
   */
  public function getData() {
    // Get all 'root node' items (items with no parents), sorted by weight.
    $root_items = $this->database->select('tabledrag_example', 't')
      ->fields('t')
      ->condition('pid', '0', '=')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();

    // Initialize a variable to store our ordered tree structure.
    $tree = [];

    // Depth will be incremented in our getTree()
    // function for the first parent item, so we start it at -1.
    $depth = -1;

    // Loop through the root item, and add their trees to the array.
    foreach ($root_items as $root_item) {
      $this->getTree($root_item, $tree, $depth);
    }

    return $tree;
  }

  /**
   * Recursively adds $item to $item_tree, ordered by parent/child/weight.
   *
   * @param mixed $item
   *   The item.
   * @param array $tree
   *   The item tree.
   * @param int $depth
   *   The depth of the item.
   */
  public function getTree($item, array &$tree = [], &$depth = 0) {
    // Increase our $depth value by one.
    $depth++;

    // Set the current tree 'depth' for this item, used to calculate
    // indentation.
    $item->depth = $depth;

    // Add the item to the tree.
    $tree[$item->id] = $item;

    // Retrieve each of the children belonging to this nested demo.
    $children = $this->database->select('tabledrag_example', 't')
      ->fields('t')
      ->condition('pid', $item->id, '=')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();

    foreach ($children as $child) {
      // Make sure this child does not already exist in the tree, to
      // avoid loops.
      if (!in_array($child->id, array_keys($tree))) {
        // Add this child's tree to the $itemtree array.
        $this->getTree($child, $tree, $depth);
      }
    }

    // Finished processing this tree branch.  Decrease our $depth value by one
    // to represent moving to the next branch.
    $depth--;
  }

}
