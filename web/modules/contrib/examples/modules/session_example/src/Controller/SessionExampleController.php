<?php

namespace Drupal\session_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for a page to display the session information.
 *
 * @ingroup session_example
 */
class SessionExampleController extends ControllerBase {

  /**
   * Display the example session information.
   *
   * By default, controller methods receive a Request object as a parameter, so
   * we can use one here.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   A renderable array containing the user information from the session.
   */
  public function showSession(Request $request) {
    // Get the session from the request object.
    $session = $request->getSession();

    // Make a table of the session information.
    $row = [];
    foreach (['name', 'email', 'quest', 'color'] as $item) {
      $key = "session_example.$item";
      // Get the session value, with a default of 'No name' etc. for each type
      // of information we have.
      $row[0][$item] = $session->get($key, $this->t('No @type', ['@type' => $item]));
    }

    return [
      // Since this page will be cached, we have to manage the caching. We'll
      // use a cache tag and manage it within the session helper. We use the
      // session ID to guarantee a unique tag per session. The submission form
      // will manage invalidating this tag.
      '#cache' => [
        'tags' => ['session_example:' . $session->getId()],
      ],
      'description' => [
        '#type' => 'item',
        '#title' => $this->t('Saved Session Keys'),
        '#markup' => $this->t('The example form lets you set some session keys.  This page lists their current values.'),
      ],
      'session_status' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Email'),
          $this->t('Quest'),
          $this->t('Color'),
        ],
        '#rows' => $row,
      ],
    ];
  }

}
