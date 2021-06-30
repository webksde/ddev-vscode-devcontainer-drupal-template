<?php

namespace Drupal\form_api_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements InputDemo form controller.
 *
 * This example demonstrates the different input elements that are used to
 * collect data in a form.
 */
class InputDemo extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This example shows the use of all input-types.'),
    ];

    // CheckBoxes.
    $form['tests_taken'] = [
      '#type' => 'checkboxes',
      '#options' => ['SAT' => $this->t('SAT'), 'ACT' => $this->t('ACT')],
      '#title' => $this->t('What standardized tests did you take?'),
      '#description' => 'Checkboxes, #type = checkboxes',
    ];

    // Color.
    $form['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#default_value' => '#ffffff',
      '#description' => 'Color, #type = color',
    ];

    // Date.
    $form['expiration'] = [
      '#type' => 'date',
      '#title' => $this->t('Content expiration'),
      '#default_value' => ['year' => 2020, 'month' => 2, 'day' => 15],
      '#description' => 'Date, #type = date',
    ];

    // Date-time.
    $form['datetime'] = [
      '#type' => 'datetime',
      '#title' => 'Date Time',
      '#date_increment' => 1,
      '#date_timezone' => date_default_timezone_get(),
      '#default_value' => date_default_timezone_get(),
      '#description' => $this->t('Date time, #type = datetime'),
    ];

    // URL.
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#size' => 30,
      '#description' => $this->t('URL, #type = url'),
    ];

    // Email.
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Email, #type = email'),
    ];

    // Number.
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#description' => $this->t('Number, #type = number'),
    ];

    // Password.
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => 'Password, #type = password',
    ];

    // Password Confirm.
    $form['password_confirm'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('New Password'),
      '#description' => $this->t('PasswordConfirm, #type = password_confirm'),
    ];

    // Range.
    $form['size'] = [
      '#type' => 'range',
      '#title' => $this->t('Size'),
      '#min' => 10,
      '#max' => 100,
      '#description' => $this->t('Range, #type = range'),
    ];

    // Radios.
    $form['settings']['active'] = [
      '#type' => 'radios',
      '#title' => $this->t('Poll status'),
      '#options' => [0 => $this->t('Closed'), 1 => $this->t('Active')],
      '#description' => $this->t('Radios, #type = radios'),
    ];

    // Search.
    $form['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#description' => $this->t('Search, #type = search'),
    ];

    // Select.
    $form['favorite'] = [
      '#type' => 'select',
      '#title' => $this->t('Favorite color'),
      '#options' => [
        'red' => $this->t('Red'),
        'blue' => $this->t('Blue'),
        'green' => $this->t('Green'),
      ],
      '#empty_option' => $this->t('-select-'),
      '#description' => $this->t('Select, #type = select'),
    ];

    // Multiple values option elements.
    $form['select_multiple'] = [
      '#type' => 'select',
      '#title' => 'Select (multiple)',
      '#multiple' => TRUE,
      '#options' => [
        'sat' => 'SAT',
        'act' => 'ACT',
        'none' => 'N/A',
      ],
      '#default_value' => ['sat'],
      '#description' => 'Select Multiple',
    ];

    // Tel.
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#description' => $this->t('Tel, #type = tel'),
    ];

    // Details.
    $form['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Details'),
      '#description' => $this->t('Details, #type = details'),
    ];

    // TableSelect.
    $options = [
      1 => ['first_name' => 'Indy', 'last_name' => 'Jones'],
      2 => ['first_name' => 'Darth', 'last_name' => 'Vader'],
      3 => ['first_name' => 'Super', 'last_name' => 'Man'],
    ];

    $header = [
      'first_name' => $this->t('First Name'),
      'last_name' => $this->t('Last Name'),
    ];

    $form['table'] = [
      '#type' => 'tableselect',
      '#title' => $this->t('Users'),
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No users found'),
    ];

    // Textarea.
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#description' => $this->t('Textarea, #type = textarea'),
    ];

    // Text format.
    $form['text_format'] = [
      '#type' => 'text_format',
      '#title' => 'Text format',
      '#format' => 'plain_text',
      '#expected_value' => [
        'value' => 'Text value',
        'format' => 'plain_text',
      ],
      '#textformat_value' => [
        'value' => 'Testvalue',
        'format' => 'filtered_html',
      ],
      '#description' => $this->t('Text format, #type = text_format'),
    ];

    // Textfield.
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => $this->t('Textfield, #type = textfield'),
    ];

    // Weight.
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => 10,
      '#description' => $this->t('Weight, #type = weight'),
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Extra actions for the display.
    $form['actions']['extra_actions'] = [
      '#type' => 'dropbutton',
      '#links' => [
        'simple_form' => [
          'title' => $this->t('Simple Form'),
          'url' => Url::fromRoute('form_api_example.simple_form'),
        ],
        'demo' => [
          'title' => $this->t('Build Demo'),
          'url' => Url::fromRoute('form_api_example.build_demo'),
        ],
      ],
    ];

    // File.
    $form['file'] = [
      '#type' => 'file',
      '#title' => 'File',
      '#description' => $this->t('File, #type = file'),
    ];

    // Manage file.
    $form['managed_file'] = [
      '#type' => 'managed_file',
      '#title' => 'Managed file',
      '#description' => $this->t('Manage file, #type = managed_file'),
    ];

    // Image Buttons.
    $form['image_button'] = [
      '#type' => 'image_button',
      '#value' => 'Image button',
      '#src' => drupal_get_path('module', 'examples') . '/images/100x30.svg',
      '#description' => $this->t('image file, #type = image_button'),
    ];

    // Button.
    $form['button'] = [
      '#type' => 'button',
      '#value' => 'Button',
      '#description' => $this->t('Button, #type = button'),
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#description' => $this->t('Submit, #type = submit'),
    ];

    // Add a reset button that handles the submission of the form.
    $form['actions']['reset'] = [
      '#type' => 'button',
      '#button_type' => 'reset',
      '#value' => $this->t('Reset'),
      '#description' => $this->t('Submit, #type = button, #button_type = reset, #attributes = this.form.reset();return false'),
      '#attributes' => [
        'onclick' => 'this.form.reset(); return false;',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_api_example_input_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Find out what was submitted.
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $label = isset($form[$key]['#title']) ? $form[$key]['#title'] : $key;

      // Many arrays return 0 for unselected values so lets filter that out.
      if (is_array($value)) {
        $value = array_filter($value);
      }

      // Only display for controls that have titles and values.
      if ($value && $label) {
        $display_value = is_array($value) ? preg_replace('/[\n\r\s]+/', ' ', print_r($value, 1)) : $value;
        $message = $this->t('Value for %title: %value', ['%title' => $label, '%value' => $display_value]);
        $this->messenger()->addMessage($message);
      }
    }
  }

}
