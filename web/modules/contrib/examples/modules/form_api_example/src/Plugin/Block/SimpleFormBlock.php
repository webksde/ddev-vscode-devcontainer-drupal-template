<?php

namespace Drupal\form_api_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\form_api_example\Form\SimpleForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Example: Display a form' block.
 *
 * This example demonstrates the use of the form_builder service, an
 * instance of \Drupal\Core\Form\FormBuilder, in order to retrieve and display
 * a form.
 *
 * @Block(
 *   id = "form_api_example_simple_form_block",
 *   admin_label = @Translation("Example: Display a form")
 * )
 */
class SimpleFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [
      'description' => [
        '#markup' => $this->t('Using form provided by @classname', ['@classname' => SimpleForm::class]),
      ],
    ];

    // Use the form builder service to retrieve a form by providing the full
    // name of the class that implements the form you want to display. getForm()
    // will return a render array representing the form that can be used
    // anywhere render arrays are used.
    //
    // In this case the build() method of a block plugin is expected to return
    // a render array so we add the form to the existing output and return it.
    $output['form'] = $this->formBuilder->getForm(SimpleForm::class);
    return $output;
  }

}
