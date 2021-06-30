<?php

namespace Drupal\batch_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with examples on how to use cache.
 */
class BatchExampleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('This example offers two different batches. The first does 1000 identical operations, each completed in on run; the second does 20 operations, but each takes more than one run to operate if there are more than 5 nodes.'),
    ];
    $form['batch'] = [
      '#type' => 'select',
      '#title' => 'Choose batch',
      '#options' => [
        'batch_1' => $this->t('batch 1 - 1000 operations'),
        'batch_2' => $this->t('batch 2 - 20 operations.'),
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Go',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gather our form value.
    $value = $form_state->getValues()['batch'];
    // Set the batch, using convenience methods.
    $batch = [];
    switch ($value) {
      case 'batch_1':
        $batch = $this->generateBatch1();
        break;

      case 'batch_2':
        $batch = $this->generateBatch2();
        break;
    }
    batch_set($batch);
  }

  /**
   * Generate Batch 1.
   *
   * Batch 1 will process one item at a time.
   *
   * This creates an operations array defining what batch 1 should do, including
   * what it should do when it's finished. In this case, each operation is the
   * same and by chance even has the same $nid to operate on, but we could have
   * a mix of different types of operations in the operations array.
   */
  public function generateBatch1() {
    $num_operations = 1000;
    $this->messenger()->addMessage($this->t('Creating an array of @num operations', ['@num' => $num_operations]));

    $operations = [];
    // Set up an operations array with 1000 elements, each doing function
    // batch_example_op_1.
    // Each operation in the operations array means at least one new HTTP
    // request, running Drupal from scratch to accomplish the operation. If the
    // operation returns with $context['finished'] != TRUE, then it will be
    // called again.
    // In this example, $context['finished'] is always TRUE.
    for ($i = 0; $i < $num_operations; $i++) {
      // Each operation is an array consisting of
      // - The function to call.
      // - An array of arguments to that function.
      $operations[] = [
        'batch_example_op_1',
        [
          $i + 1,
          $this->t('(Operation @operation)', ['@operation' => $i]),
        ],
      ];
    }
    $batch = [
      'title' => $this->t('Creating an array of @num operations', ['@num' => $num_operations]),
      'operations' => $operations,
      'finished' => 'batch_example_finished',
    ];
    return $batch;
  }

  /**
   * Generate Batch 2.
   *
   * Batch 2 will process five items at a time.
   *
   * This creates an operations array defining what batch 2 should do, including
   * what it should do when it's finished. In this case, each operation is the
   * same and by chance even has the same $nid to operate on, but we could have
   * a mix of different types of operations in the operations array.
   */
  public function generateBatch2() {
    $num_operations = 20;

    $operations = [];
    // 20 operations, each one loads all nodes.
    for ($i = 0; $i < $num_operations; $i++) {
      $operations[] = [
        'batch_example_op_2',
        [$this->t('(Operation @operation)', ['@operation' => $i])],
      ];
    }
    $batch = [
      'operations' => $operations,
      'finished' => 'batch_example_finished',
      // @current, @remaining, @total, @percentage, @estimate and @elapsed.
      // These placeholders are replaced with actual values in _batch_process(),
      // using strtr() instead of t(). The values are determined based on the
      // number of operations in the 'operations' array (above), NOT by the
      // number of nodes that will be processed. In this example, there are 20
      // operations, so @total will always be 20, even though there are multiple
      // nodes per operation.
      // Defaults to t('Completed @current of @total.').
      'title' => $this->t('Processing batch 2'),
      'init_message' => $this->t('Batch 2 is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Batch 2 has encountered an error.'),
    ];
    return $batch;
  }

}
