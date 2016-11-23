/**
 * @file
 * Javascript behaviors displaying Behat test run results.
 */

(function ($) {
    'use strict';

    Drupal.behaviors.beedit = {
        attach: function (context, settings) {
            var lineNumber = 0;
            var interval;

            var pid = $('#beedit-feature-run-output').data('pid');
            var fileId = $('#beedit-feature-run-output').data('file_id');

            function testStatusUpdate() {
                $.ajax({
                    type: 'GET',
                    url: '/beedit/run_status/' + pid + '/' + fileId + '/' + lineNumber,
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    cache: false,
                    success: function (data) {
                        $.each(data.content, function (index, value) {
                            lineNumber++;
                           $(value).appendTo('#beedit-feature-run-output pre');
                        });
                        if (data.eof) {
                            clearInterval(interval);
                            $('#beedit-feature-run-status').hide();
                        }

                    }
                });
            }
            interval = setInterval(testStatusUpdate, 1500);
        }
    };
}(jQuery));
