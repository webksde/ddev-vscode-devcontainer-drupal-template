<?php

/**
 * @file
 * Hooks specific to the Hooks Example module.
 */

/**
 * @addtogroup hooks_example
 * @{
 * Whenever your module invokes a hook you should document the use-case, and
 * parameters for that hook in a {MODULE_NAME}.api.php file. The standard is to
 * create a docblock where the first line is a summary starting with an
 * imperative verb. Followed by a more detailed explanation of when the hook
 * is triggered and documentation for any parameters.
 *
 * The body of the function should contain a functional example.
 *
 * The contents of this file are never loaded, or executed, it is purely for
 * documentation purposes.
 *
 * @link https://www.drupal.org/docs/develop/coding-standards/api-documentation-and-comment-standards#hooks
 * Read the standards for documenting hooks. @endlink
 *
 * Examples:
 * @see file.api.php
 * @see node.api.php
 */

/**
 * Respond to node view count being incremented.
 *
 * This hooks allows modules to respond whenever the total number of times the
 * current user has viewed a specific node during their current session is
 * increased.
 *
 * @param int $current_count
 *   The number of times that the current user has viewed the node during this
 *   session.
 * @param \Drupal\node\NodeInterface $node
 *   The node being viewed.
 */
function hook_hooks_example_count_incremented($current_count, \Drupal\node\NodeInterface $node) {
  // If this is the first time the user has viewed this node we display a
  // message letting them know.
  if ($current_count === 1) {
    \Drupal::messenger()->addMessage(t('This is the first time you have viewed the node %title.', ['%title' => $node->label()]));
  }
}

/**
 * @} End of "addtogroup hooks_example".
 */
