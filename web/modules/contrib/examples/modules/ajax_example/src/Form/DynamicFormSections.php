<?php

namespace Drupal\ajax_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Dynamically-enabled form with graceful no-JS degradation.
 *
 * Example of a form with portions dynamically enabled or disabled, but with
 * graceful degradation in the case of no JavaScript.
 *
 * The idea here is that certain parts of the form don't need to be displayed
 * unless a given option is selected, but then they should be displayed and
 * configured.
 *
 * The third $no_js_use argument is strictly for demonstrating operation
 * without JavaScript, without making the user/developer turn off JavaScript.
 */
class DynamicFormSections extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_example_dynamicsectiondegrades';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nojs = NULL) {
    // Add our CSS and tiny JS to hide things when they should be hidden.
    $form['#attached']['library'][] = 'ajax_example/ajax_example.library';

    // Explanatory text with helpful links.
    $form['info'] = [
      '#markup' =>
      $this->t('<p>Like other examples in this module, this form has a path that
        can be modified with /nojs to simulate its behavior without JavaScript.
        </p><ul>
        <li>@try_it_without_ajax</li>
        <li>@try_it_with_ajax</li>
      </ul>',
        [
          '@try_it_without_ajax' => Link::createFromRoute(
            $this->t('Try it without AJAX'),
            'ajax_example.dynamic_form_sections', ['nojs' => 'nojs'])
            ->toString(),
          '@try_it_with_ajax' => Link::createFromRoute(
            $this->t('Try it with AJAX'),
            'ajax_example.dynamic_form_sections')
            ->toString(),
        ]
      ),
    ];

    $form['question_type_select'] = [
      // This is our select dropdown.
      '#type' => 'select',
      '#title' => $this->t('Question style'),
      // We have a variety of form items you can use to get input from the user.
      '#options' => [
        'Choose question style' => 'Choose question style',
        'Multiple Choice' => 'Multiple Choice',
        'True/False' => 'True/False',
        'Fill-in-the-blanks' => 'Fill-in-the-blanks',
      ],
      // The #ajax section tells the AJAX system that whenever this dropdown
      // emits an event, it should call the callback and put the resulting
      // content into the wrapper we specify. The questions-fieldset-wrapper is
      // defined below.
      '#ajax' => [
        'wrapper' => 'questions-fieldset-wrapper',
        'callback' => '::promptCallback',
      ],
    ];
    // The CSS for this module hides this next button if JS is enabled.
    $form['question_type_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose'),
      '#attributes' => ['class' => ['ajax-example-inline']],
      // No need to validate when submitting this.
      '#limit_validation_errors' => [],
      '#validate' => [],
    ];

    // This section allows us to demonstrate no-AJAX use without turning off
    // javascript in the browser.
    if ($nojs != 'nojs') {
      // Allow JavaScript to hide the choose button if we're using AJAX.
      $form['question_type_submit']['#attributes']['class'][] = 'ajax-example-hide';
    }
    else {
      // Remove #ajax from the above, so it won't perform AJAX behaviors.
      unset($form['question_type_select']['#ajax']);
    }

    // This fieldset just serves as a container for the part of the form
    // that gets rebuilt. It has a nice line around it so you can see it.
    $form['questions_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Stuff will appear here'),
      '#open' => TRUE,
      // We set the ID of this fieldset to questions-fieldset-wrapper so the
      // AJAX command can replace it.
      '#attributes' => ['id' => 'questions-fieldset-wrapper'],
    ];

    // When the AJAX request comes in, or when the user hit 'Submit' if there is
    // no JavaScript, the form state will tell us what the user has selected
    // from the dropdown. We can look at the value of the dropdown to determine
    // which secondary form to display.
    $question_type = $form_state->getValue('question_type_select');
    if (!empty($question_type) && $question_type !== 'Choose question style') {

      $form['questions_fieldset']['question'] = [
        '#markup' => $this->t('Who was the first president of the U.S.?'),
      ];

      // Build up a secondary form, based on the type of question the user
      // chose.
      switch ($question_type) {
        case 'Multiple Choice':
          $form['questions_fieldset']['question'] = [
            '#type' => 'radios',
            '#title' => $this->t('Who was the first president of the United States'),
            '#options' => [
              'George Bush' => 'George Bush',
              'Adam McGuire' => 'Adam McGuire',
              'Abraham Lincoln' => 'Abraham Lincoln',
              'George Washington' => 'George Washington',
            ],

          ];
          break;

        case 'True/False':
          $form['questions_fieldset']['question'] = [
            '#type' => 'radios',
            '#title' => $this->t('Was George Washington the first president of the United States?'),
            '#options' => [
              'George Washington' => 'True',
              0 => 'False',
            ],
            '#description' => $this->t('Click "True" if you think George Washington was the first president of the United States.'),
          ];
          break;

        case 'Fill-in-the-blanks':
          $form['questions_fieldset']['question'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Who was the first president of the United States'),
            '#description' => $this->t('Please type the correct answer to the question.'),
          ];
          break;
      }

      $form['questions_fieldset']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit your answer'),
      ];
    }
    return $form;
  }

  /**
   * Final submit handler.
   *
   * Reports what values were finally set.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = $this->messenger();
    // This is only executed when a button is pressed, not when the AJAXfield
    // select is changed.
    // Now handle the case of the next, previous, and submit buttons.
    // Only submit will result in actual submission, all others rebuild.
    if ($form_state->getValue('question_type_submit') == 'Choose') {
      $form_state->setValue('question_type_select', $form_state->getUserInput()['question_type_select']);
      $form_state->setRebuild();
    }

    if ($form_state->getValue('submit') == 'Submit your answer') {
      $form_state->setRebuild(FALSE);
      $answer = $form_state->getValue('question');
      // Special handling for the checkbox.
      if ($answer == 1 && $form['questions_fieldset']['question']['#type'] == 'checkbox') {
        $answer = $form['questions_fieldset']['question']['#title'];
      }
      if ($answer == $this->t('George Washington')) {
        $messenger->addMessage($this->t('You got the right answer: @answer', ['@answer' => $answer]));
      }
      else {
        $messenger->addMessage($this->t('Sorry, your answer (@answer) is wrong', ['@answer' => $answer]));
      }
      return;
    }
    // Sets the form to be rebuilt after processing.
    $form_state->setRebuild();
  }

  /**
   * Callback for the select element.
   *
   * Since the questions_fieldset part of the form has already been built during
   * the AJAX request, we can return only that part of the form to the AJAX
   * request, and it will insert that part into questions-fieldset-wrapper.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function promptCallback(array $form, FormStateInterface $form_state) {
    return $form['questions_fieldset'];
  }

}
