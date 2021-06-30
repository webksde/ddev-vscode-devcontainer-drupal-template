<?php

namespace Drupal\render_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\examples\Utility\DescriptionTemplateTrait;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Variable;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides module description page and examples of building render arrays.
 *
 * Controllers that respond to a route should always return their content as
 * a renderable array. See the arrays() method below as an example.
 *
 * @ingroup render_example
 */
class RenderExampleController extends ControllerBase implements TrustedCallbackInterface {

  use DescriptionTemplateTrait;

  /**
   * Constructs a new BlockController instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'render_example';
  }

  /**
   * Examples of defining content using renderable arrays.
   *
   * Methods on a controller that are the target of a route should return a
   * renderable array which contains any content to display for that route.
   */
  public function arrays() {
    // The core structure of the Render API is the render array, which is a
    // hierarchical associative array containing data to be rendered and
    // properties describing how the data should be rendered. Whenever a module
    // needs to output content it should do so be defining that content as a
    // renderable array. Below we'll look at some common examples of how render
    // arrays can be used to define content.
    $build = [];

    // CSS and JavaScript libraries can be attached to elements in a renderable
    // array. This way, if the element ends up being rendered and displayed you
    // know for sure the CSS/JavaScript will also be included. But, if for
    // some reason the element isn't ever rendered then Drupal can skip the
    // unnecessary extra files.
    //
    // Learn more about attaching CSS and JavaScript libraries with the
    // #attached property here:
    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21theme.api.php/group/theme_render/#sec_attached
    $build['#attached'] = [
      'library' => [
        'render_example/render-example.library',
      ],
    ];

    // Renderable arrays have two kinds of key/value pairs: properties and
    // children. Properties have keys starting with '#' and their values
    // influence how the array will be translated to a string. Children are all
    // elements whose keys do not start with a '#'. Their values should be
    // renderable arrays themselves.
    //
    // This example defines a new element, 'simple', that contains two
    // properties; '#markup' and '#description'. This is the quickest way to
    // output a string of HTML.
    $build['simple'] = [
      '#markup' => '<p>' . $this->t('This page contains examples of various content elements described using render arrays. Read the code and comments in \Drupal\render_example\Controller\RenderExampleController::arrays() for more information.') . '</p>',
      '#description' => $this->t('Example of using #markup'),
    ];

    // Additional properties can be used to further define the content. In this
    // case '#prefix' and '#suffix' are being used to provide strings to add
    // before, and after, the main content. This is useful because now the tag
    // being used to wrap the block of content can be easily changed without
    // having to worry about the content. Or, the tag can easily be left out
    // during rendering if for example the content is being output as JSON.
    //
    // There is a set of common properties that can be used for all elements in
    // a render array. These are defined by
    // \Drupal\Core\Render\Element\RenderElement. Most elements also have
    // additional element type specific properties.
    //
    // Figuring out what additional properties are available requires first
    // determining what sort of render element you're dealing with. Look for the
    // presence of one of these properties to start:
    // - #markup, or #plain_text: These are the simplest render arrays, and are
    //   used to display simple strings of text. In addition to the common set
    //   of properties available for all elements #markup elements can use the
    //   #allowed_tags property, an array of additional tags to allow when the
    //   HTML string is run through \Drupal\Component\Utility\Xss::filterAdmin()
    //   to strip out possible XSS vectors.
    // - #theme: The presence of #theme indicates that the array contains data
    //   to be themed by a particular theme hook. The available properties will
    //   depend on the specific theme hook. See the example below for more about
    //   determining what properties to use.
    // - #type: The presence of #type indicates that the array contains data and
    //   options for a particular type of "render element" (for example, 'form',
    //   'textfield', 'submit', for HTML form element types; 'table', for a
    //   table with rows, columns, and headers). The additional properties will
    //   depend on the render element type, and are documented on the class that
    //   defines the element type.
    //
    $build['simple_extras'] = [
      '#description' => $this->t('Example of using #prefix and #suffix'),
      // Note the addition of '#type' => 'markup' in this example compared to
      // the one above. Because #markup is such a commonly used element type you
      // can exclude the '#type' => 'markup' line and it will be assumed
      // automatically if the '#markup' property is present.
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This one adds a prefix and suffix, which put a blockqoute tag around the item.') . '</p>',
      '#prefix' => '<blockquote>',
      '#suffix' => '</blockquote>',
    ];

    // In addition to #markup, you can also use #plain_text to output, you
    // guessed it, strings of plain text. This indicates that the array contains
    // text which should be escaped before it is displayed.
    $build['simple_text'] = [
      '#plain_text' => '<em>This is escaped</em>',
      '#description' => $this->t('Example of using #plain_text'),
    ];

    // Using the '#theme' property for an element specifies that the array
    // contains data to be themed by a particular theme hook. Essentially using
    // a Twig template to generate the HTML for an element. Modules define theme
    // hooks by implementing hook_theme(), which specifies the input "variables"
    // used to provide data and options; if a hook_theme() implementation
    // specifies variable 'separator', then in a render array, you would provide
    // this data using the '#separator' property.
    //
    // @see hook_theme()
    $build['theme_element'] = [
      // The '#theme' property can be set to any valid theme hook. For more
      // information about theme hooks, and to discover available theme hooks
      // that you can use when creating render arrays see the documentation for
      // hook_theme().
      //
      // Many of the most commonly used theme hooks are defined in
      // drupal_common_theme().
      '#theme' => 'item_list',
      '#title' => $this->t('Example of using #theme'),
      // The #items property is specific to the 'item_list' theme hook, and
      // corresponds to the variable {{ items }} in the item-list.twig.html
      // template file.
      '#items' => [
        $this->t('This is an item in the list'),
        $this->t('This is some more text that we need in the list'),
      ],
    ];

    // Using the '#type' property for an element specifies that the array
    // contains data and options for a particular type of "render element".
    // Render element types can be thought of as prepackaged render arrays that
    // provide default values for a set of properties as well as code that
    // will perform additional processing on the array before it is rendered.
    //
    // As an example take a look at the code in
    // \Drupal\Core\Render\Element\Table::getInfo(). Notice that it is defining
    // values for #theme, and #process? These values will be merged with
    // whatever properties you define in your code.
    //
    // In addition, most render element types have type specific properties. A
    // table for example has #header, and #rows properties. The easiest way to
    // determine what element type specific properties exist is to read the
    // documentation for the class that defines the element type. Don't forget
    // that it will also inherit properties used by any class it is extending.
    //
    // There are two types of render element types:
    // - Generic elements: Generic render element types encapsulate logic for
    //   generating HTML and attaching relevant CSS and JavaScript to the page.
    //   These include things like link, table, and drop button elements.
    // - Form elements: Most of the render element types provided by core
    //   represent the various widgets you might use on a form. Text fields,
    //   password fields, and file upload buttons for example. These elements
    //   are intended to be used in conjunction with a form controller class
    //   and have additional properties such as `#required`, and
    //   `#element_validate`, related to their use as part of a form. For more
    //   on form elements check out the fapi_example module.
    $build['table'] = [
      // The value used for #type is the ID of the plugin that implements the
      // element type you want to use. This can be inferred from the annotation
      // for the element.
      // You can also find a list of element types provided by Drupal core here
      // https://api.drupal.org/api/drupal/elements.
      '#type' => 'table',
      '#caption' => $this->t('Our favorite colors.'),
      '#header' => [$this->t('Name'), $this->t('Favorite color')],
      '#rows' => [
        [$this->t('Amber'), $this->t('teal')],
        [$this->t('Addi'), $this->t('green')],
        [$this->t('Blake'), $this->t('#063')],
        [$this->t('Enid'), $this->t('indigo')],
        [$this->t('Joe'), $this->t('green')],
      ],
      '#description' => $this->t('Example of using #type.'),
    ];

    // Render arrays can be nested any level deep. This allows you to group
    // like things together. A great example of this is the $page array used in
    // conjunction with the page.html.twig template. The top level contains all
    // the regions, each of which contain the blocks placed in that region,
    // which in turn contain their own content. In fact, when this array is
    // ultimately displayed on a page it will be as part of the $page array.
    $build['nested_example'] = [
      '#description' => $this->t('Example of nesting elements'),
      '#markup' => '<p>' . $this->t('Render arrays can contain any number of nested elements. During rendering, the innermost elements are rendered first, and their output is incorporated into the parent element.') . '</p>',
      'nested_child_element' => [
        // An un-ordered list of links.
        // See /core/modules/system/templates/item-list.html.twig.
        '#theme' => 'item_list',
        '#title' => $this->t('Links'),
        '#list_type' => 'ol',
        '#items' => [
          Link::fromTextAndUrl($this->t('Drupal'), Url::fromUri('https://www.drupal.org')),
          Link::fromTextAndUrl($this->t('Not Drupal'), Url::fromUri('https://wordpress.org/')),
        ],
      ],
    ];

    // Example of adding a link using the #link element type.
    $build['nested_example']['another_nested_child'] = [
      // See \Drupal\Core\Render\Element\Link.
      '#type' => 'link',
      '#title' => $this->t('A link to example.com'),
      '#url' => Url::fromUri('https://example.com'),
    ];

    // The #theme_wrappers property can be used to provide an array of theme
    // hooks which provide the envelope or "wrapper" of a set of child elements.
    // The theme function finds its element children (the sub-arrays) already
    // rendered in '#children'.
    $build['theme_wrappers demonstration'] = [
      '#description' => $this->t('Example of using #theme_wrappers'),
      'child1' => ['#markup' => $this->t('Markup for child1')],
      'child2' => ['#markup' => $this->t('Markup for child2')],
      '#theme_wrappers' => [
        'render_example_add_div',
      ],
    ];

    // Use the #access property to control who can see what content. If an
    // element in an render array has its #access property set to FALSE it will
    // be removed from the array before rendering. And thus not visible.
    $build['access_example'] = [
      '#description' => $this->t('Example of using #access to control visibility'),
      '#markup' => $this->t('This text is only visible to authenticated users.'),
      '#access' => $this->currentUser->isAuthenticated(),
    ];

    // Some properties define callbacks, which are callable functions or methods
    // that are triggered at specific points during the rendering pipeline.
    $build['pre_render_and_post_render'] = [
      '#description' => $this->t('Example of using #pre_render and #post_render'),
      '#markup' => '<div style="color:green">' . $this->t('markup for pre_render and post_render example') . '</div>',
      // #pre_render callbacks are triggered early in the rendering process,
      // they get access to the element in the array where the callback is
      // named, and all of its children. They can be used to do things like
      // conditionally alter the value of a property prior to the array being
      // rendered to HTML.
      '#pre_render' => [static::class . '::preRenderAddSuffix'],
      // #post_render callbacks are triggered after the array has been rendered
      // and can operate on the rendered HTML. They also have access to the
      // original array for context.
      '#post_render' => [static::class . '::postRenderAddPrefix'],
    ];

    // Properties that contain callbacks can also reference methods on a class
    // in addition to functions. See
    // \Drupal\render_example\Controller\RenderExampleController::preRender()
    // @todo: This doesn't work, we need to fix it.
    // https://www.drupal.org/project/examples/issues/2986435
    // $build['#pre_render'] = [static::class, 'preRender'];.
    // Caching is an important part of the Render API, converting an array to a
    // string of HTML can be an expensive process, and therefore whenever
    // possible the Render API will cache the results of rendering an array in
    // order to improve performance.
    //
    // When defining a render array you should use the #cache property to define
    // the cachability of an element.
    $build['cache_demonstration'] = [
      '#description' => $this->t('#cache demonstration'),
      // This string contains information that is specific to the user who is
      // currently viewing the page. We can cache it, and re-use the string any
      // time the same user views the page again. However, if the user changes,
      // or if the user changes their name, we need to expire the cached data
      // and rebuild it so that it is accurate.
      '#markup' => $this->t('Hello @name, welcome to the #cache example.', ['@name' => $this->currentUser->getAccountName()]),
      // The #cache property is used to provide metadata about the element being
      // cached, and the conditions under which it should be expired. This can
      // be time based, or context based. You can read more about caching
      // render arrays here
      // https://www.drupal.org/docs/8/api/render-api/cacheability-of-render-arrays
      '#cache' => [
        // The "current user" is used above, which depends on the request, so
        // we tell Drupal to vary by the 'user' cache context.
        'contexts' => [
          'user',
        ],
      ],
    ];

    // A #lazy_builder callback can be used to build a highly dynamic section of
    // a render array from scratch. This, combined with the use of placeholders,
    // allows the renderer to cache some, but not all, portions of a render
    // array. Without #lazy_builders, if any element in the render tree is
    // uncacheable the whole tree would need to be re-rendered every time.
    //
    // The general rendering flow is as follows:
    // - Check for cached version of output from previous rendering, if it
    //   exists replace any placeholders in the rendered output with their
    //   dynamic content as generated by the #lazy_builder callback, and return
    //   the resulting HTML.
    // - If no cached version exists render the array to HTML, when an element
    //   that can be placeholdered is encountered insert a placeholder, cache
    //   the HTML after rendering for next time, replace the placeholders with
    //   their dynamic content, and return the resulting HTML.
    //
    // This is especially noticeable when used in conjunction with modules like
    // Big Pipe which do rendering of a page in multiple passes vs. the default
    // single flush renderer.
    //
    // See \Drupal\block\BlockViewBuilder::viewMultiple() for an example from
    // core.
    $build['lazy_builder'] = [
      // Set the value of the #lazy_builder property to an array, the first key
      // of the array is the method, service, or function, to call in oder to
      // generate the dynamic data. The second argument is an array of any
      // arguments to pass to the callback. Arguments can be only primitive
      // types (string, bool, int, float, NULL).
      '#lazy_builder' => [
        static::class . '::lazyBuilder',
        [$this->currentUser->id(), 'Y-m-d'],
      ],
      // #lazy_builder callbacks can be used in conjunction with
      // #create_placeholder to tell the renderer that instead of simply calling
      // the #lazy_builder code right away, to instead insert a placeholder and
      // delay execution of the #lazy_builder code until it's needed.
      //
      // This is somewhat analogous to the way Drupal uses the PSR-4 autoloading
      // standard to "lazy" load PHP files that contain the definition of a
      // class only if, and when, that class is used.
      //
      // To force a element to use a placeholder set #create_placeholder to
      // TRUE.
      //
      // Alternatively you could include #cache metadata (see above) and allow
      // the Render API to use that metadata to automatically determine based on
      // the existence of high-cardinality cache contexts in the subtree whether
      // or not the element should use a placeholder.
      '#create_placeholder' => TRUE,
    ];

    // Example of the marquee element type defined by
    // \Drupal\render_example\Element\Marquee.
    $build['marquee'] = [
      '#description' => $this->t('Example custom element type'),
      '#type' => 'markup',
      'marquee_element' => [
        '#type' => 'marquee',
        '#content' => $this->t('Hello world!'),
      ],
    ];

    $output = [];
    // We are going to create a new output render array that pairs each
    // example with a set of helper render arrays. These are used to display
    // the description as a title and the unrendered content alongside the
    // examples.
    foreach (Element::children($build) as $key) {
      if (isset($build[$key])) {
        $output[$key] = [
          '#theme' => 'render_array',
          'description' => [
            '#type' => 'markup',
            '#markup' => $build[$key]['#description'] ?? '',
          ],
          'rendered' => $build[$key],
          'unrendered' => [
            '#type' => 'markup',
            '#markup' => htmlentities(Variable::export($build[$key])),
          ],
        ];
      }
    }

    foreach (Element::properties($build) as $key) {
      $output[$key] = $build[$key];
    }

    return $output;
  }

