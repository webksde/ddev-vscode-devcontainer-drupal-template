<?php

namespace Drupal\form_api_example\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the ModalForm form controller.
 *
 * This example demonstrates implementation of a form that is designed to be
 * used as a modal form.  To properly display the modal the link presented by
 * the \Drupal\form_api_example\Controller\Page page controller loads the Drupal
 * dialog and ajax libraries.  The submit handler in this class returns ajax
 * commands to replace text in the calling page after submission .
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Create a new form object and inject its services.
    $form = new static();
    $form->setRequestStack($container->get('request_stack'));
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_api_example_modal_form';
  }

  /**
   * Helper method so we can have consistent dialog options.
   *
   * @return string[]
   *   An array of jQuery UI elements to pass on to our dialog form.
   */
  protected static function getDataDialogOptions() {
    return [
      'width' => '50%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nojs = NULL) {
    // Add the core AJAX library.
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This example demonstrates a form that can work as a normal multi-request form, or as a modal dialog using AJAX.'),
    ];
    // Add a link to show this form in a modal dialog if we're not already in
    // one.
    if ($nojs == 'nojs') {
      $form['use_ajax_container'] = [
        '#type' => 'details',
        '#open' => TRUE,
      ];
      $form['use_ajax_container']['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('In order to show a modal dialog by clicking on a link, that link has to have class <code>use-ajax</code> and <code>data-dialog-type="modal"</code>. This link has those attributes.'),
      ];
      $form['use_ajax_container']['use_ajax'] = [
        '#type' => 'link',
        '#title' => $this->t('See this form as a modal.'),
        '#url' => Url::fromRoute('form_api_example.modal_form', ['nojs' => 'ajax']),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode(static::getDataDialogOptions()),
          // Add this id so that we can test this form.
          'id' => 'ajax-example-modal-link',
        ],
      ];
    }

    // This element is responsible for displaying form errors in the AJAX
    // dialog.
    if ($nojs == 'ajax') {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -999,
      ];
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];

    // Set the form to not use AJAX if we're on a nojs path. When this form is
    // within the modal dialog, Drupal will make sure we're using an AJAX path
    // instead of a nojs one.
    if ($nojs == 'nojs') {
      unset($form['actions']['submit']['#ajax']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('title');
    $this->messenger()->addMessage(
      $this->t('Submit handler: You specified a title of @title.', ['@title' => $title])
    );
  }

  /**
   * Implements the submit handler for the modal dialog AJAX call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of AJAX commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    // We begin building a new ajax reponse.
    $response = new AjaxResponse();

    // If the user submitted the form and there are errors, show them the
    // input dialog again with error messages. Since the title element is
    // required, the empty string wont't validate and there will be an error.
    if ($form_state->getErrors()) {
      // If there are errors, we can show the form again with the errors in
      // the status_messages section.
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new OpenModalDialogCommand($this->t('Errors'), $form, static::getDataDialogOptions()));
    }
    // If there are no errors, show the output dialog.
    else {
      // We don't want any messages that were added by submitForm().
      $this->messenger()->deleteAll();
      // We use FormattableMarkup to handle sanitizing the input.
      // @todo: There's probably a better way to do this.
      $title = new FormattableMarkup(':title', [':title' => $form_state->getValue('title')]);
      // This will be the contents for the modal dialog.
      $content = [
        '#type' => 'item',
        '#markup' => $this->t("Your specified title of '%title' appears in this modal dialog.", ['%title' => $title]),
      ];
      // Add the OpenModalDialogCommand to the response. This will cause Drupal
      // AJAX to show the modal dialog. The user can click the little X to close
      // the dialog.
      $response->addCommand(new OpenModalDialogCommand($title, $content, static::getDataDialogOptions()));
    }

    // Finally return our response.
    return $response;
  }

}
