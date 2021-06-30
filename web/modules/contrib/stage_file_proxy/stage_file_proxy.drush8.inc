<?php

/**
 * @file
 * Contains drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function stage_file_proxy_drush_command() {
  $items['stage-file-proxy-dl'] = [
    'description' => 'Download all managed files from the origin.',
  ];

  return $items;
}

/**
 * Download all managed files from the origin.
 */
function drush_stage_file_proxy_dl() {
  \Drupal::service('stage_file_proxy.command')->dl();
}