  /**
   * A #pre_render callback, expand array to include additional example info.
   *
   * This method is called during the process of rendering the array generated
   * by \Drupal\render_example\Controller\RenderExampleController::arrays().
   *
   * This also demonstrates how a #pre_render callback could be used to expand
   * a relatively simple array into multiple individual renderable elements
   * based on application logic.
   *
   * @param array $element
   *   Pre render methods (and functions) get a single argument that is the
   *   render API array representing the element where the #pre_render property
   *   was defined, and all of it's children.
   *
   * @return array
   *   Pre render methods (and functions) should return the modified render
   *   array.
   */
  public static function preRender(array $element) {
    // For each first level child element lets add some additional helpful
    // output. \Drupal\Core\Render\Element::children() is a utility method that
    // allows you to quickly identify all children of a render array. That is
    // those key/value pairs whose key does not start with a '#'.
    foreach (Element::children($element) as $key) {
      $child = $element[$key];
      unset($element[$key]);
      if (isset($child['#description'])) {
        $element[$key] = [
          // The value from the #description property will be used as a title
          // for this element in the final output.
          'description' => [
            '#markup' => $child['#description'],
          ],
          // Move the original element to 'rendered'. The rendering process is
          // recursive so this will still be located, and rendered to HTML.
          'rendered' => $child,
          // Export the element definition as a string of text so we can display
          // the array that was used to create the rendered output just below
          // the output.
          'unrendered' => [
            '#markup' => htmlentities(Variable::export($child)),
          ],
          '#theme' => 'render_array',
        ];
      }
    }

    // Return our modified version of the original $element.
    return $element;
  }

