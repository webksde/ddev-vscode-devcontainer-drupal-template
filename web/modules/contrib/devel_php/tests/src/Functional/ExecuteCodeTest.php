<?php

declare(strict_types = 1);

namespace Drupal\Tests\devel_php\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests execute code.
 *
 * @group devel_php
 */
class ExecuteCodeTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'devel',
    'devel_php',
  ];

  /**
   * Tests handle errors.
   */
  public function testHandleErrors() {
    $url = Url::fromRoute('devel_php.execute_php');

    $user = $this->drupalCreateUser(['execute php code']);
    $this->drupalLogin($user);
    $this->drupalGet($url);

    $edit['code'] = 'devel_help()';
    $this->submitForm($edit, $this->t('Execute'));
    $this->assertSession()->pageTextContains('syntax error, unexpected end of file');

    $edit['code'] = 'devel_help2();';
    $this->submitForm($edit, $this->t('Execute'));
    $this->assertSession()->pageTextContains('Call to undefined function devel_help2()');

    $edit['code'] = 'devel_help();';
    $this->submitForm($edit, $this->t('Execute'));
    $this->assertSession()->pageTextContains('Too few arguments to function devel_help(), 0 passed');
  }

  /**
   * Tests output buffer.
   */
  public function testOutputBuffer() {
    $url = Url::fromRoute('devel_php.execute_php');

    $user = $this->drupalCreateUser([
      'access devel information',
      'execute php code',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet($url);

    $edit['code'] = 'echo \Drupal::VERSION;';
    $this->submitForm($edit, $this->t('Execute'));
    $this->assertSession()->pageTextContains(\Drupal::VERSION);
  }

}
