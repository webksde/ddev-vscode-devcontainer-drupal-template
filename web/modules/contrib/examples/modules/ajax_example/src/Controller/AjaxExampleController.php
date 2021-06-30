<?php

namespace Drupal\ajax_example\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\examples\Utility\DescriptionTemplateTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for AJAX example routes.
 */
class AjaxExampleController extends ControllerBase {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ajax_example';
  }

  /**
   * Demonstrates a clickable AJAX-enabled link using the 'use-ajax' class.
   *
   * Because of the 'use-ajax' class applied here, the link submission is done
   * without a page refresh.
   *
   * When using the AJAX framework outside the context of a form or a renderable
   * array of type 'link', you have to include ajax.js explicitly.
   *
   * @return array
   *   Form API array.
   *
   * @ingroup ajax_example
   */
  public function renderLinkRenderableArray() {
    $build['my_div'] = [
      '#markup' => $this->t('
The link below has been rendered as an element with the #ajax property, so if
javascript is enabled, ajax.js will try to submit it via an AJAX call instead
of a normal page load. The URL also contains the "/nojs/" magic string, which
is stripped if javascript is enabled, allowing the server code to tell by the
URL whether JS was enabled or not, letting it do different things based on that.'),
    ];
    // We'll add a nice border element for our demo.
    $build['ajax_link'] = [
      '#type' => 'details',
      '#title' => $this->t('This is the AJAX link'),
      '#open' => TRUE,
    ];
    // We build the AJAX link.
    $build['ajax_link']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Click me'),
      // We have to ensure that Drupal's Ajax system is loaded.
      '#attached' => ['library' => ['core/drupal.ajax']],
      // We add the 'use-ajax' class so that Drupal's AJAX system can spring
      // into action.
      '#attributes' => ['class' => ['use-ajax']],
      // The URL for this link element is the callback. In our case, it's route
      // ajax_example.ajax_link_callback, which maps to ajaxLinkCallback()
      // below. The route has a /{nojs} section, which is how the callback can
      // know whether the request was made by AJAX or some other means where
      // JavaScript won't be able to handle the result. If the {nojs} part of
      // the path is replaced with 'ajax', then the request was made by AJAX.
      '#url' => Url::fromRoute('ajax_example.ajax_link_callback', ['nojs' => 'nojs']),
    ];
    // We provide a DIV that AJAX can append some text into.
    $build['ajax_link']['destination'] = [
      '#type' => 'container',
      '#attributes' => ['id' => ['ajax-example-destination-div']],
    ];
    return $build;
  }

  /**
   * Callback for link example.
   *
   * Takes different logic paths based on whether Javascript was enabled.
   * If $type == 'ajax', it tells this function that ajax.js has rewritten
   * the URL and thus we are doing an AJAX and can return an array of commands.
   *
   * @param string $nojs
   *   Either 'ajax' or 'nojs. Type is simply the normal URL argument to this
   *   URL.
   *
   * @return string|array
   *   If $type == 'ajax', returns an array of AJAX Commands.
   *   Otherwise, just returns the content, which will end up being a page.
   */
  public function ajaxLinkCallback($nojs = 'nojs') {
    // Determine whether the request is coming from AJAX or not.
    if ($nojs == 'ajax') {
      $output = $this->t("This is some content delivered via AJAX");
      $response = new AjaxResponse();
      $response->addCommand(new AppendCommand('#ajax-example-destination-div', $output));

      // See ajax_example_advanced.inc for more details on the available
      // commands and how to use them.
      // $page = array('#type' => 'ajax', '#commands' => $commands);
      // ajax_deliver($response);
      return $response;
    }
    $response = new Response($this->t("This is some content delivered via a page load."));
    return $response;
  }

}
