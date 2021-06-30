<?php

/**
 * @file
 */

namespace Drupal\theming_example\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class ThemingPageController {

  use StringTranslationTrait;

  /**
   * Initial landing page explaining the use of the module.
   *
   * We create a render array and specify the theme to be used through the use
   * of #theme_wrappers. With all output, we aim to leave the content as a
   * render array just as long as possible, so that other modules (or the theme)
   * can alter it.
   *
   * @see render_example.module
   * @see form_example_elements.inc
   */
  public function entryPage() {
    $links = [];
    $links[] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('theming_example.list'),
      '#title' => t('Simple page with a list'),
    ];
    $links[] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('theming_example.form_select'),
      '#title' => t('Simple form 1'),
    ];
    $links[] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('theming_example.form_text'),
      '#title' => t('Simple form 2'),
    ];
    $content = [
      '#theme' => 'item_list',
      '#theme_wrappers' => ['theming_example_content_array'],
      '#items' => $links,
      '#title' => t('Some examples of pages and forms that are run through theme functions.'),
    ];

    return $content;
  }

  /**
   * The list page callback.
   *
   * An example page where the output is supplied as an array which is themed
   * into a list and styled with css.
   *
   * In this case we'll use the core-provided theme_item_list as a #theme_wrapper.
   * Any theme need only override theme_item_list to change the behavior.
   */
  public function list() {
    $items = [
      $this->t('First item'),
      $this->t('Second item'),
      $this->t('Third item'),
      $this->t('Fourth item'),
    ];

    // First we'll create a render array that simply uses theme_item_list.
    $title = $this->t("A list returned to be rendered using theme('item_list')");
    $build['render_version'] = [
      // We use #theme here instead of #theme_wrappers because theme_item_list()
      // is the classic type of theme function that does not just assume a
      // render array, but instead has its own properties (#type, #title, #items).
      '#theme' => 'item_list',
      // '#type' => 'ul',  // The default type is 'ul'
      // We can easily make sure that a css or js file is present using #attached.
      '#attached' => ['library' => ['theming_example/list']],
      '#title' => $title,
      '#items' => $items,
      '#attributes' => ['class' => ['render-version-list']],
    ];

    // Now we'll create a render array which uses our own list formatter,
    // theme('theming_example_list').
    $title = $this->t("The same list rendered by theme('theming_example_list')");
    $build['our_theme_function'] = array(
      '#theme' => 'theming_example_list',
      '#attached' => ['library' => ['theming_example/list']],
      '#title' => $title,
      '#items' => $items,
    );
    return $build;
  }

}
