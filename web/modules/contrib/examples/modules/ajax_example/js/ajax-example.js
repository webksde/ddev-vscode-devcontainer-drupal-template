/**
 * @file
 * JavaScript for ajax_example.
 */

(function ($) {

  // Re-enable form elements that are disabled for non-ajax situations.
  Drupal.behaviors.enableFormItemsForAjaxForms = {
    attach: function () {
      // If ajax is enabled, we want to hide items that are marked as hidden in
      // our example.
      if (Drupal.ajax) {
        $('.ajax-example-hide').hide();
      }
    }
  };

})(jQuery);
