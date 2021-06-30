<?php

namespace Drupal\testing_example\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A highly-contrived controller class used to demonstrate unit testing.
 */
class ContrivedController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation')
    );
  }

  /**
   * Construct a new controller.
   *
   * @param Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   */
  public function __construct(TranslationInterface $translation) {
    $this->setStringTranslation($translation);
  }

  /**
   * A controller method which displays a sum in terms of hands.
   *
   * @param int $first
   *   A parameter to the controller path.
   * @param int $second
   *   A parameter to the controller path.
   *
   * @return string[]
   *   A markup array.
   */
  public function displayAddedNumbers($first, $second) {
    return [
      '#markup' => '<p>' . $this->handCount($first, $second) . '</p>',
    ];
  }

  /**
   * Generate a message based on how many hands are needed to count the sum.
   *
   * @param int $first
   *   First parameter.
   * @param int $second
   *   Second parameter.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated message.
   */
  protected function handCount($first, $second) {
    $sum = abs($this->add((int) $first, (int) $second));
    if ($sum <= 5) {
      $message = $this->t('I can count these on one hand.');
    }
    elseif ($sum <= 10) {
      $message = $this->t('I need two hands to count these.');
    }
    else {
      $message = $this->t("That's just too many numbers to count.");
    }
    return $message;
  }

  /**
   * Add two numbers.
   *
   * @param int $first
   *   The first parameter.
   * @param int $second
   *   The second parameter.
   *
   * @return int
   *   The sum of the two parameters.
   */
  protected function add($first, $second) {
    return $first + $second;
  }

}
