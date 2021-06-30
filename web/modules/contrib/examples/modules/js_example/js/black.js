/**
 * @file
 * Contains the definition of the behaviour jsTestBlackWeight.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to to weight div.
   */
  Drupal.behaviors.jsTestBlackWeight = {
    attach: function (context, settings) {
      var weight = drupalSettings.js_example.js_weights.black;
      var newDiv = $('<div></div>').css('color', 'black').html('I have a weight of ' + weight);
      $('#js-weights').append(newDiv);
    }
  };
})(jQuery, Drupal, drupalSettings);
