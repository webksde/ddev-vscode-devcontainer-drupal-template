<?php

namespace Drupal\stage_file_proxy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide settings for Stage File Proxy.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, string $site_path) {
    parent::__construct($config_factory);

    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('site.path')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stage_file_proxy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'stage_file_proxy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    $config = $this->config('stage_file_proxy.settings');

    $form['origin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin website'),
      '#default_value' => $config->get('origin'),
      '#description' => $this->t("The origin website. For example: 'http://example.com'. If the site is using HTTP Basic Authentication (the browser popup for username and password) you can embed those in the url. Be sure to URL encode any special characters:<br/><br/>For example, setting a user name of 'myusername' and password as, 'letme&in' the configuration would be the following: <br/><br/>'http://myusername:letme%26in@example.com';"),
      '#required' => FALSE,
    ];

    $form['verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify SSL'),
      '#default_value' => $config->get('verify'),
      '#description' => $this->t('Verifies the validity of the SSL certificate presented by the server when checked (if HTTPS is used).'),
      '#required' => FALSE,
    ];

    $stage_file_proxy_origin_dir = $config->get('origin_dir');
    if (!$stage_file_proxy_origin_dir) {
      $stage_file_proxy_origin_dir = $config->get('file_public_path');
      if (empty($stage_file_proxy_origin_dir)) {
        $stage_file_proxy_origin_dir = $this->sitePath . '/files';
      }
    }
    $form['origin_dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Origin directory'),
      '#default_value' => $stage_file_proxy_origin_dir,
      '#description' => $this->t('If this is set then Stage File Proxy will use a different path for the remote files. This is useful for multisite installations where the sites directory contains different names for each url. If this is not set, it defaults to the same path as the local site.'),
      '#required' => FALSE,
    ];

    $form['use_imagecache_root'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Imagecache Root'),
      '#default_value' => $config->get('use_imagecache_root'),
      '#description' => $this->t("When checked Stage File Proxy will look for /imagecache/ in the URL and determine the original file and request that rather than the processed file, then send a header to the browser to refresh the image and let imagecache handle it. This will speed up future imagecache requests for the same original file."),
      '#required' => FALSE,
    ];

    $form['hotlink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hotlink'),
      '#default_value' => $config->get('hotlink'),
      '#description' => $this->t("When checked Stage File Proxy will not transfer the remote file to the local machine, it will just serve a 301 to the remote file and let the origin webserver handle it."),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $origin = $form_state->getValue('origin');

    if (!empty($origin) && filter_var($origin, FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('origin', 'Origin needs to be a valid URL.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('stage_file_proxy.settings');

    $keys = [
      'origin',
      'origin_dir',
      'use_imagecache_root',
      'hotlink',
      'verify',
    ];
    foreach ($keys as $key) {
      $value = $form_state->getValue($key);
      if ($key === 'origin') {
        $value = trim($value, '/ ');
      }
      $config->set($key, $value);
    }
    $config->save();
    $this->messenger()->addMessage($this->t('Your settings have been saved.'));
  }

}
