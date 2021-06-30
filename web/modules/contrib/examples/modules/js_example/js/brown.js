/**
 * @file
 * Contains the definition of the behaviour jsTestBrownWeight.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsTestBrownWeight = {
    attach: function (context, settings) {
      var weight = drupalSettings.js_example.js_weights.brown;
      var newDiv = $('<div></div>').css('color', 'brown').html('I have a weight of ' + weight);
      $('#js-weights').append(newDiv);
    }
  };
})(jQuery, Drupal, drupalSettings);
