<?php

namespace Drupal\devel_generate_example\Plugin\DevelGenerate;

use Drupal\devel_generate\DevelGenerateBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a ExampleDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "devel_generate_example",
 *   label = "Example",
 *   description = "Generate a given number of examples.",
 *   url = "devel_generate_example",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE
 *   }
 * )
 */
class ExampleDevelGenerate extends DevelGenerateBase {

  /**
   *
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['num'] = [
      '#type' => 'textfield',
      '#title' => $this->t('How many examples would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#size' => 10,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all examples before generating new examples.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $num = $values['num'];
    $kill = $values['kill'];

    if ($kill) {
      $this->setMessage($this->t('Old examples have been deleted.'));
    }

    // Creating user in order to demonstrate
    // how to override default business login generation.
    $edit = [
      'uid'     => NULL,
      'name'    => 'example_devel_generate',
      'pass'    => '',
      'mail'    => 'example_devel_generate@example.com',
      'status'  => 1,
      'created' => \Drupal::time()->getRequestTime(),
      'roles' => '',
      // A flag to let hook_user_* know that this is a generated user.
      'devel_generate' => TRUE,
    ];

    $account = user_load_by_name('example_devel_generate');
    if (!$account) {
      $account = $this->getEntityTypeManager()->getStorage('user')->create($edit);
    }

    // Populate all fields with sample values.
    $this->populateFields($account);

    $account->save();

    $this->setMessage($this->t('@num_examples created.', [
      '@num_examples' => $this->formatPlural($num, '1 example', '@count examples'),
    ]));
  }

  /**
   *
   */
  public function validateDrushParams(array $args, array $options = []) {
    $values = [
      'num' => $options['num'],
      'kill' => $options['kill'],
    ];
    return $values;
  }

}
