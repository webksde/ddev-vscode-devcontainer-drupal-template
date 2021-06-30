<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drush\Utils\StringUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin that generates media entities.
 *
 * @DevelGenerate(
 *   id = "media",
 *   label = @Translation("media"),
 *   description = @Translation("Generate a given number of media entities."),
 *   url = "media",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "name_length" = 4,
 *   },
 *   dependencies = {
 *     "media",
 *   },
 * )
 */
class MediaDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The media entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * The media type entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * The user entity storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The system time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The Drush batch flag.
   *
   * @var bool
   */
  protected $drushBatch;

  /**
   * Constructs a new 'media' plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator, DateFormatterInterface $date_formatter, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mediaStorage = $entity_type_manager->getStorage('media');
    $this->mediaTypeStorage = $entity_type_manager->getStorage('media_type');
    $this->userStorage = $entity_type_manager->getStorage('user');;
    $this->languageManager = $language_manager;
    $this->urlGenerator = $url_generator;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('url_generator'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $types = $this->mediaTypeStorage->loadMultiple();

    if (empty($types)) {
      $create_url = $this->urlGenerator->generateFromRoute('entity.media_type.add_form');
      $this->setMessage($this->t('You do not have any media types that can be generated. <a href=":url">Go create a new media type</a>', [
        ':url' => $create_url,
      ]), MessengerInterface::TYPE_ERROR);
      return [];
    }

    $options = [];
    foreach ($types as $type) {
      $options[$type->id()] = ['type' => ['#markup' => $type->label()]];
    }

    $form['media_types'] = [
      '#type' => 'tableselect',
      '#header' => ['type' => $this->t('Media type')],
      '#options' => $options,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all media</strong> in these types before generating new media.'),
      '#default_value' => $this->getSetting('kill'),
    ];
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many media items would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }
    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How far back in time should the media be dated?'),
      '#description' => $this->t('Media creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    $form['name_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of words in names'),
      '#default_value' => $this->getSetting('name_length'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 255,
    ];

    $options = [];
    // We always need a language.
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $form['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Set language on media'),
      '#multiple' => TRUE,
      '#description' => $this->t('Requires locale.module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
    // Remove the media types not selected.
    $media_types = array_filter($form_state->getValue('media_types'));
    if (!$media_types) {
      $form_state->setErrorByName('media_types', $this->t('Please select at least one media type'));
    }
    // Store the normalized value back, in form state.
    $form_state->setValue('media_types', array_combine($media_types, $media_types));
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    if ($this->isBatch($values['num'])) {
      $this->generateBatchMedia($values);
    }
    else {
      $this->generateMedia($values);
    }
  }

  /**
   * Method for creating media when number of elements is less than 50.
   *
   * @param array $values
   *   Array of values submitted through a form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the bundle does not exist or was needed but not specified.
   */
  protected function generateMedia(array $values) {
    if (!empty($values['kill']) && $values['media_types']) {
      $this->mediaKill($values);
    }

    if (!empty($values['media_types'])) {
      // Generate media items.
      $this->preGenerate($values);
      $start = time();
      for ($i = 1; $i <= $values['num']; $i++) {
        $this->createMediaItem($values);
        if (isset($values['feedback']) && $i % $values['feedback'] == 0) {
          $now = time();
          $this->messenger()->addStatus(dt('Completed !feedback media items (!rate media/min)', [
            '!feedback' => $values['feedback'],
            '!rate' => ($values['feedback'] * 60) / ($now - $start),
          ]));
          $start = $now;
        }
      }
    }
    $this->setMessage($this->formatPlural($values['num'], '1 media item created.', 'Finished creating @count media items.'));
  }

  /**
   * Method for creating media when number of elements is greater than 50.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function generateBatchMedia(array $values) {
    $operations = [];

    // Setup the batch operations and save the variables.
    $operations[] = [
      'devel_generate_operation',
      [$this, 'batchPreGenerate', $values],
    ];

    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchMediaKill', $values],
      ];
    }

    // Add the operations to create the media.
    for ($num = 0; $num < $values['num']; $num++) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchCreateMediaItem', $values],
      ];
    }

    // Start the batch.
    $batch = [
      'title' => $this->t('Generating media items'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];
    batch_set($batch);

    if ($this->drushBatch) {
      drush_backend_batch_process();
    }
  }

  /**
   * Provides a batch version of preGenerate().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param iterable $context
   *   Batch job context.
   *
   * @see self::preGenerate()
   */
  public function batchPreGenerate(array $vars, iterable &$context) {
    $context['results'] = $vars;
    $context['results']['num'] = 0;
    $this->preGenerate($context['results']);
  }

  /**
   * Provides a batch version of createMediaItem().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the bundle does not exist or was needed but not specified.
   *
   * @see self::createMediaItem()
   */
  public function batchCreateMediaItem(array $vars, &$context) {
    if ($this->drushBatch) {
      $this->createMediaItem($vars);
    }
    else {
      $this->createMediaItem($context['results']);
    }
    $context['results']['num']++;
  }

  /**
   * Provides a batch version of mediaKill().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   *
   * @see self::mediaKill()
   */
  public function batchMediaKill($vars, &$context) {
    if ($this->drushBatch) {
      $this->mediaKill($vars);
    }
    else {
      $this->mediaKill($context['results']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []) {
    $add_language = $options['languages'];
    if (!empty($add_language)) {
      $add_language = explode(',', str_replace(' ', '', $add_language));
      // Intersect with the enabled languages to make sure the language args
      // passed are actually enabled.
      $values['values']['add_language'] = array_intersect($add_language, array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL)));
    }

    $values['kill'] = $options['kill'];
    $values['feedback'] = $options['feedback'];
    $values['name_length'] = 6;
    $values['num'] = array_shift($args);

    $all_media_types = array_values($this->mediaTypeStorage->getQuery()->execute());
    $requested_media_types = StringUtils::csvToArray($options['media-types'] ?: $all_media_types);

    if (empty($requested_media_types)) {
      throw new \Exception(dt('No media types available'));
    }
    // Check for any missing media type.
    if ($invalid_media_types = array_diff($requested_media_types, $all_media_types)) {
      throw new \Exception("Requested media types don't exists: " . implode(', ', $invalid_media_types));
    }

    $values['media_types'] = array_combine($requested_media_types, $requested_media_types);

    if ($this->isBatch($values['num'])) {
      $this->drushBatch = TRUE;
      $this->preGenerate($values);
    }

    return $values;
  }

  /**
   * Deletes all media of given media media types.
   *
   * @param array $values
   *   The input values from the settings form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the media type does not exist.
   */
  protected function mediaKill(array $values) {
    $mids = $this->mediaStorage->getQuery()
      ->condition('bundle', $values['media_types'], 'IN')
      ->execute();

    if (!empty($mids)) {
      $media = $this->mediaStorage->loadMultiple($mids);
      $this->mediaStorage->delete($media);
      $this->setMessage($this->t('Deleted %count media items.', ['%count' => count($mids)]));
    }
  }

  /**
   * Code to be run before generating items.
   *
   * Returns the same array passed in as parameter, but with an array of uids
   * for the key 'users'.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function preGenerate(array &$results) {
    // Get user id.
    $users = array_values($this->userStorage->getQuery()
      ->range(0, 50)
      ->execute());
    $users = array_merge($users, ['0']);
    $results['users'] = $users;
  }

  /**
   * Create one media item. Used by both batch and non-batch code branches.
   *
   * @param array $results
   *   The input values from the settings form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the bundle does not exist or was needed but not specified.
   */
  protected function createMediaItem(array &$results) {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }

    $media_type = array_rand($results['media_types']);
    $uid = $results['users'][array_rand($results['users'])];

    $media = $this->mediaStorage->create([
      'bundle' => $media_type,
      'name' => $this->getRandom()->sentences(mt_rand(1, $results['name_length']), TRUE),
      'uid' => $uid,
      'revision' => mt_rand(0, 1),
      'status' => TRUE,
      'created' => $this->time->getRequestTime() - mt_rand(0, $results['time_range']),
      'langcode' => $this->getLangcode($results),
    ]);

    // A flag to let hook implementations know that this is a generated item.
    $media->devel_generate = $results;

    // Populate all fields with sample values.
    $this->populateFields($media);

    $media->save();
  }

  /**
   * Determine language based on $results.
   *
   * @param array $results
   *   The input values from the settings form.
   *
   * @return string
   *   The language code.
   */
  protected function getLangcode(array $results) {
    if (isset($results['add_language'])) {
      $langcodes = $results['add_language'];
      $langcode = $langcodes[array_rand($langcodes)];
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    return $langcode;
  }

  /**
   * Finds out if the media item generation will run in batch process.
   *
   * @param int $media_items_count
   *   Number of media items to be generated.
   *
   * @return bool
   *   If the process should be a batch process.
   */
  protected function isBatch($media_items_count) {
    return $media_items_count >= 50;
  }

}
