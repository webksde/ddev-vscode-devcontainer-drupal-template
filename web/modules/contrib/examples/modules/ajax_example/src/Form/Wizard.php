<?php

namespace Drupal\ajax_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AJAX example wizard.
 */
class Wizard extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_example_wizard';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Since FormBase uses service traits, we can inject these services without
    // adding our own __construct() method.
    $form = new static($container);
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $no_js_use = FALSE) {
    $url = Url::fromUri('internal:/examples/ajax-example/wizard-nojs');
    $link = Link::fromTextAndUrl($this->t('examples/ajax-example/wizard-nojs'), $url)
      ->toString();

    // Prepare link for multiple arguments.
    $urltwo = Url::fromUri('internal:/examples/ajax-example/wizard');
    $linktwo = Link::fromTextAndUrl($this->t('examples/ajax-example/wizard'), $urltwo)
      ->toString();

    // We want to deal with hierarchical form values.
    $form['#tree'] = TRUE;
    $form['description'] = [
      '#markup' => $this->t('This example is a step-by-step wizard. The @link does it without page reloads; the @link1 is the same code but simulates a non-javascript environment, showing it with page reloads.', [
        '@link' => $linktwo,
        '@link1' => $link,
      ]),
    ];

    $form['step'] = [
      '#type' => 'value',
      '#value' => !empty($form_state->getValue('step')) ? $form_state->getValue('step') : 1,
    ];

    switch ($form['step']['#value']) {
      case 1:
        $limit_validation_errors = [['step']];
        $form['step1'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 1: Personal details'),
        ];
        $form['step1']['name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Your name'),
          '#default_value' => $form_state->hasValue(['step1', 'name']) ? $form_state->getValue(['step1', 'name']) : '',
          '#required' => TRUE,
        ];
        break;

      case 2:
        $limit_validation_errors = [['step'], ['step1']];
        $form['step1'] = [
          '#type' => 'value',
          '#value' => $form_state->getValue('step1'),
        ];
        $form['step2'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 2: Street address info'),
        ];
        $form['step2']['address'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Your street address'),
          '#default_value' => $form_state->hasValue(['step2', 'address']) ? $form_state->getValue(['step2', 'address']) : '',
          '#required' => TRUE,
        ];
        break;

      case 3:
        $limit_validation_errors = [['step'], ['step1'], ['step2']];
        $form['step1'] = [
          '#type' => 'value',
          '#value' => $form_state->getValue('step1'),
        ];
        $form['step2'] = [
          '#type' => 'value',
          '#value' => $form_state->getValue('step2'),
        ];
        $form['step3'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 3: City info'),
        ];
        $form['step3']['city'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Your city'),
          '#default_value' => $form_state->hasValue(['step3', 'city']) ? $form_state->getValue(['step3', 'city']) : '',
          '#required' => TRUE,
        ];
        break;

      default:
        $limit_validation_errors = [];
    }

    $form['actions'] = ['#type' => 'actions'];
    if ($form['step']['#value'] > 1) {
      $form['actions']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous step'),
        '#limit_validation_errors' => $limit_validation_errors,
        '#submit' => ['::prevSubmit'],
        '#ajax' => [
          'wrapper' => 'ajax-example-wizard-wrapper',
          'callback' => '::prompt',
        ],
      ];
    }
    if ($form['step']['#value'] != 3) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next step'),
        '#submit' => ['::nextSubmit'],
        '#ajax' => [
          'wrapper' => 'ajax-example-wizard-wrapper',
          'callback' => '::prompt',
        ],
      ];
    }
    if ($form['step']['#value'] == 3) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t("Submit your information"),
      ];
    }

    // This simply allows us to demonstrate no-javascript use without
    // actually turning off javascript in the browser. Removing the #ajax
    // element turns off AJAX behaviors on that element and as a result
    // ajax.js doesn't get loaded.
    // For demonstration only! You don't need this.
    if ($no_js_use) {
      // Remove the #ajax from the above, so ajax.js won't be loaded.
      // For demonstration only.
      unset($form['actions']['next']['#ajax']);
      unset($form['actions']['prev']['#ajax']);
    }

    $form['#prefix'] = '<div id="ajax-example-wizard-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * Wizard callback function.
   *
   * @param array $form
   *   Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form API form.
   *
   * @return array
   *   Form array.
   */
  public function prompt(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Ajax callback that moves the form to the next step and rebuild the form.
   *
   * @param array $form
   *   The Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   *
   * @return array
   *   The Form API form.
   */
  public function nextSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setValue('step', $form_state->getValue('step') + 1);
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Ajax callback that moves the form to the previous step.
   *
   * @param array $form
   *   The Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   *
   * @return array
   *   The Form API form.
   */
  public function prevSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setValue('step', $form_state->getValue('step') - 1);
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Save away the current information.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = $this->messenger();
    $messenger->addMessage($this->t('Your information has been submitted:'));
    $messenger->addMessage($this->t('Name: @name', ['@name' => $form_state->getValue(['step1', 'name'])]));
    $messenger->addMessage($this->t('Address: @address', ['@address' => $form_state->getValue(['step2', 'address'])]));
    $messenger->addMessage($this->t('City: @city', ['@city' => $form_state->getValue(['step3', 'city'])]));

  }

}
