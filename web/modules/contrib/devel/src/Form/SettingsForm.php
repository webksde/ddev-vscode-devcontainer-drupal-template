<?php

namespace Drupal\devel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\devel\DevelDumperPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Devel Dumper Plugin Manager.
   *
   * @var \Drupal\devel\DevelDumperPluginManager
   */
  protected $dumperManager;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\devel\DevelDumperPluginManagerInterface $devel_dumper_manager
   *   Devel Dumper Plugin Manager.
   */
  public function __construct(DevelDumperPluginManagerInterface $devel_dumper_manager) {
    $this->dumperManager = $devel_dumper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.devel_dumper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devel.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $current_url = Url::createFromRequest($request);
    $devel_config = $this->config('devel.settings');

    $form['page_alter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display $page array'),
      '#default_value' => $devel_config->get('page_alter'),
      '#description' => $this->t('Display $page array from <a href="https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21theme.api.php/function/hook_page_attachments_alter/8">hook_page_attachments_alter()</a> in the messages area of each page.'),
    ];
    $form['raw_names'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display machine names of permissions and modules'),
      '#default_value' => $devel_config->get('raw_names'),
      '#description' => $this->t('Display the language-independent machine names of the permissions in mouse-over hints on the <a href=":permissions_url">Permissions</a> page and the module base file names on the Permissions and <a href=":modules_url">Modules</a> pages.', [
        ':permissions_url' => Url::fromRoute('user.admin_permissions')->toString(),
        ':modules_url' => Url::fromRoute('system.modules_list')->toString(),
      ]),
    ];
    $form['rebuild_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rebuild the theme registry on every page load'),
      '#description' => $this->t('New templates, theme overrides, and changes to the theme.info.yml need the theme registry to be rebuilt in order to appear on the site.'),
      '#default_value' => $devel_config->get('rebuild_theme'),
    ];

    $error_handlers = devel_get_handlers();
    $form['error_handlers'] = [
      '#type' => 'select',
      '#title' => $this->t('Error handlers'),
      '#options' => [
        DEVEL_ERROR_HANDLER_NONE => $this->t('None'),
        DEVEL_ERROR_HANDLER_STANDARD => $this->t('Standard Drupal'),
        DEVEL_ERROR_HANDLER_BACKTRACE_DPM => $this->t('Backtrace in the message area'),
        DEVEL_ERROR_HANDLER_BACKTRACE_KINT => $this->t('Backtrace above the rendered page'),
      ],
      '#multiple' => TRUE,
      '#default_value' => empty($error_handlers) ? DEVEL_ERROR_HANDLER_NONE : $error_handlers,
      '#description' => [
        [
          '#markup' => $this->t('Select the error handler(s) to use, in case you <a href=":choose">choose to show errors on screen</a>.', [':choose' => Url::fromRoute('system.logging_settings')->toString()]),
        ],
        [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('<em>None</em> is a good option when stepping through the site in your debugger.'),
            $this->t('<em>Standard Drupal</em> does not display all the information that is often needed to resolve an issue.'),
            $this->t('<em>Backtrace</em> displays nice debug information when any type of error is noticed, but only to users with the %perm permission.', ['%perm' => $this->t('Access developer information')]),
          ],
        ],
        [
          '#markup' => $this->t('Depending on the situation, the theme, the size of the call stack and the arguments, etc., some handlers may not display their messages, or display them on the subsequent page. Select <em>Standard Drupal</em> <strong>and</strong> <em>Backtrace above the rendered page</em> to maximize your chances of not missing any messages.') . '<br />' .
          $this->t('Demonstrate the current error handler(s):') . ' ' .
          Link::fromTextAndUrl('notice', $current_url->setOption('query', ['demo' => 'notice']))->toString() . ', ' .
          Link::fromTextAndUrl('notice+warning', $current_url->setOption('query', ['demo' => 'warning']))->toString() . ', ' .
          Link::fromTextAndUrl('notice+warning+error', $current_url->setOption('query', ['demo' => 'error']))->toString() . ' (' .
          $this->t('The presentation of the @error is determined by PHP.', ['@error' => 'error']) . ')',
        ],
      ],
    ];

    $form['error_handlers']['#size'] = count($form['error_handlers']['#options']);
    if ($request->query->has('demo')) {
      if ($request->getMethod() == 'GET') {
        $this->demonstrateErrorHandlers($request->query->get('demo'));
      }
      $request->query->remove('demo');
    }

    $dumper = $devel_config->get('devel_dumper');
    $default = $this->dumperManager->isPluginSupported($dumper) ? $dumper : $this->dumperManager->getFallbackPluginId(NULL);

    $form['dumper'] = [
      '#type' => 'radios',
      '#title' => $this->t('Variables Dumper'),
      '#options' => [],
      '#default_value' => $default,
      '#description' => $this->t('Select the debugging tool used for formatting and displaying the variables inspected through the debug functions of Devel. <strong>NOTE</strong>: Some of these plugins require external libraries for to be enabled. Learn how install external libraries with <a href=":url">Composer</a>.', [
        ':url' => 'https://www.drupal.org/node/2404989',
      ]),
    ];

    foreach ($this->dumperManager->getDefinitions() as $id => $definition) {
      $form['dumper']['#options'][$id] = $definition['label'];

      $supported = $this->dumperManager->isPluginSupported($id);
      $form['dumper'][$id]['#disabled'] = !$supported;

      $form['dumper'][$id]['#description'] = [
        '#type' => 'inline_template',
        '#template' => '{{ description }}{% if not supported %}<div><small>{% trans %}<strong>Not available</strong>. You may need to install external dependencies for use this plugin.{% endtrans %}</small></div>{% endif %}',
        '#context' => [
          'description' => $definition['description'],
          'supported' => $supported,
        ],
      ];
    }

    // Allow custom debug filename for use in DevelDumperManager::debug()
    $default_file = $devel_config->get('debug_logfile') ?: 'temporary://drupal_debug.txt';
    $form['debug_logfile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Debug Log File'),
      '#description' => $this->t('This is the log file that Devel functions such as ddm() write to. Use temporary:// to represent your systems temporary directory. Save with a blank filename to revert to the default.'),
      '#default_value' => $default_file,
    ];

    // Specify whether debug file should have <pre> tags around each $dump,
    // for use in Plugin\Devel\Dumper\DoctrineDebug::export()
    $form['debug_pre'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap debug in &lt;pre&gt; tags'),
      '#default_value' => $devel_config->get('debug_pre'),
      '#description' => $this->t('You may want the debug output wrapped in &lt;pre&gt; tags, depending on your debug file format and how it is displayed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('devel.settings')
      ->set('page_alter', $values['page_alter'])
      ->set('raw_names', $values['raw_names'])
      ->set('error_handlers', $values['error_handlers'])
      ->set('rebuild_theme', $values['rebuild_theme'])
      ->set('devel_dumper', $values['dumper'])
      ->set('debug_logfile', $values['debug_logfile'] ?: 'temporary://drupal_debug.txt')
      ->set('debug_pre', $values['debug_pre'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Demonstrates the capabilities of the error handler.
   *
   * @param string $severity
   *   The severity level for which demonstrate the error handler capabilities.
   */
  protected function demonstrateErrorHandlers($severity) {
    switch ($severity) {
      case 'notice':
        trigger_error('This is an example notice', E_USER_NOTICE);
        break;

      case 'warning':
        trigger_error('This is an example notice', E_USER_NOTICE);
        trigger_error('This is an example warning', E_USER_WARNING);
        break;

      case 'error':
        trigger_error('This is an example notice', E_USER_NOTICE);
        trigger_error('This is an example warning', E_USER_WARNING);
        trigger_error('This is an example error', E_USER_ERROR);
        break;
    }
  }

}
