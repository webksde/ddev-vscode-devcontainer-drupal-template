<?php

namespace Drupal\devel\Twig\Extension;

use Drupal\devel\DevelDumperManagerInterface;

/**
 * Provides the Devel debugging function within Twig templates.
 *
 * NOTE: This extension doesn't do anything unless twig_debug is enabled.
 * The twig_debug setting is read from the Twig environment, not Drupal
 * Settings, so a container rebuild is necessary when toggling twig_debug on
 * and off.
 */
class Debug extends \Twig_Extension {

  /**
   * The devel dumper service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * Constructs a Debug object.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The devel dumper service.
   */
  public function __construct(DevelDumperManagerInterface $dumper) {
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'devel_debug';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $options = [
      'is_safe' => ['html'],
      'needs_environment' => TRUE,
      'needs_context' => TRUE,
      'is_variadic' => TRUE,
    ];

    return [
      new \Twig_SimpleFunction('devel_dump', [$this, 'dump'], $options),
      new \Twig_SimpleFunction('kpr', [$this, 'dump'], $options),
      //  Preserve familiar kint() function for dumping
      new \Twig_SimpleFunction('kint', [$this, 'dump'], $options),
      new \Twig_SimpleFunction('devel_message', [$this, 'message'], $options),
      new \Twig_SimpleFunction('dpm', [$this, 'message'], $options),
      new \Twig_SimpleFunction('dsm', [$this, 'message'], $options),
      new \Twig_SimpleFunction('devel_breakpoint', [$this, 'breakpoint'], [
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
        'is_variadic' => TRUE,
      ]),
    ];
  }

  /**
   * Provides debug function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @return string
   *   String representation of the input variables.
   *
   * @see \Drupal\devel\DevelDumperManager::dump()
   */
  public function dump(\Twig_Environment $env, array $context, array $args = []) {
    if (!$env->isDebug()) {
      return NULL;
    }

    ob_start();

    // No arguments passed, display full Twig context.
    if (empty($args)) {
      $context_variables = $this->getContextVariables($context);
      $this->dumper->dump($context_variables, 'Twig context');
    }
    else {
      $parameters = $this->guessTwigFunctionParameters();

      foreach ($args as $index => $variable) {
        $name = !empty($parameters[$index]) ? $parameters[$index] : NULL;
        $this->dumper->dump($variable, $name);
      }
    }

    return ob_get_clean();
  }

  /**
   * Provides debug function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @see \Drupal\devel\DevelDumperManager::message()
   */
  public function message(\Twig_Environment $env, array $context, array $args = []) {
    if (!$env->isDebug()) {
      return;
    }

    // No arguments passed, display full Twig context.
    if (empty($args)) {
      $context_variables = $this->getContextVariables($context);
      $this->dumper->message($context_variables, 'Twig context');
    }
    else {
      $parameters = $this->guessTwigFunctionParameters();

      foreach ($args as $index => $variable) {
        $name = !empty($parameters[$index]) ? $parameters[$index] : NULL;
        $this->dumper->message($variable, $name);
      }
    }

  }

  /**
   * Provides XDebug integration for Twig templates.
   *
   * To use this features simply put the following statement in the template
   * of interest:
   *
   * @code
   * {{ devel_breakpoint() }}
   * @endcode
   *
   * When the template is evaluated is made a call to a dedicated method in
   * devel twig debug extension in which is used xdebug_break(), that emits a
   * breakpoint to the debug client (the debugger break on the specific line as
   * if a normal file/line breakpoint was set on this line).
   * In this way you'll be able to inspect any variables available in the
   * template (environment, context, specific variables etc..) in your IDE.
   *
   * @param \Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   */
  public function breakpoint(\Twig_Environment $env, array $context, array $args = []) {
    if (!$env->isDebug()) {
      return;
    }

    if (function_exists('xdebug_break')) {
      xdebug_break();
    }
  }

  /**
   * Filters the Twig context variable.
   *
   * @param array $context
   *   The Twig context.
   *
   * @return array
   *   An array Twig context variables.
   */
  protected function getContextVariables(array $context) {
    $context_variables = [];
    foreach ($context as $key => $value) {
      if (!$value instanceof \Twig_Template) {
        $context_variables[$key] = $value;
      }
    }
    return $context_variables;
  }

  /**
   * Gets the twig function parameters for the current invocation.
   *
   * @return array
   *   The detected twig function parameters.
   */
  protected function guessTwigFunctionParameters() {
    $callee = NULL;
    $template = NULL;

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);

    foreach ($backtrace as $index => $trace) {
      if (isset($trace['object']) && $trace['object'] instanceof \Twig_Template && 'Twig_Template' !== get_class($trace['object'])) {
        $template = $trace['object'];
        $callee = $backtrace[$index - 1];
        break;
      }
    }

    $parameters = [];

    /** @var \Twig_Template $template */
    if (NULL !== $template && NULL !== $callee) {
      $line_number = $callee['line'];
      $debug_infos = $template->getDebugInfo();

      if (isset($debug_infos[$line_number])) {
        $source_line = $debug_infos[$line_number];
        $source_file_name = $template->getTemplateName();

        if (is_readable($source_file_name)) {
          $source = file($source_file_name, FILE_IGNORE_NEW_LINES);
          $line = $source[$source_line - 1];

          preg_match('/\((.+)\)/', $line, $matches);
          if (isset($matches[1])) {
            $parameters = array_map('trim', explode(',', $matches[1]));
          }
        }
      }
    }

    return $parameters;
  }

}
