<?php

namespace Drupal\devel\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the container info pages.
 */
class ContainerInfoController extends ControllerBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The dumper manager service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * ServiceInfoController constructor.
   *
   * @param \Drupal\Core\DrupalKernelInterface $drupalKernel
   *   The drupal kernel.
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The dumper manager service.
   */
  public function __construct(DrupalKernelInterface $drupalKernel, DevelDumperManagerInterface $dumper) {
    $this->kernel = $drupalKernel;
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('kernel'),
      $container->get('devel.dumper')
    );
  }

  /**
   * Builds the services overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function serviceList() {
    $headers = [
      $this->t('ID'),
      $this->t('Class'),
      $this->t('Alias'),
      $this->t('Operations'),
    ];

    $rows = [];

    if ($container = $this->kernel->getCachedContainerDefinition()) {
      foreach ($container['services'] as $service_id => $definition) {
        $service = unserialize($definition);

        $row['id'] = [
          'data' => $service_id,
          'filter' => TRUE,
        ];
        $row['class'] = [
          'data' => isset($service['class']) ? $service['class'] : '',
          'filter' => TRUE,
        ];
        $row['alias'] = [
          'data' => array_search($service_id, $container['aliases']) ?: '',
          'filter' => TRUE,
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'devel' => [
              'title' => $this->t('Devel'),
              'url' => Url::fromRoute('devel.container_info.service.detail', ['service_id' => $service_id]),
              'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                  'minHeight' => 500,
                ]),
              ],
            ],
          ],
        ];

        $rows[$service_id] = $row;
      }

      ksort($rows);
    }

    $output['services'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter service id, alias or class'),
      '#filter_description' => $this->t('Enter a part of the service id, service alias or class to filter by.'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No services found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-service-list'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of the service.
   *
   * @param string $service_id
   *   The ID of the service to retrieve.
   *
   * @return array
   *   A render array containing the service detail.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested service is not defined.
   */
  public function serviceDetail($service_id) {
    $instance = $this->container->get($service_id, ContainerInterface::NULL_ON_INVALID_REFERENCE);
    if ($instance === NULL) {
      throw new NotFoundHttpException();
    }

    $output = [];

    if ($cached_definitions = $this->kernel->getCachedContainerDefinition()) {
      // Tries to retrieve the service definition from the kernel's cached
      // container definition.
      if (isset($cached_definitions['services'][$service_id])) {
        $definition = unserialize($cached_definitions['services'][$service_id]);

        // If the service has an alias add it to the definition.
        if ($alias = array_search($service_id, $cached_definitions['aliases'])) {
          $definition['alias'] = $alias;
        }

        $output['definition'] = $this->dumper->exportAsRenderable($definition, $this->t('Computed Definition'));
      }
    }

    $output['instance'] = $this->dumper->exportAsRenderable($instance, $this->t('Instance'));

    return $output;
  }

  /**
   * Builds the parameters overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function parameterList() {
    $headers = [
      $this->t('Name'),
      $this->t('Operations'),
    ];

    $rows = [];

    if ($container = $this->kernel->getCachedContainerDefinition()) {
      foreach ($container['parameters'] as $parameter_name => $definition) {
        $row['name'] = [
          'data' => $parameter_name,
          'filter' => TRUE,
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'devel' => [
              'title' => $this->t('Devel'),
              'url' => Url::fromRoute('devel.container_info.parameter.detail', ['parameter_name' => $parameter_name]),
              'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                  'minHeight' => 500,
                ]),
              ],
            ],
          ],
        ];

        $rows[$parameter_name] = $row;
      }

      ksort($rows);
    }

    $output['parameters'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter parameter name'),
      '#filter_description' => $this->t('Enter a part of the parameter name to filter by.'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No parameters found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-parameter-list'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of the parameter value.
   *
   * @param string $parameter_name
   *   The name of the parameter to retrieve.
   *
   * @return array
   *   A render array containing the parameter value.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested parameter is not defined.
   */
  public function parameterDetail($parameter_name) {
    try {
      $parameter = $this->container->getParameter($parameter_name);
    }
    catch (ParameterNotFoundException $e) {
      throw new NotFoundHttpException();
    }

    return $this->dumper->exportAsRenderable($parameter);
  }

}
