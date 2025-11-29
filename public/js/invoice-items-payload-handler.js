/**
 * Invoice Form Items Payload Handler
 * 
 * This JavaScript should be added at the end of create_modal.blade.php
 * to properly convert items_payload from JSON string to multidimensional array
 */

$(document).ready(function () {
    // Override form submission to handle items_payload properly
    $('form#invoice-form, form[action*="invoice.store"], form[action*="invoice.update"]').on('submit', function (e) {
        // Convert items_payload from JSON string to array format
        var $itemsPayloadInput = $('#items_payload');

        if ($itemsPayloadInput.length && $itemsPayloadInput.val()) {
            try {
                var itemsData = JSON.parse($itemsPayloadInput.val());

                // Create hidden inputs for the multidimensional array
                itemsData.forEach(function (item, index) {
                    for (var key in item) {
                        if (item.hasOwnProperty(key)) {
                            var $input = $('<input>').attr({
                                type: 'hidden',
                                name: 'items_payload[' + index + '][' + key + ']',
                                value: item[key] || ''
                            });
                            $(this).append($input);
                        }
                    }
                }.bind(this));

                // Remove the original JSON input
                $itemsPayloadInput.remove();
            } catch (e) {
                console.error('Error parsing items_payload:', e);
            }
        }

        // Continue with form submission
        return true;
    });
});
