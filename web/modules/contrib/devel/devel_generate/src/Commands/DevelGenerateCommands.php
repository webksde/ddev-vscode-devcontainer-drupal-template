<?php

namespace Drupal\devel_generate\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drush\Commands\DrushCommands;

/**
 * Provide Drush commands for all the Devel Generate processes.
 *
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a
 * drush.services.yml in the root of your module like this module does.
 *
 * Note: Integer values for defaults need to be in quotes, otherwise they can
 * match with numeric constants such as InputOption::VALUE_OPTIONAL in
 * Consolidation\AnnotatedCommand\Parser\CommandInfo::createInputOptions()
 * and consequently get removed from the help output.
 */
class DevelGenerateCommands extends DrushCommands {

  /**
   * The DevelGenerate plugin manager.
   *
   * @var \Drupal\devel_generate\DevelGeneratePluginManager
   */
  protected $manager;

  /**
   * The plugin instance.
   *
   * @var \Drupal\devel_generate\DevelGenerateBaseInterface
   */
  protected $pluginInstance;

  /**
   * The Generate plugin parameters.
   *
   * @var array
   */
  protected $parameters;

  /**
   * DevelGenerateCommands constructor.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $manager
   *   The DevelGenerate plugin manager.
   */
  public function __construct(DevelGeneratePluginManager $manager) {
    parent::__construct();
    $this->setManager($manager);
  }

  /**
   * Get the DevelGenerate plugin manager.
   *
   * @return \Drupal\devel_generate\DevelGeneratePluginManager
   *   The DevelGenerate plugin manager.
   */
  public function getManager() {
    return $this->manager;
  }

  /**
   * Set the DevelGenerate plugin manager.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $manager
   *   The DevelGenerate plugin manager.
   */
  public function setManager(DevelGeneratePluginManager $manager) {
    $this->manager = $manager;
  }

  /**
   * Get the DevelGenerate plugin instance.
   *
   * @return mixed
   *   The DevelGenerate plugin instance.
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

  /**
   * Set the DevelGenerate plugin instance.
   *
   * @param mixed $pluginInstance
   *   The DevelGenerate plugin instance.
   */
  public function setPluginInstance($pluginInstance) {
    $this->pluginInstance = $pluginInstance;
  }

  /**
   * Get the DevelGenerate plugin parameters.
   *
   * @return array
   *   The plugin parameters.
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Set the DevelGenerate plugin parameters.
   *
   * @param array $parameters
   *   The plugin parameters.
   */
  public function setParameters(array $parameters) {
    $this->parameters = $parameters;
  }

  /**
   * Create users.
   *
   * @command devel-generate:users
   * @aliases genu, devel-generate-users
   * @pluginId user
   *
   * @param int $num
   *   Number of users to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete all users before generating new ones.
   * @option roles A comma delimited list of role IDs for new users. Don't specify 'authenticated'.
   * @option pass Specify a password to be set for all generated users.
   */
  public function users($num = 50, array $options = ['kill' => FALSE, 'roles' => '']) {
    // @todo pass $options to the plugins.
    $this->generate();
  }

  /**
   * Create terms in specified vocabulary.
   *
   * @command devel-generate:terms
   * @aliases gent, devel-generate-terms
   * @pluginId term
   *
   * @param int $num
   *   Number of terms to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete all terms in these vocabularies before generating new ones.
   * @option bundles A comma-delimited list of machine names for the vocabularies where terms will be created.
   * @option feedback An integer representing interval for insertion rate logging.
   * @option languages A comma-separated list of language codes
   * @option translations A comma-separated list of language codes for translations.
   * @option min-depth The minimum depth of hierarchy for the new terms.
   * @option max-depth The maximum depth of hierarchy for the new terms.
   */
  public function terms($num = 50, array $options = ['kill' => FALSE, 'bundles' => NULL, 'feedback' => '1000', 'languages' => NULL, 'translations' => NULL, 'min-depth' => '1', 'max-depth' => '4']) {
    $this->generate();
  }

  /**
   * Create vocabularies.
   *
   * @command devel-generate:vocabs
   * @aliases genv, devel-generate-vocabs
   * @pluginId vocabulary
   * @validate-module-enabled taxonomy
   *
   * @param int $num
   *   Number of vocabularies to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete all vocabs before generating new ones.
   */
  public function vocabs($num = 1, array $options = ['kill' => FALSE]) {
    $this->generate();
  }

  /**
   * Create menus.
   *
   * @command devel-generate:menus
   * @aliases genm, devel-generate-menus
   * @pluginId menu
   * @validate-module-enabled menu_link_content
   *
   * @param int $number_menus
   *   Number of menus to generate.
   * @param int $number_links
   *   Number of links to generate.
   * @param int $max_depth
   *   Max link depth.
   * @param int $max_width
   *   Max width of first level of links.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete any menus and menu links previously created by devel_generate before generating new ones.
   */
  public function menus($number_menus = 2, $number_links = 50, $max_depth = 3, $max_width = 8, array $options = ['kill' => FALSE]) {
    $this->generate();
  }

  /**
   * Create content.
   *
   * @command devel-generate:content
   * @aliases genc, devel-generate-content
   * @pluginId content
   * @validate-module-enabled node
   *
   * @param int $num
   *   Number of nodes to generate.
   * @param int $max_comments
   *   Maximum number of comments to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete all content before generating new content.
   * @option bundles A comma-delimited list of content types to create.
   * @option authors A comma delimited list of authors ids. Defaults to all users.
   * @option feedback An integer representing interval for insertion rate logging.
   * @option skip-fields A comma delimited list of fields to omit when generating random values
   * @option languages A comma-separated list of language codes
   * @option translations A comma-separated list of language codes for translations.
   * @option add-type-label Add the content type label to the front of the node title
   */
  public function content($num = 50, $max_comments = 0, array $options = ['kill' => FALSE, 'bundles' => 'page,article', 'authors' => NULL, 'feedback' => 1000, 'languages' => NULL, 'translations' => NULL, 'add-type-label' => FALSE]) {
    $this->generate();
  }

  /**
   * Create media items.
   *
   * @command devel-generate:media
   * @aliases genmd, devel-generate-media
   * @pluginId media
   * @validate-module-enabled media
   *
   * @param int $num
   *   Number of media items to generate.
   * @param array $options
   *   Array of options as described below.
   *
   * @option kill Delete all media items before generating new media.
   * @option media-types A comma-delimited list of media types to create.
   * @option feedback An integer representing interval for insertion rate logging.
   * @option skip-fields A comma delimited list of fields to omit when generating random values.
   * @option languages A comma-separated list of language codes
   */
  public function media($num = 50, array $options = ['kill' => FALSE, 'media-types' => NULL, 'feedback' => 1000]) {
    $this->generate();
  }

  /**
   * The standard drush validate hook.
   *
   * @hook validate
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The data sent from the drush command.
   */
  public function validate(CommandData $commandData) {
    $manager = $this->getManager();
    $args = $commandData->input()->getArguments();
    // The command name is the first argument but we do not need this.
    array_shift($args);
    /* @var DevelGenerateBaseInterface $instance */
    $instance = $manager->createInstance($commandData->annotationData()->get('pluginId'), []);
    $this->setPluginInstance($instance);
    $parameters = $instance->validateDrushParams($args, $commandData->input()->getOptions());
    $this->setParameters($parameters);
  }

  /**
   * Wrapper for calling the plugin instance generate function.
   */
  public function generate() {
    $instance = $this->getPluginInstance();
    $instance->generate($this->getParameters());
  }

}
