/**
 * @file
 * Contains the definition of the behaviour jsTestBlueWeight.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsTestBlueWeight = {
    attach: function (context, settings) {
      var weight = drupalSettings.js_example.js_weights.blue;
      var newDiv = $('<div></div>').css('color', 'blue').html('I have a weight of ' + weight);
      $('#js-weights').append(newDiv);
    }
  };
})(jQuery, Drupal, drupalSettings);
