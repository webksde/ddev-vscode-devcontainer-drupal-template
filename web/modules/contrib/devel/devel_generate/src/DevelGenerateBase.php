<?php

namespace Drupal\devel_generate;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base DevelGenerate plugin implementation.
 */
abstract class DevelGenerateBase extends PluginBase implements DevelGenerateBaseInterface {

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The random data generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $random;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    // Merge defaults if we have no value for the key.
    if (!array_key_exists($key, $this->settings)) {
      $this->settings = $this->getDefaultSettings();
    }
    return isset($this->settings[$key]) ? $this->settings[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    $definition = $this->getPluginDefinition();
    return $definition['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $values) {
    $this->generateElements($values);
    $this->setMessage('Generate process complete.');
  }

  /**
   * Business logic relating with each DevelGenerate plugin.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function generateElements(array $values) {

  }

  /**
   * Populate the fields on a given entity with sample values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be enriched with sample field values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function populateFields(EntityInterface $entity) {
    $properties = [
      'entity_type' => $entity->getEntityType()->id(),
      'bundle' => $entity->bundle(),
    ];
    $field_config_storage = \Drupal::entityTypeManager()->getStorage('field_config');
    /* @var \Drupal\field\FieldConfigInterface[] $instances */
    $instances = $field_config_storage->loadByProperties($properties);

    // @todo not implemented for Drush9+. Possibly remove.
    if ($skips = @$_REQUEST['skip-fields']) {
      foreach (explode(',', $skips) as $skip) {
        unset($instances[$skip]);
      }
    }

    foreach ($instances as $instance) {
      $field_storage = $instance->getFieldStorageDefinition();
      $max = $cardinality = $field_storage->getCardinality();
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // Just an arbitrary number for 'unlimited'.
        $max = rand(1, 3);
      }
      $field_name = $field_storage->getName();
      $entity->$field_name->generateSampleItems($max);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleDrushParams($args) {

  }

  /**
   * Set a message for either drush or the web interface.
   *
   * @param string $msg
   *   The message to display.
   * @param string $type
   *   (optional) The message type, as defined in MessengerInterface. Defaults
   *   to MessengerInterface::TYPE_STATUS.
   */
  protected function setMessage($msg, $type = MessengerInterface::TYPE_STATUS) {
    if (function_exists('drush_log')) {
      $msg = strip_tags($msg);
      drush_log($msg);
    }
    else {
      \Drupal::messenger()->addMessage($msg, $type);
    }
  }

  /**
   * Check if a given param is a number.
   *
   * @param mixed $number
   *   The parameter to check.
   *
   * @return bool
   *   TRUE if the parameter is a number, FALSE otherwise.
   */
  public static function isNumber($number) {
    if ($number == NULL) {
      return FALSE;
    }
    if (!is_numeric($number)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function getEntityTypeManager() {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

  /**
   * Returns the random data generator.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random data generator.
   */
  protected function getRandom() {
    if (!$this->random) {
      $this->random = new Random();
    }
    return $this->random;
  }

  /**
   * Generates a random sentence of specific length.
   *
   * Words are randomly selected with length from 2 up to the optional parameter
   * $max_word_length. The first word is capitalised. No ending period is added.
   *
   * @param int $sentence_length
   *   The total length of the sentence, including the word-separating spaces.
   * @param int $max_word_length
   *   (optional) Maximum length of each word. Defaults to 8.
   *
   * @return string
   *   A sentence of the required length.
   */
  protected function randomSentenceOfLength($sentence_length, $max_word_length = 8) {
    // Maximum word length cannot be longer than the sentence length.
    $max_word_length = min($sentence_length, $max_word_length);
    $words = [];
    $remainder = $sentence_length;
    do {
      if ($remainder <= $max_word_length) {
        // If near enough to the end then generate the exact length word to fit.
        $next_word = $remainder;
      }
      else {
        // Cannot fill the remaining space with one word, so choose a random
        // length, short enough for a following word of at least minimum length.
        $next_word = mt_rand(2, min($max_word_length, $remainder - 3));
      }
      $words[] = $this->getRandom()->word($next_word);
      $remainder = $remainder - $next_word - 1;
    } while ($remainder > 0);
    $sentence = ucfirst(implode(' ', $words));
    return $sentence;
  }

  /**
   * Creates the language and translation section of the form.
   *
   * This is used by both Content and Term generation.
   *
   * @param string $items
   *   The name of the things that are being generated - 'nodes' or 'terms'.
   *
   * @return array
   *   The language details section of the form.
   */
  protected function getLanguageForm($items) {
    // We always need a language, even if the language module is not installed.
    $options = [];
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $language_module_exists = $this->moduleHandler->moduleExists('language');
    $translation_module_exists = $this->moduleHandler->moduleExists('content_translation');

    $form['language'] = [
      '#type' => 'details',
      '#title' => $this->t('Language'),
      '#open' => $language_module_exists,
    ];
    $form['language']['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the primary language(s) for @items', ['@items' => $items]),
      '#multiple' => TRUE,
      '#description' => $language_module_exists ? '' : $this->t('Disabled - requires Language module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
      '#disabled' => !$language_module_exists,
    ];
    $form['language']['translate_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the language(s) for translated @items', ['@items' => $items]),
      '#multiple' => TRUE,
      '#description' => $translation_module_exists ? $this->t('Translated @items will be created for each language selected.', ['@items' => $items]) : $this->t('Disabled - requires Content Translation module.'),
      '#options' => $options,
      '#disabled' => !$translation_module_exists,
    ];
    return $form;
  }

  /**
   * Return a language code.
   *
   * @param array $add_language
   *   Optional array of language codes from which to select one at random.
   *   If empty then return the site's default language.
   *
   * @return string
   *   The language code to use.
   */
  protected function getLangcode(array $add_language) {
    if (empty($add_language)) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }
    return $add_language[array_rand($add_language)];
  }

}
