<?php

namespace Drupal\ajax_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Re-populate a dropdown based on form state.
 */
class DependentDropdown extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_example_dependentdropdown';
  }

  /**
   * {@inheritdoc}
   *
   * The $nojs parameter is specified as a path parameter on the route.
   *
   * @see ajax_example.routing.yml
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
            'ajax_example.dependent_dropdown', ['nojs' => 'nojs'])
            ->toString(),
          '@try_it_with_ajax' => Link::createFromRoute(
            $this->t('Try it with AJAX'),
            'ajax_example.dependent_dropdown')
            ->toString(),
        ]
      ),
    ];

    // Our first dropdown lets us select a family of instruments: String,
    // Woodwind, Brass, or Percussion.
    $instrument_family_options = static::getFirstDropdownOptions();
    // When the AJAX request occurs, this form will be build in order to process
    // form state before the AJAX callback is called. We can use this
    // opportunity to populate the form as we wish based on the changes to the
    // form that caused the AJAX request. If the user caused the AJAX request,
    // then it would have been setting a value for instrument_family_options.
    // So if there's a value in that dropdown before we build it here, we grab
    // it's value to help us build the specific instrument dropdown. Otherwise
    // we can just use the value of the first item as the default value.
    if (empty($form_state->getValue('instrument_family_dropdown'))) {
      // Use a default value.
      $selected_family = key($instrument_family_options);
    }
    else {
      // Get the value if it already exists.
      $selected_family = $form_state->getValue('instrument_family_dropdown');
    }

    $form['instrument_family_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose an instrument family'),
    ];
    $form['instrument_family_fieldset']['instrument_family_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Instrument Type'),
      '#options' => $instrument_family_options,
      '#default_value' => $selected_family,
      // Bind an ajax callback to the change event (which is the default for the
      // select form type) of the first dropdown. It will replace the second
      // dropdown when rebuilt.
      '#ajax' => [
        // When 'event' occurs, Drupal will perform an ajax request in the
        // background. Usually the default value is sufficient (eg. change for
        // select elements), but valid values include any jQuery event,
        // most notably 'mousedown', 'blur', and 'submit'.
        'callback' => '::instrumentDropdownCallback',
        'wrapper' => 'instrument-fieldset-container',
      ],
    ];
    // Since we don't know if the user has js or not, we always need to output
    // this element, then hide it with with css if javascript is enabled.
    $form['instrument_family_fieldset']['choose_family'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose'),
      '#attributes' => ['class' => ['ajax-example-hide', 'ajax-example-inline']],
    ];
    // We are using the path parameter $nojs to signal when to simulate the
    // the user turning off JavaScript. We'll remove all the AJAX elements. This
    // is not required, and is here so that we can demonstrate a graceful
    // fallback without having to turn off JavaScript.
    if ($nojs == 'nojs') {
      // Removing the #ajax element tells the system not to use AJAX.
      unset($form['instrument_family_fieldset']['instrument_family_dropdown']['#ajax']);
      // Removing the ajax-example-hide class from the Choose button ensures
      // that our JavaScript won't hide it.
      unset($form['instrument_family_fieldset']['choose_family']['#attributes']);
    }

    // Since we're managing state for this whole fieldset (both the dropdown
    // and enabling the Submit button), we want to replace the whole thing
    // on AJAX requests. That's why we put it in this container.
    $form['instrument_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'instrument-fieldset-container'],
    ];
    // Build the instrument field set.
    $form['instrument_fieldset_container']['instrument_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose an instrument'),
    ];
    $form['instrument_fieldset_container']['instrument_fieldset']['instrument_dropdown'] = [
      '#type' => 'select',
      '#title' => $instrument_family_options[$selected_family] . ' ' . $this->t('Instruments'),
      // When the form is rebuilt during ajax processing, the $selected_family
      // variable will now have the new value and so the options will change.
      '#options' => static::getSecondDropdownOptions($selected_family),
      '#default_value' => !empty($form_state->getValue('instrument_dropdown')) ? $form_state->getValue('instrument_dropdown') : '',
    ];
    $form['instrument_fieldset_container']['instrument_fieldset']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    // We might normally use #state to disable the instrument fields based on
    // the instrument family fields. But since the premise is that we don't have
    // JavaScript running, #state won't work either. We have to set up the state
    // of the instrument fieldset here, based on the selected instrument family.
    if ($selected_family == 'none') {
      $form['instrument_fieldset_container']['instrument_fieldset']['instrument_dropdown']['#title'] =
        $this->t('You must choose an instrument family.');
      $form['instrument_fieldset_container']['instrument_fieldset']['instrument_dropdown']['#disabled'] = TRUE;
      $form['instrument_fieldset_container']['instrument_fieldset']['submit']['#disabled'] = TRUE;
    }
    else {
      $form['instrument_fieldset_container']['instrument_fieldset']['instrument_dropdown']['#disabled'] = FALSE;
      $form['instrument_fieldset_container']['instrument_fieldset']['submit']['#disabled'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = (string) $form_state->getTriggeringElement()['#value'];
    switch ($trigger) {
      case 'Submit':
        // Submit: We're done.
        $this->messenger()->addMessage($this->t('Your values have been submitted. Instrument family: @family, Instrument: @instrument', [
          '@family' => $form_state->getValue('instrument_family_dropdown'),
          '@instrument' => $form_state->getValue('instrument_dropdown'),
        ]));
        return;
    }
    // 'Choose' or anything else will cause rebuild of the form and present
    // it again.
    $form_state->setRebuild();
  }

  /**
   * Provide a new dropdown based on the AJAX call.
   *
   * This callback will occur *after* the form has been rebuilt by buildForm().
   * Since that's the case, the form should contain the right values for
   * instrument_dropdown.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the render structure that will replace the
   *   instrument-dropdown-replace form element.
   */
  public function instrumentDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['instrument_fieldset_container'];
  }

  /**
   * Helper function to populate the first dropdown.
   *
   * This would normally be pulling data from the database.
   *
   * @return array
   *   Dropdown options.
   */
  public static function getFirstDropdownOptions() {
    return [
      'none' => 'none',
      'String' => 'String',
      'Woodwind' => 'Woodwind',
      'Brass' => 'Brass',
      'Percussion' => 'Percussion',
    ];
  }

  /**
   * Helper function to populate the second dropdown.
   *
   * This would normally be pulling data from the database.
   *
   * @param string $key
   *   This will determine which set of options is returned.
   *
   * @return array
   *   Dropdown options
   */
  public static function getSecondDropdownOptions($key = '') {
    switch ($key) {
      case 'String':
        $options = [
          'Violin' => 'Violin',
          'Viola' => 'Viola',
          'Cello' => 'Cello',
          'Double Bass' => 'Double Bass',
        ];
        break;

      case 'Woodwind':
        $options = [
          'Flute' => 'Flute',
          'Clarinet' => 'Clarinet',
          'Oboe' => 'Oboe',
          'Bassoon' => 'Bassoon',
        ];
        break;

      case 'Brass':
        $options = [
          'Trumpet' => 'Trumpet',
          'Trombone' => 'Trombone',
          'French Horn' => 'French Horn',
          'Euphonium' => 'Euphonium',
        ];
        break;

      case 'Percussion':
        $options = [
          'Bass Drum' => 'Bass Drum',
          'Timpani' => 'Timpani',
          'Snare Drum' => 'Snare Drum',
          'Tambourine' => 'Tambourine',
        ];
        break;

      default:
        $options = ['none' => 'none'];
        break;
    }
    return $options;
  }

}
