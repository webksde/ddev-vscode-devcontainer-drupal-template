<?php

namespace Drupal\form_api_example\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the ajax demo form controller.
 *
 * This example demonstrates using ajax callbacks to populate the options of a
 * color select element dynamically based on the value selected in another
 * select element in the form.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class AjaxColorForm extends DemoBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_api_example_ajax_color_demo';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This form example demonstrates functioning of an AJAX callback.'),
    ];

    // The #ajax attribute used in the temperature input element defines an ajax
    // callback that will invoke the 'updateColor' method on this form object.
    // Whenever the temperature element changes, it will invoke this callback
    // and replace the contents of the 'color_wrapper' container with the
    // results of this method call.
    $form['temperature'] = [
      '#title' => $this->t('Temperature'),
      '#type' => 'select',
      '#options' => $this->getColorTemperatures(),
      '#empty_option' => $this->t('- Select a color temperature -'),
      '#ajax' => [
        // Could also use [get_class($this), 'updateColor'].
        'callback' => '::updateColor',
        'wrapper' => 'color-wrapper',
      ],
    ];

    // Add a wrapper that can be replaced with new HTML by the ajax callback.
    // This is given the ID that was passed to the ajax callback in the '#ajax'
    // element above.
    $form['color_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'color-wrapper'],
    ];

    // Add a color element to the color_wrapper container using the value
    // from temperature to determine which colors to include in the select
    // element.
    $temperature = $form_state->getValue('temperature');
    if (!empty($temperature)) {
      $form['color_wrapper']['color'] = [
        '#type' => 'select',
        '#title' => $this->t('Color'),
        '#options' => $this->getColorsByTemperature($temperature),
      ];
    }

    // Add a submit button that handles the submission of the form.
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];

    return $form;
  }

  /**
   * Ajax callback for the color dropdown.
   */
  public function updateColor(array $form, FormStateInterface $form_state) {
    return $form['color_wrapper'];
  }

  /**
   * Returns colors that correspond with the given temperature.
   *
   * @param string $temperature
   *   The color temperature for which to return a list of colors. Can be either
   *   'warm' or 'cool'.
   *
   * @return array
   *   An associative array of colors that correspond to the given color
   *   temperature, suitable to use as form options.
   */
  protected function getColorsByTemperature($temperature) {
    return $this->getColors()[$temperature]['colors'];
  }

  /**
   * Returns a list of color temperatures.
   *
   * @return array
   *   An associative array of color temperatures, suitable to use as form
   *   options.
   */
  protected function getColorTemperatures() {
    return array_map(function ($color_data) {
      return $color_data['name'];
    }, $this->getColors());
  }

  /**
   * Returns an array of colors grouped by color temperature.
   *
   * @return array
   *   An associative array of color data, keyed by color temperature.
   */
  protected function getColors() {
    return [
      'warm' => [
        'name' => $this->t('Warm'),
        'colors' => [
          'red' => $this->t('Red'),
          'orange' => $this->t('Orange'),
          'yellow' => $this->t('Yellow'),
        ],
      ],
      'cool' => [
        'name' => $this->t('Cool'),
        'colors' => [
          'blue' => $this->t('Blue'),
          'purple' => $this->t('Purple'),
          'green' => $this->t('Green'),
        ],
      ],
    ];
  }

}
