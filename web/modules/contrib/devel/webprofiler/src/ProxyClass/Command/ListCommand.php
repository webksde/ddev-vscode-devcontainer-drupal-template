<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php
 * 'Drupal\webprofiler\Command\ListCommand'
 * "modules/contrib/devel/webprofiler/src".
 */

namespace Drupal\webprofiler\ProxyClass\Command {

  /**
   * Provides a proxy class for \Drupal\webprofiler\Command\ListCommand.
   *
   * @see \Drupal\Component\ProxyBuilder
   */
  class ListCommand {

    use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

    /**
     * The id of the original proxied service.
     *
     * @var string
     */
    protected $drupalProxyOriginalServiceId;

    /**
     * The real proxied service, after it was lazy loaded.
     *
     * @var \Drupal\webprofiler\Command\ListCommand
     */
    protected $service;

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructs a ProxyClass Drupal proxy object.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container.
     * @param string $drupal_proxy_original_service_id
     *   The service ID of the original service.
     */
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id) {
      $this->container = $container;
      $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
    }

    /**
     * Lazy loads the real service from the container.
     *
     * @return object
     *   Returns the constructed real service.
     */
    protected function lazyLoadItself() {
      if (!isset($this->service)) {
        $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
      }

      return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function showMessage($output, $message, $type = 'info') {
      return $this->lazyLoadItself()->showMessage($output, $message, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function ignoreValidationErrors() {
      return $this->lazyLoadItself()->ignoreValidationErrors();
    }

    /**
     * {@inheritdoc}
     */
    public function setApplication(\Symfony\Component\Console\Application $application = NULL) {
      return $this->lazyLoadItself()->setApplication($application);
    }

    /**
     * {@inheritdoc}
     */
    public function setHelperSet(\Symfony\Component\Console\Helper\HelperSet $helperSet) {
      return $this->lazyLoadItself()->setHelperSet($helperSet);
    }

    /**
     * {@inheritdoc}
     */
    public function getHelperSet() {
      return $this->lazyLoadItself()->getHelperSet();
    }

    /**
     * {@inheritdoc}
     */
    public function getApplication() {
      return $this->lazyLoadItself()->getApplication();
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled() {
      return $this->lazyLoadItself()->isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function run(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
      return $this->lazyLoadItself()->run($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function setCode(callable $code) {
      return $this->lazyLoadItself()->setCode($code);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeApplicationDefinition($mergeArgs = TRUE) {
      return $this->lazyLoadItself()->mergeApplicationDefinition($mergeArgs);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition) {
      return $this->lazyLoadItself()->setDefinition($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
      return $this->lazyLoadItself()->getDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function getNativeDefinition() {
      return $this->lazyLoadItself()->getNativeDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function addArgument($name, $mode = NULL, $description = '', $default = NULL) {
      return $this->lazyLoadItself()
        ->addArgument($name, $mode, $description, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function addOption($name, $shortcut = NULL, $mode = NULL, $description = '', $default = NULL) {
      return $this->lazyLoadItself()
        ->addOption($name, $shortcut, $mode, $description, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name) {
      return $this->lazyLoadItself()->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessTitle($title) {
      return $this->lazyLoadItself()->setProcessTitle($title);
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
      return $this->lazyLoadItself()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setHidden($hidden) {
      return $this->lazyLoadItself()->setHidden($hidden);
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden() {
      return $this->lazyLoadItself()->isHidden();
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description) {
      return $this->lazyLoadItself()->setDescription($description);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
      return $this->lazyLoadItself()->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function setHelp($help) {
      return $this->lazyLoadItself()->setHelp($help);
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp() {
      return $this->lazyLoadItself()->getHelp();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessedHelp() {
      return $this->lazyLoadItself()->getProcessedHelp();
    }

    /**
     * {@inheritdoc}
     */
    public function setAliases($aliases) {
      return $this->lazyLoadItself()->setAliases($aliases);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases() {
      return $this->lazyLoadItself()->getAliases();
    }

    /**
     * {@inheritdoc}
     */
    public function getSynopsis($short = FALSE) {
      return $this->lazyLoadItself()->getSynopsis($short);
    }

    /**
     * {@inheritdoc}
     */
    public function addUsage($usage) {
      return $this->lazyLoadItself()->addUsage($usage);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsages() {
      return $this->lazyLoadItself()->getUsages();
    }

    /**
     * {@inheritdoc}
     */
    public function getHelper($name) {
      return $this->lazyLoadItself()->getHelper($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer($container) {
      return $this->lazyLoadItself()->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
      return $this->lazyLoadItself()->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
      return $this->lazyLoadItself()->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslator($translator) {
      return $this->lazyLoadItself()->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function trans($key) {
      return $this->lazyLoadItself()->trans($key);
    }

  }

}
