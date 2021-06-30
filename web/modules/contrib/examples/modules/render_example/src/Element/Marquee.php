<?php

namespace Drupal\render_example\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a marquee render element.
 *
 * New render element types are defined as plugins. They live in the
 * Drupal\{module_name}\Element namespace and implement
 * \Drupal\Core\Render\Element\ElementInterface. They are annotated with either
 * \Drupal\Core\Render\Annotation\RenderElement or
 * \Drupal\Core\Render\Annotation\FormElement. And extend either the
 * \Drupal\Core\Render\Element\RenderElement, or
 * \Drupal\Core\Render\Element\FormElement base classes.
 *
 * In the annotation below we define the string "marquee" as the ID for this
 * plugin. That will also be the value used for the '#type' property in a render
 * array. For example:
 *
 * @code
 * $build['awesome'] = [
 *   '#type' => 'marquee',
 *   '#content' => 'Whoa cools, a marquee!',
 * ];
 * @endcode
 *
 * View an example of this custom element in use in
 * \Drupal\render_example\Controller\RenderExampleController::arrays().
 *
 * @see plugin_api
 * @see render_example_theme()
 *
 * @RenderElement("marquee")
 */
class Marquee extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {

    // Returns an array of default properties that will be merged with any
    // properties defined in a render array when using this element type.
    // You can use any standard render array property here, and you can also
    // custom properties that are specific to your new element type.
    return [
      // See render_example_theme() where this new theme hook is declared.
      '#theme' => 'render_example_marquee',
      // Define a default #pre_render method. We will use this to handle
      // additional processing for the custom attributes we add below.
      '#pre_render' => [
        [self::class, 'preRenderMarquee'],
      ],
      // This is a custom property for our element type. We set it to blank by
      // default. The expectation is that a user will add the content that they
      // would like to see inside the marquee tag. This custom property is
      // accounted for in the associated template file.
      '#content' => '',
      '#attributes' => [
        'direction' => 'left',
        'loop' => -1,
        'scrollamount' => 'random',
      ],
    ];
  }

  /**
   * Pre-render callback; Process custom attribute options.
   *
   * @param array $element
   *   The renderable array representing the element with '#type' => 'marquee'
   *   property set.
   *
   * @return array
   *   The passed in element with changes made to attributes depending on
   *   context.
   */
  public static function preRenderMarquee(array $element) {
    // Normal attributes for a <marquee> tag do not include a 'random' option
    // for scroll amount. Our marquee element type does though. So we use this
    // #pre_render callback to check if the element was defined with the value
    // 'random' for the scrollamount attribute, and if so replace the string
    // with a random number.
    if ($element['#attributes']['scrollamount'] == 'random') {
      $element['#attributes']['scrollamount'] = abs(rand(1, 50));
    }
    return $element;
  }

}
