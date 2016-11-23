/**
 * @file
 * Javascript behaviors for Bedit feature edit form.
 */

(function ($) {
    'use strict';

    Drupal.behaviors.beedit = {
        attach: function (context, settings) {

            // Step Definition list filter field.
            $('#edit-filter').keyup(function () {
                // Filter the list of step definitions.
                $('#beedit-definition-list').children('div').each(function () {
                   var filter = $('#edit-filter').val();
                   if ($(this).html().toLowerCase().indexOf(filter) >= 0) {
                       $(this).show();
                   }
                   else {
                       $(this).hide();
                   }
                });

                // Hide and display the filter field clear icon.
                $('#edit-filter + span').css({
                    'visibility': $(this).val().length ? 'visible' : 'hidden'
                });
            });

            // Clear the filter field if clear icon is clicked.
            $('#edit-filter + span').click(function () {
               $('#edit-filter').val('').keyup();
               $(this).css({'visibility': 'hidden'});
            });

            // Copy step definition to clipboard.
            $('div.clipboard').click(function (event) {
              copyToClipboard($(this).text().trim());
              displayToolTip(event, 'Definition Copied to Clipboard');
            });

            // Set initial widths of main and secondary panels.
            $(window).resize(function () {
                var containerWidth = $('#beedit-feature-form').width();
                var initialWidth = Math.floor((containerWidth / 2) - 20);
                $('.beedit-edit-form-main').css('width', initialWidth);
                $('.beedit-edit-form-secondary').css('width', initialWidth);
            })

            // Resize main and secondary panels.
            $('.beedit-edit-form-main').resizable({
                handles: 'e',
                resize: function (event, ui) {
                    var containerWidth = $('#beedit-feature-form').width();
                    var currentWidth = ui.size.width;
                    var padding = 20;
                    $(this).width(currentWidth);
                    $('.beedit-edit-form-secondary').width(containerWidth - currentWidth - padding);
                }
            })

            // Function to save text to clipboard.
            function copyToClipboard(text) {
                // Create a hidden textarea populated with the clicked text.
                var textarea = $('<textarea />');
                textarea.val(text).css({
                    visibility: 'none',
                    position: 'fixed',
                }).appendTo('body');

                // Focus and select the text within the textarea.
                textarea.focus().select();

                // Copy the text and remove the temporary textarea.
                document.execCommand('copy');
                textarea.remove();
            }

            // Function to display a temporary tooltip.
            function displayToolTip(event, message) {
                // Create a temporary tooltip, then remove it after a second.
                var tooltip = $('<div>' + message + '</div>').appendTo('body');
                tooltip.css({
                    'position': 'absolute',
                    'top': event.pageY - 50,
                    'left': event.pageX - 20,
                    'background-color': '#888888',
                    'color' : '#FFFFFF',
                    'padding' : '5px',
                });

                setTimeout(function () {
                    tooltip.remove();
                }, 1000);
            }
        }
    };
}(jQuery));