  /**
   * Example #lazy_builder callback.
   *
   * Demonstrates the use of a #lazy_builder callback to build out a render
   * array that can be substituted into the parent array wherever the cacheable
   * placeholder exists.
   *
   * This method is called during the process of rendering the array generated
   * by \Drupal\render_example\Controller\RenderExampleController::arrays().
   *
   * @param string $date_format
   *   Date format to use with \Drupal\Core\Datetime\DateFormatter::format().
   *
   * @return array
   *   A renderable array with content to replace the #lazy_builder placeholder.
   */
  public static function lazyBuilder($date_format) {
    $build = [
      'lazy_builder_time' => [
        '#markup' => '<p>' . \Drupal::translation()->translate('The current time is @time', [
          '@time' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'long'),
        ]) . '</p>',
      ],
    ];

    return $build;
  }

  /**
   * Example '#post_render' callback function.
   *
   * Post render callbacks are triggered after an element has been rendered to
   * HTML and can act upon the final rendered string.
   *
   * This function is used as a post render callback in
   * Drupal\render_example\Controller\RenderExampleController::arrays().
   *
   * @param string $markup
   *   The rendered element.
   * @param array $element
   *   The element which was rendered (for reference)
   *
   * @return string
   *   Markup altered as necessary. In this case we add a little postscript.
   *
   * @see \Drupal\render_example\Controller\RenderExampleController::arrays()
   */
  public static function postRenderAddPrefix($markup, array $element) {
    $markup .= '<div style="color:blue">This markup was added after rendering by a #post_render callback.</div>';
    return $markup;
  }

  /**
   * Example '#pre_render' function.
   *
   * Pre render callbacks are triggered prior to rendering an element to HTML
   * and are given the chance to manipulate the renderable array. Any changes
   * they make will be reflected in the final rendered HTML.
   *
   * We need to wrap suffix in a Markup object.
   * Otherwise, style attribute will be removed by Xss
   * @see \Drupal\Component\Utility\Xss::filter()
   *
   * This function is used as a post render callback in
   * \Drupal\render_example\Controller\RenderExampleController::arrays().
   *
   * @param array $element
   *   The element which will be rendered.
   *
   * @return array
   *   The altered element. In this case we add a #prefix to it.
   *
   * @see \Drupal\render_example\Controller\RenderExampleController::arrays()
   */
  public static function preRenderAddSuffix(array $element) {
    $element['#suffix'] = Markup::create('<div style="color:red">'
      . t('This #suffix was added by a #pre_render callback.') . '</div>');
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return ['postRenderAddPrefix', 'preRenderAddSuffix', 'lazyBuilder'];
  }

}
