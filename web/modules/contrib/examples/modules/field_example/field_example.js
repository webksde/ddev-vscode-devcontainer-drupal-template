/**
 * @file
 * Javascript for Field Example.
 */

/**
 * Provides a farbtastic colorpicker for the fancier widget.
 */
(function ($) {

  'use strict';

  Drupal.behaviors.field_example_colorpicker = {
    attach: function () {
      $('.edit-field-example-colorpicker').on('focus', function (event) {
        var edit_field = this;
        var picker = $(this).closest('div').parent().find('.field-example-colorpicker');
        // Hide all color pickers except this one.
        $('.field-example-colorpicker').hide();
        $(picker).show();
        $.farbtastic(picker, function (color) {
          edit_field.value = color;
        }).setColor(edit_field.value);
      });
    }
  };
})(jQuery);
