/**
 * @file
 * Contains the definition of the behaviour jsTestRedWeight.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsTestRedWeight = {
    attach: function (context, settings) {
      var weight = drupalSettings.js_example.js_weights.red;
      var newDiv = $('<div></div>').css('color', 'red').html('I have a weight of ' + weight);
      $('#js-weights').append(newDiv);
    }
  };
})(jQuery, Drupal, drupalSettings);
