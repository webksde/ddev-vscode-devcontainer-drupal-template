<?php

namespace Drupal\admin_toolbar_tools\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default implementation for menu link plugins.
 */
class ExtraLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The admin toolbar tools configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RouteProviderInterface $route_provider, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->routeProvider = $route_provider;
    $this->themeHandler = $theme_handler;
    $this->config = $config_factory->get('admin_toolbar_tools.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('router.route_provider'),
      $container->get('theme_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    $max_bundle_number = $this->config->get('max_bundle_number');
    $entity_types = $this->entityTypeManager->getDefinitions();
    $content_entities = [];
    foreach ($entity_types as $key => $entity_type) {
      if ($entity_type->getBundleEntityType() && ($entity_type->get('field_ui_base_route') != '')) {
        $content_entities[$key] = [
          'content_entity' => $key,
          'content_entity_bundle' => $entity_type->getBundleEntityType(),
        ];
      }
    }

    // Adds common links to entities.
    foreach ($content_entities as $entities) {
      $content_entity_bundle = $entities['content_entity_bundle'];
      $content_entity = $entities['content_entity'];
      $content_entity_bundle_storage = $this->entityTypeManager->getStorage($content_entity_bundle);
      $bundles_ids = $content_entity_bundle_storage->getQuery()->pager($max_bundle_number)->execute();
      $bundles = $this->entityTypeManager->getStorage($content_entity_bundle)->loadMultiple($bundles_ids);
      if (count($bundles) == $max_bundle_number && $this->routeExists('entity.' . $content_entity_bundle . '.collection')) {
        $links[$content_entity_bundle . '.collection'] = [
          'title' => $this->t('All types'),
          'route_name' => 'entity.' . $content_entity_bundle . '.collection',
          'parent' => 'entity.' . $content_entity_bundle . '.collection',
          'weight' => -1,
        ] + $base_plugin_definition;
      }
      foreach ($bundles as $machine_name => $bundle) {
        // Normally, the edit form for the bundle would be its root link.
        $content_entity_bundle_root = NULL;
        if ($this->routeExists('entity.' . $content_entity_bundle . '.overview_form')) {
          // Some bundles have an overview/list form that make a better root
          // link.
          $content_entity_bundle_root = 'entity.' . $content_entity_bundle . '.overview_form.' . $machine_name;
          $links[$content_entity_bundle_root] = [
            'route_name' => 'entity.' . $content_entity_bundle . '.overview_form',
            'parent' => 'entity.' . $content_entity_bundle . '.collection',
            'route_parameters' => [$content_entity_bundle => $machine_name],
            'class' => 'Drupal\admin_toolbar_tools\Plugin\Menu\MenuLinkEntity',
            'metadata' => [
              'entity_type' => $bundle->getEntityTypeId(),
              'entity_id' => $bundle->id(),
            ],
          ] + $base_plugin_definition;
        }
        if ($this->routeExists('entity.' . $content_entity_bundle . '.edit_form')) {
          $key = 'entity.' . $content_entity_bundle . '.edit_form.' . $machine_name;
          $links[$key] = [
            'route_name' => 'entity.' . $content_entity_bundle . '.edit_form',
            'parent' => 'entity.' . $content_entity_bundle . '.collection',
            'route_parameters' => [$content_entity_bundle => $machine_name],
          ] + $base_plugin_definition;
          if (empty($content_entity_bundle_root)) {
            $content_entity_bundle_root = $key;
            $links[$key]['parent'] = 'entity.' . $content_entity_bundle . '.collection';

            // When not grouped by bundle, use bundle name as title.
            $links[$key]['class'] = 'Drupal\admin_toolbar_tools\Plugin\Menu\MenuLinkEntity';
            $links[$key]['metadata'] = [
              'entity_type' => $bundle->getEntityTypeId(),
              'entity_id' => $bundle->id(),
            ];
          }
          else {
            $links[$key]['parent'] = $base_plugin_definition['id'] . ':' . $content_entity_bundle_root;
            $links[$key]['title'] = $this->t('Edit');
          }
        }
        if ($this->moduleHandler->moduleExists('field_ui')) {
          if ($this->routeExists('entity.' . $content_entity . '.field_ui_fields')) {
            $links['entity.' . $content_entity . '.field_ui_fields' . $machine_name] = [
              'title' => $this->t('Manage fields'),
              'route_name' => 'entity.' . $content_entity . '.field_ui_fields',
              'parent' => $base_plugin_definition['id'] . ':' . $content_entity_bundle_root,
              'route_parameters' => [$content_entity_bundle => $machine_name],
              'weight' => 1,
            ] + $base_plugin_definition;
          }
          if ($this->routeExists('entity.entity_form_display.' . $content_entity . '.default')) {
            $links['entity.entity_form_display.' . $content_entity . '.default' . $machine_name] = [
              'title' => $this->t('Manage form display'),
              'route_name' => 'entity.entity_form_display.' . $content_entity . '.default',
              'parent' => $base_plugin_definition['id'] . ':' . $content_entity_bundle_root,
              'route_parameters' => [$content_entity_bundle => $machine_name],
              'weight' => 2,
            ] + $base_plugin_definition;
          }
          if ($this->routeExists('entity.entity_view_display.' . $content_entity . '.default')) {
            $links['entity.entity_view_display.' . $content_entity . '.default.' . $machine_name] = [
              'title' => $this->t('Manage display'),
              'route_name' => 'entity.entity_view_display.' . $content_entity . '.default',
              'parent' => $base_plugin_definition['id'] . ':' . $content_entity_bundle_root,
              'route_parameters' => [$content_entity_bundle => $machine_name],
              'weight' => 3,
            ] + $base_plugin_definition;
          }
        }
        if ($this->moduleHandler->moduleExists('devel') && $this->routeExists('entity.' . $content_entity_bundle . '.devel_load')) {
          $links['entity.' . $content_entity_bundle . '.devel_load.' . $machine_name] = [
            'title' => $this->t('Devel'),
            'route_name' => 'entity.' . $content_entity_bundle . '.devel_load',
            'parent' => $base_plugin_definition['id'] . ':' . $content_entity_bundle_root,
            'route_parameters' => [$content_entity_bundle => $machine_name],
            'weight' => 4,
          ] + $base_plugin_definition;
        }
        if ($this->routeExists('entity.' . $content_entity_bundle . '.delete_form')) {
          $links['entity.' . $content_entity_bundle . '.delete_form.' . $machine_name] = [
            'title' => $this->t('Delete'),
            'route_name' => 'entity.' . $content_entity_bundle . '.delete_form',
            'parent' => $base_plugin_definition['id'] . ':' . $content_entity_bundle_root,
            'route_parameters' => [$content_entity_bundle => $machine_name],
            'weight' => 5,
          ] + $base_plugin_definition;
        }
      }
    }

    // Adds user links.
    $links['user.admin_create'] = [
      'title' => $this->t('Add user'),
      'route_name' => 'user.admin_create',
      'parent' => 'entity.user.collection',
    ] + $base_plugin_definition;
    $links['user.admin_permissions'] = [
      'title' => $this->t('Permissions'),
      'route_name' => 'user.admin_permissions',
      'parent' => 'entity.user.collection',
    ] + $base_plugin_definition;
    $links['entity.user_role.collection'] = [
      'title' => $this->t('Roles'),
      'route_name' => 'entity.user_role.collection',
      'parent' => 'entity.user.collection',
    ] + $base_plugin_definition;
    $links['user.logout'] = [
      'title' => $this->t('Logout'),
      'route_name' => 'user.logout',
      'parent' => 'admin_toolbar_tools.help',
      'weight' => 10,
    ] + $base_plugin_definition;
    $links['user.role_add'] = [
      'title' => $this->t('Add role'),
      'route_name' => 'user.role_add',
      'parent' => $base_plugin_definition['id'] . ':entity.user_role.collection',
      'weight' => -50,
    ] + $base_plugin_definition;
    // Adds sub-links to Account settings link.
    if ($this->moduleHandler->moduleExists('field_ui')) {
      $links['entity.user.field_ui_fields_'] = [
        'title' => $this->t('Manage fields'),
        'route_name' => 'entity.user.field_ui_fields',
        'parent' => 'entity.user.admin_form',
        'weight' => 1,
      ] + $base_plugin_definition;
      $links['entity.entity_form_display.user.default_'] = [
        'title' => $this->t('Manage form display'),
        'route_name' => 'entity.entity_form_display.user.default',
        'parent' => 'entity.user.admin_form',
        'weight' => 2,
      ] + $base_plugin_definition;
      $links['entity.entity_view_display.user.default_'] = [
        'title' => $this->t('Manage display'),
        'route_name' => 'entity.entity_view_display.user.default',
        'parent' => 'entity.user.admin_form',
        'weight' => 3,
      ] + $base_plugin_definition;
    }

    foreach ($this->entityTypeManager->getStorage('user_role')->loadMultiple() as $role) {
      $links['entity.user_role.edit_form.' . $role->id()] = [
        'route_name' => 'entity.user_role.edit_form',
        'parent' => $base_plugin_definition['id'] . ':entity.user_role.collection',
        'weight' => $role->getWeight(),
        'route_parameters' => ['user_role' => $role->id()],
        'class' => 'Drupal\admin_toolbar_tools\Plugin\Menu\MenuLinkEntity',
        'metadata' => [
          'entity_type' => $role->getEntityTypeId(),
          'entity_id' => $role->id(),
        ],
      ] + $base_plugin_definition;
      $links['entity.user_role.edit_permissions_form.' . $role->id()] = [
        'title' => $this->t('Edit permissions'),
        'route_name' => 'entity.user_role.edit_permissions_form',
        'parent' => $base_plugin_definition['id'] . ':entity.user_role.edit_form.' . $role->id(),
        'route_parameters' => ['user_role' => $role->id()],
      ] + $base_plugin_definition;
      if ($role->id() != 'anonymous' && $role->id() != 'authenticated') {
        $links['entity.user_role.delete_form.' . $role->id()] = [
          'title' => $this->t('Delete'),
          'route_name' => 'entity.user_role.delete_form',
          'parent' => $base_plugin_definition['id'] . ':entity.user_role.edit_form.' . $role->id(),
          'route_parameters' => ['user_role' => $role->id()],
        ] + $base_plugin_definition;
      }
      if ($this->moduleHandler->moduleExists('devel')) {
        $links['entity.user_role.devel_load.' . $role->id()] = [
          'title' => $this->t('Devel'),
          'route_name' => 'entity.user_role.devel_load',
          'parent' => $base_plugin_definition['id'] . ':entity.user_role.edit_form.' . $role->id(),
          'route_parameters' => ['user_role' => $role->id()],
        ] + $base_plugin_definition;
      }
    }

    if ($this->moduleHandler->moduleExists('node')) {
      $links['node.type_add'] = [
        'title' => $this->t('Add content type'),
        'route_name' => 'node.type_add',
        'parent' => 'entity.node_type.collection',
        'weight' => -2,
      ] + $base_plugin_definition;
      $links['node.add'] = [
        'title' => $this->t('Add content'),
        'route_name' => 'node.add_page',
        'parent' => 'system.admin_content',
      ] + $base_plugin_definition;
      // Adds node links for each content type.
      foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $type) {
        $links['node.add.' . $type->id()] = [
          'route_name' => 'node.add',
          'parent' => $base_plugin_definition['id'] . ':node.add',
          'route_parameters' => ['node_type' => $type->id()],
          'class' => 'Drupal\admin_toolbar_tools\Plugin\Menu\MenuLinkEntity',
          'metadata' => [
            'entity_type' => $type->getEntityTypeId(),
            'entity_id' => $type->id(),
          ],
        ] + $base_plugin_definition;
      }
    }

    if ($this->moduleHandler->moduleExists('field_ui')) {
      $links['field_ui.entity_form_mode_add'] = [
        'title' => $this->t('Add form mode'),
        'route_name' => 'field_ui.entity_form_mode_add',
        'parent' => 'entity.entity_form_mode.collection',
      ] + $base_plugin_definition;
      $links['field_ui.entity_view_mode_add'] = [
        'title' => $this->t('Add view mode'),
        'route_name' => 'field_ui.entity_view_mode_add',
        'parent' => 'entity.entity_view_mode.collection',
      ] + $base_plugin_definition;
    }

    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $links['entity.taxonomy_vocabulary.add_form'] = [
        'title' => $this->t('Add vocabulary'),
        'route_name' => 'entity.taxonomy_vocabulary.add_form',
        'parent' => 'entity.taxonomy_vocabulary.collection',
        'weight' => -5,
      ] + $base_plugin_definition;
    }

    if ($this->moduleHandler->moduleExists('menu_ui')) {
      $links['entity.menu.add_form'] = [
        'title' => $this->t('Add menu'),
        'route_name' => 'entity.menu.add_form',
        'parent' => 'entity.menu.collection',
        'weight' => -2,
      ] + $base_plugin_definition;
      // Adds links to /admin/structure/menu.
      $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
      uasort($menus, [Menu::class, 'sort']);
      $menus = array_slice($menus, 0, $max_bundle_number);
      if (count($menus) == $max_bundle_number) {
        $links['entity.menu.collection'] = [
          'title' => $this->t('All menus'),
          'route_name' => 'entity.menu.collection',
          'parent' => 'entity.menu.collection',
          'weight' => -1,
        ] + $base_plugin_definition;
      }
      $weight = 0;
      foreach ($menus as $menu_id => $menu) {
        $links['entity.menu.edit_form.' . $menu_id] = [
          'route_name' => 'entity.menu.edit_form',
          'parent' => 'entity.menu.collection',
          'route_parameters' => ['menu' => $menu_id],
          'weight' => $weight,
          'class' => 'Drupal\admin_toolbar_tools\Plugin\Menu\MenuLinkEntity',
          'metadata' => [
            'entity_type' => $menu->getEntityTypeId(),
            'entity_id' => $menu->id(),
          ],
        ] + $base_plugin_definition;
        $links['entity.menu.add_link_form.' . $menu_id] = [
          'title' => $this->t('Add link'),
          'route_name' => 'entity.menu.add_link_form',
          'parent' => $base_plugin_definition['id'] . ':entity.menu.edit_form.' . $menu_id,
          'route_parameters' => ['menu' => $menu_id],
        ] + $base_plugin_definition;
        // Un-deletable menus.
        $menus = ['admin', 'devel', 'footer', 'main', 'tools', 'account'];
        if (!in_array($menu_id, $menus)) {
          $links['entity.menu.delete_form.' . $menu_id] = [
            'title' => $this->t('Delete'),
            'route_name' => 'entity.menu.delete_form',
            'parent' => $base_plugin_definition['id'] . ':entity.menu.edit_form.' . $menu_id,
            'route_parameters' => ['menu' => $menu_id],
          ] + $base_plugin_definition;
        }
        if ($this->moduleHandler->moduleExists('devel') && $this->routeExists('entity.menu.devel_load')) {
          $links['entity.menu.devel_load.' . $menu_id] = [
            'title' => $this->t('Devel'),
            'route_name' => 'entity.menu.devel_load',
            'parent' => $base_plugin_definition['id'] . ':entity.menu.edit_form.' . $menu_id,
            'route_parameters' => ['menu' => $menu_id],
          ] + $base_plugin_definition;
        }
        $weight++;
      }
    }

    // If module block_content is enabled.
    if ($this->moduleHandler->moduleExists('block_content')) {
      $links['block_content.add_page'] = [
        'title' => $this->t('Add custom block'),
        'route_name' => 'block_content.add_page',
        'parent' => 'block.admin_display',
      ] + $base_plugin_definition;
      $links['entity.block_content.collection'] = [
        'title' => $this->t('Custom block library'),
        'route_name' => 'entity.block_content.collection',
        'parent' => 'block.admin_display',
      ] + $base_plugin_definition;
      $links['entity.block_content_type.collection'] = [
        'title' => $this->t('Block types'),
        'route_name' => 'entity.block_content_type.collection',
        'parent' => 'block.admin_display',
      ] + $base_plugin_definition;
    }

    // If module Contact is enabled.
    if ($this->moduleHandler->moduleExists('contact')) {
      $links['contact.form_add'] = [
        'title' => $this->t('Add contact form'),
        'route_name' => 'contact.form_add',
        'parent' => 'entity.contact_form.collection',
        'weight' => -5,
      ] + $base_plugin_definition;
    }

    // If module Update Manager is enabled.
    if ($this->moduleHandler->moduleExists('update')) {
      $links['update.module_install'] = [
        'title' => $this->t('Install new module'),
        'route_name' => 'update.module_install',
        'parent' => 'system.modules_list',
      ] + $base_plugin_definition;
      $links['update.module_update'] = [
        'title' => $this->t('Update'),
        'route_name' => 'update.module_update',
        'parent' => 'system.modules_list',
      ] + $base_plugin_definition;
      $links['update.theme_install'] = [
        'title' => $this->t('Install new theme'),
        'route_name' => 'update.theme_install',
        'parent' => 'system.themes_page',
      ] + $base_plugin_definition;
      $links['update.theme_update'] = [
        'title' => $this->t('Update'),
        'route_name' => 'update.theme_update',
        'parent' => 'system.themes_page',
      ] + $base_plugin_definition;
    }

    // If module Devel is enabled.
    if ($this->moduleHandler->moduleExists('devel')) {
      $links['devel'] = [
        'title' => $this->t('Development'),
        'route_name' => 'system.admin_config_development',
        'parent' => 'admin_toolbar_tools.help',
        'weight' => '-8',
      ] + $base_plugin_definition;
      $links['devel.admin_settings'] = [
        'title' => $this->t('Devel settings'),
        'route_name' => 'devel.admin_settings',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.configs_list'] = [
        'title' => $this->t('Config editor'),
        'route_name' => 'devel.configs_list',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.reinstall'] = [
        'title' => $this->t('Reinstall modules'),
        'route_name' => 'devel.reinstall',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.menu_rebuild'] = [
        'title' => $this->t('Rebuild menu'),
        'route_name' => 'devel.menu_rebuild',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.state_system_page'] = [
        'title' => $this->t('State editor'),
        'route_name' => 'devel.state_system_page',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.theme_registry'] = [
        'title' => $this->t('Theme registry'),
        'route_name' => 'devel.theme_registry',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.entity_info_page'] = [
        'title' => $this->t('Entity info'),
        'route_name' => 'devel.entity_info_page',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.session'] = [
        'title' => $this->t('Session viewer'),
        'route_name' => 'devel.session',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      $links['devel.element_info'] = [
        'title' => $this->t('Element Info'),
        'route_name' => 'devel.elements_page',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      // Menu link for the Toolbar module.
      $links['devel.toolbar.settings'] = [
        'title' => $this->t('Devel Toolbar Settings'),
        'route_name' => 'devel.toolbar.settings_form',
        'parent' => $base_plugin_definition['id'] . ':devel',
      ] + $base_plugin_definition;
      if ($this->moduleHandler->moduleExists('webprofiler')) {
        $links['devel.webprofiler'] = [
          'title' => $this->t('Webprofiler settings'),
          'route_name' => 'webprofiler.settings',
          'parent' => $base_plugin_definition['id'] . ':devel',
        ] + $base_plugin_definition;
      }
      // If module Devel PHP is enabled.
      if ($this->moduleHandler->moduleExists('devel_php') && $this->routeExists('devel_php.execute_php')) {
        $links['devel.devel_php.execute_php'] = [
          'title' => $this->t('Execute PHP Code'),
          'route_name' => 'devel_php.execute_php',
          'parent' => $base_plugin_definition['id'] . ':devel',
        ] + $base_plugin_definition;
      }
    }

    // If module Views Ui enabled.
    if ($this->moduleHandler->moduleExists('views_ui')) {
      $links['views_ui.add'] = [
        'title' => $this->t('Add view'),
        'route_name' => 'views_ui.add',
        'parent' => 'entity.view.collection',
        'weight' => -5,
      ] + $base_plugin_definition;
      $links['views_ui.field_list'] = [
        'title' => $this->t('Used in views'),
        'route_name' => 'views_ui.reports_fields',
        'parent' => 'entity.field_storage_config.collection',
      ] + $base_plugin_definition;
    }

    // Adds theme management links.
    $links['system.theme_settings'] = [
      'title' => $this->t('Settings'),
      'route_name' => 'system.theme_settings',
      'parent' => 'system.themes_page',
    ] + $base_plugin_definition;
    $installed_themes = $this->installedThemes();
    foreach ($installed_themes as $key_theme => $label_theme) {
      $links['system.theme_settings_theme.' . $key_theme] = [
        'title' => $label_theme,
        'route_name' => 'system.theme_settings_theme',
        'parent' => $base_plugin_definition['id'] . ':system.theme_settings',
        'route_parameters' => ['theme' => $key_theme],
      ] + $base_plugin_definition;
    }

    // If module Language enabled.
    if ($this->moduleHandler->moduleExists('language')) {
      $links['language.negotiation'] = [
        'title' => $this->t('Detection and selection'),
        'route_name' => 'language.negotiation',
        'parent' => 'entity.configurable_language.collection',
      ] + $base_plugin_definition;
      $links['language.add'] = [
        'title' => $this->t('Add language'),
        'route_name' => 'language.add',
        'parent' => 'entity.configurable_language.collection',
      ] + $base_plugin_definition;
    }

    // If module Media enabled.
    if ($this->moduleHandler->moduleExists('media')) {
      $links['media.type_add'] = [
        'title' => $this->t('Add media type'),
        'route_name' => 'entity.media_type.add_form',
        'parent' => 'entity.media_type.collection',
        'weight' => -2,
      ] + $base_plugin_definition;
      // Displays media link in toolbar.
      $links['media_page'] = [
        'title' => $this->t('Media'),
        'route_name' => 'entity.media.collection',
        'parent' => 'system.admin_content',
      ] + $base_plugin_definition;
      if ($this->moduleHandler->moduleExists('media_library')) {
        $links['media_library'] = [
            'title' => $this->t('Media library'),
            'route_name' => 'view.media_library.page',
            'parent' => $base_plugin_definition['id'] . ':media_page',
          ] + $base_plugin_definition;
      }
      $links['add_media'] = [
        'title' => $this->t('Add media'),
        'route_name' => 'entity.media.add_page',
        'parent' => $base_plugin_definition['id'] . ':media_page',
      ] + $base_plugin_definition;
      // Adds links for each media type.
      foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $type) {
        $links['media.add.' . $type->id()] = [
          'route_name' => 'entity.media.add_form',
          'parent' => $base_plugin_definition['id'] . ':add_media',
          'route_parameters' => ['media_type' => $type->id()],
          'class' => 'Drupal\admin_toolbar_tools\Plugin\Menu\MenuLinkEntity',
          'metadata' => [
            'entity_type' => $type->getEntityTypeId(),
            'entity_id' => $type->id(),
          ],
        ] + $base_plugin_definition;
      }
    }

    // If module Config enabled.
    if ($this->moduleHandler->moduleExists('config')) {
      $links['config.import'] = [
        'title' => $this->t('Import'),
        'route_name' => 'config.import_full',
        'parent' => 'config.sync',
        'weight' => 1,
      ] + $base_plugin_definition;
      $links['config.export'] = [
        'title' => $this->t('Export'),
        'route_name' => 'config.export_full',
        'parent' => 'config.sync',
        'weight' => 2,
      ] + $base_plugin_definition;
    }

    // Adds a menu link to clear Views cache.
    if ($this->moduleHandler->moduleExists('views')) {
      $links['flush_views'] = [
        'title' => $this->t('Flush views cache'),
        'route_name' => 'admin_toolbar_tools.flush_views',
        'parent' => 'admin_toolbar_tools.flush',
      ] + $base_plugin_definition;
      // Adding a menu link to Files.
      if ($this->moduleHandler->moduleExists('file') && $this->routeExists('view.files.page_1')) {
        $links['view.files'] = [
          'title' => $this->t('Files'),
          'route_name' => 'view.files.page_1',
          'parent' => 'system.admin_content',
        ] + $base_plugin_definition;
      }
    }

    return $links;
  }

  /**
   * Determine if a route exists by name.
   *
   * @param string $route_name
   *   The name of the route to check.
   *
   * @return bool
   *   Whether a route with that route name exists.
   */
  public function routeExists($route_name) {
    return (count($this->routeProvider->getRoutesByNames([$route_name])) === 1);
  }

  /**
   * Lists all installed themes.
   *
   * @return array
   *   The list of installed themes.
   */
  public function installedThemes() {
    $themeHandler = $this->themeHandler;
    $all_themes = $themeHandler->listInfo();
    $themes_installed = [];
    foreach ($all_themes as $key_theme => $theme) {
      if ($themeHandler->hasUi($key_theme)) {
        $themes_installed[$key_theme] = $themeHandler->getName($key_theme);
      }
    }
    return $themes_installed;
  }

}
