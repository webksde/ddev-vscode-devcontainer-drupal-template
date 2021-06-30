<?php

namespace Drupal\form_api_example\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the container demo form.
 *
 * This example demonstrates commonly used container elements in a form.
 * Container elements are often used to group elements within a form.
 *
 * The submit handler in this form is provided by the DemoBase class.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ContainerDemo extends DemoBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This form example demonstrates container elements: details, fieldset and container.'),
    ];

    // Details containers replace D7's collapsible field sets.
    $form['author'] = [
      '#type' => 'details',
      '#title' => 'Author Info (type = details)',
    ];

    $form['author']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];

    $form['author']['pen_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pen Name'),
    ];

    // Conventional field set.
    $form['book'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Book Info (type = fieldset)'),
    ];

    $form['book']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
    ];

    $form['book']['publisher'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher'),
    ];

    // Containers have no visual display but wrap any contained elements in a
    // div tag.
    $form['accommodation'] = [
      '#type' => 'container',
    ];

    $form['accommodation']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Special Accommodations (type = container)'),
    ];

    $form['accommodation']['diet'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dietary Restrictions'),
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_api_example_container_demo';
  }

}
