/**
 * @file
 * Contains the definition of the behaviour jsTestPurpleWeight.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsTestPurpleWeight = {
    attach: function (context, settings) {
      var weight = drupalSettings.js_example.js_weights.purple;
      var newDiv = $('<div></div>').css('color', 'purple').html('I have a weight of ' + weight);
      $('#js-weights').append(newDiv);
    }
  };
})(jQuery, Drupal, drupalSettings);
