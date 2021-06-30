<?php

namespace Drupal\admin_toolbar_tools\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminToolbarToolsSettingsForm.
 *
 * @package Drupal\admin_toolbar_tools\Form
 */
class AdminToolbarToolsSettingsForm extends ConfigFormBase {

  /**
   * The cache menu instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheMenu;

  /**
   * The menu link manager instance.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * AdminToolbarToolsSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   A menu link manager instance.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheMenu
   *   A cache menu instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, MenuLinkManagerInterface $menuLinkManager, CacheBackendInterface $cacheMenu) {
    parent::__construct($configFactory);
    $this->cacheMenu = $cacheMenu;
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.menu.link'),
      $container->get('cache.menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'admin_toolbar_tools.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_toolbar_tools_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('admin_toolbar_tools.settings');
    $form['max_bundle_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of bundle sub-menus to display'),
      '#description' => $this->t('Loading a large number of items can cause performance issues.'),
      '#default_value' => $config->get('max_bundle_number'),
    ];

    $form['hoverintent_functionality'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable/Disable the hoverintent functionality'),
      '#description' => $this->t('Check it if you want to enable the hoverintent feature.'),
      '#default_value' => $config->get('hoverintent_functionality'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('admin_toolbar_tools.settings')
      ->set('max_bundle_number', $form_state->getValue('max_bundle_number'))
      ->set('hoverintent_functionality', $form_state->getValue('hoverintent_functionality'))
      ->save();
    parent::submitForm($form, $form_state);
    $this->cacheMenu->invalidateAll();
    $this->menuLinkManager->rebuild();
  }

}
