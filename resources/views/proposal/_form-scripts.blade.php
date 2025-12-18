    <script>
        // Handle Product Selection
        $(document).on('change', '.item', function() {
            if (window.itemChangeInProgress) return;
            window.itemChangeInProgress = true;
            var $input = $(this);
            var url = $input.data('url');
            var $row = $input.closest('tr');

            if ($input.val() === '') {
                window.itemChangeInProgress = false;
                return;
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    product_id: $input.val(),
                    _token: $('#token').val()
                },
                success: function(data) {
                    var item = JSON.parse(data);

                    // Populate fields
                    $row.find('.quantity').val(1);
                    $row.find('.price').val(item.product.sale_price);
                    $row.find('.pro_description').val(item.product.description);

                    // Handle Taxable Checkbox
                    // If product has taxes, we check the box. If not, we uncheck it.
                    // The 'item.taxes' from controller is an array of tax objects or 0.
                    var hasTax = (item.taxes && item.taxes.length > 0);
                    $row.find('.form-check-input[type="checkbox"]').prop('checked', hasTax);

                    // Recalculate row and totals
                    // These functions are defined in proposal/create.blade.php
                    if (typeof recalcRowAmount === 'function') {
                        recalcRowAmount($row);
                    }
                    if (typeof recalcTotals === 'function') {
                        recalcTotals();
                    }
                },
                error: function(data) {
                    console.error('Error fetching product details:', data);
                },
                complete: function() {
                    window.itemChangeInProgress = false;
                }
            });
        });

        // Handle Input Changes for Recalculation
        $(document).on('keyup change', '.quantity, .price', function() {
            var $row = $(this).closest('tr');
            if (typeof recalcRowAmount === 'function') {
                recalcRowAmount($row);
            }
            if (typeof recalcTotals === 'function') {
                recalcTotals();
            }
        });

        // Handle Tax Checkbox Change
        $(document).on('change', '.form-check-input[type="checkbox"]', function() {
            if (typeof recalcTotals === 'function') {
                recalcTotals();
            }
        });
        $(function() {
            if (window.formScriptsInitialized) return;
            window.formScriptsInitialized = true;

            var subtotalLabel = @json(__('Subtotal'));
            var textPlaceholder = @json(__('Enter your text or leave blank as a divider'));

            // 6-dot icon
            var dotsSvg =
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="#c4c4c4">' +
                '<circle cx="8" cy="6" r="2"></circle>' +
                '<circle cx="16" cy="6" r="2"></circle>' +
                '<circle cx="8" cy="12" r="2"></circle>' +
                '<circle cx="16" cy="12" r="2"></circle>' +
                '<circle cx="8" cy="18" r="2"></circle>' +
                '<circle cx="16" cy="18" r="2"></circle>' +
                '</svg>';

            // delete cell for subtotal/text rows (no data-repeater-delete)
            var deleteCellHtml =
                '<td class="text-center">' +
                '<span class="delete-icon qb-special-delete" title="Delete line">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">' +
                '<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path>' +
                '</svg>' +
                '</span>' +
                '</td>';

            // --- ADDED FUNCTIONS ---
            window.renumberProposalLines = function() {
                var $rows = $('#sortable-table tbody tr.product-row');
                $rows.each(function(index) {
                    $(this).find('.line-number').text(index + 1);
                });
            };

            window.recalcRowAmount = function($row) {
                var quantity = parseFloat($row.find('.quantity').val()) || 0;
                var price = parseFloat($row.find('.price').val()) || 0;
                // var discount = parseFloat($row.find('.discount').val()) || 0; // Discount column removed in UI
                var discount = 0; 
                
                var amount = (quantity * price) - discount;
                $row.find('.amount').val(amount.toFixed(2));

                // Tax calculation
                var taxRate = parseFloat($row.find('.itemTaxRate').val()) || 0;
                var taxPrice = (amount * taxRate) / 100;
                $row.find('.itemTaxPrice').val(taxPrice.toFixed(2));
            };

window.recalcTotals = function() {
    var subTotal        = 0;
    var taxableSubtotal = 0;   // 🔹 NEW
    var totalTax        = 0;
    var totalDiscount   = 0;
    var sectionSubtotal = 0;   // For inline subtotal rows

    // Iterate through ALL rows in the table (products, subtotals, text)
    $('#sortable-table tbody tr').each(function() {
        var $row = $(this);

        if ($row.hasClass('product-row')) {
            var quantity = parseFloat($row.find('.quantity').val()) || 0;
            var price    = parseFloat($row.find('.price').val()) || 0;
            var amount   = quantity * price;

            subTotal       += amount;
            sectionSubtotal += amount; // For section subtotals

            // ⬇️ tax logic is now controlled by the checkbox
            var isTaxable = $row.find('.form-check-input[type="checkbox"]').prop('checked');
            var rowTax    = 0;

            if (isTaxable) {
                // use per-row tax amount if present
                rowTax = parseFloat($row.find('.itemTaxPrice').val()) || 0;
                taxableSubtotal += amount;     // 🔹 add to taxable subtotal ONLY if checked
            } else {
                // make sure DB doesn’t keep stale tax for non-taxable row
                $row.find('.itemTaxPrice').val('0.00');
            }

            totalTax += rowTax;

        } else if ($row.hasClass('subtotal-row')) {
            // Update the subtotal row with the section total
            $row.find('.subtotal-amount').text(sectionSubtotal.toFixed(2));
            // reset for next section
            sectionSubtotal = 0;
        }
    });

    // Update UI labels
    $('.subTotal').text(subTotal.toFixed(2));
    $('.taxableSubtotal').text(taxableSubtotal.toFixed(2));   // 🔹 NEW
    $('.totalTax').text(totalTax.toFixed(2));
    $('.totalDiscount').text(totalDiscount.toFixed(2));

    var grandTotal = subTotal - totalDiscount + totalTax;
    $('.totalAmount').text(grandTotal.toFixed(2));

    // Update hidden inputs used on submit
    $('input[name="subtotal"]').val(subTotal.toFixed(2));
    $('input[name="taxable_subtotal"]').val(taxableSubtotal.toFixed(2)); // 🔹 NEW
    $('input[name="total_tax"]').val(totalTax.toFixed(2));
    $('input[name="total_amount"]').val(grandTotal.toFixed(2));
};



            // Make these functions globally accessible for edit mode auto-population
            window.createSubtotalBody = function createSubtotalBody(initialAmount) {
                var amountText = typeof initialAmount === 'string' ? initialAmount : '0.00';
                var $tbody = $('<tbody class="special-body subtotal-body"></tbody>');
                var $row = $('<tr class="subtotal-row"></tr>');

                $row.append(
                    '<td><div style="opacity:0;">' + dotsSvg + '</div></td>' +
                    '<td><div class="drag-handle sort-handler">' + dotsSvg + '</div></td>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td style="font-size:13px;font-weight:600;color:#393a3d;">' +
                    subtotalLabel +
                    '</td>' +
                    '<td class="input-right subtotal-amount" ' +
                    'style="font-size:13px;color:#393a3d;">' + amountText + '</td>' +
                    '<td></td>' +
                    deleteCellHtml
                );

                $tbody.append($row);
                return $tbody;
            }

            window.createTextBody = function createTextBody(initialText) {
                var $tbody = $('<tbody class="special-body text-body"></tbody>');
                var $row = $('<tr class="text-row"></tr>');

                $row.append(
                    '<td><div style="opacity:0;">' + dotsSvg + '</div></td>' +
                    '<td><div class="drag-handle sort-handler">' + dotsSvg + '</div></td>' +
                    '<td></td>' +
                    '<td colspan="5">' +
                    '<input type="text" name="extra_lines_text[]" ' +
                    'class="form-control" ' +
                    'value="' + (initialText || '') + '" ' +
                    'placeholder="' + textPlaceholder + '" ' +
                    'style="border:none;background:transparent;padding-left:0;box-shadow:none;" />' +
                    '</td>' +
                    '<td></td>' +
                    deleteCellHtml
                );

                $tbody.append($row);
                return $tbody;
            }

            // ----- bottom split button ("Add product or service" ▼) -----

            $('#new-line-menu-toggle').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#new-line-menu').toggleClass('show');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#new-line-menu, #new-line-menu-toggle, .qb-row-menu-wrapper')
                    .length) {
                    $('#new-line-menu').removeClass('show');
                    $('.qb-row-menu').removeClass('show');
                }
            });

            $('#add-subtotal-line').on('click', function() {
                $('#new-line-menu').removeClass('show');
                var $tbody = createSubtotalBody('0.00');
                $('#sortable-table').append($tbody);
                recalcTotals();
            });

            $('#add-text-line').on('click', function() {
                $('#new-line-menu').removeClass('show');
                var $tbody = createTextBody();
                $('#sortable-table').append($tbody);
                renumberProposalLines();
                recalcTotals();
            });

            // Clear all lines
            $('#clear-lines').on('click', function() {
                var $tbodies = $('#sortable-table').find('tbody');
                var $first = $tbodies.first();

                if (!confirm('{{ __('Clear all lines?') }}')) return;

                $tbodies.slice(1).remove();

                $first.find('select.item').val('');
                $first.find('textarea.pro_description').val('');
                $first.find('input.quantity').val('');
                $first.find('input.price').val('');
                $first.find('input.amount').val('0.00');
                $first.find('.form-check-input[type="checkbox"]').prop('checked', false);

                renumberProposalLines();
                recalcTotals();
            });

            // delete subtotal/text rows (those without data-repeater-delete)
            $(document).on('click', '.delete-icon', function(e) {
                if (!$(this).is('[data-repeater-delete]')) {
                    e.preventDefault();
                    $(this).closest('tbody').remove();
                    renumberProposalLines();
                    recalcTotals();
                }
            });

            // ----- per-row + menu (circle button on each row) -----

            $(document).on('click', '.qb-row-menu-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $menu = $(this).siblings('.qb-row-menu');
                $('.qb-row-menu').not($menu).removeClass('show');
                $menu.toggleClass('show');
            });

            // Add product or service under this row
            $(document).on('click', '.row-add-product', function(e) {
                e.preventDefault();
                var $tbody = $(this).closest('tbody');

                window.qbInsertAfterTbody = $tbody;
                window.qbDuplicateSource = null;

                $('.qb-row-menu').removeClass('show');
                $('[data-repeater-create]').trigger('click');
            });

            // Add subtotal under this row (subtotal for rows up to that point)
            $(document).on('click', '.row-add-subtotal', function(e) {
                e.preventDefault();
                var $tbody = $(this).closest('tbody');
                var $newBody = createSubtotalBody('0.00');
                $newBody.insertAfter($tbody);
                $('.qb-row-menu').removeClass('show');
                recalcTotals();
            });

            // Duplicate this line
            $(document).on('click', '.row-duplicate-line', function(e) {
                e.preventDefault();
                var $tbody = $(this).closest('tbody');

                window.qbInsertAfterTbody = $tbody;
                window.qbDuplicateSource = $tbody;

                $('.qb-row-menu').removeClass('show');
                $('[data-repeater-create]').trigger('click');
            });

            // Add text line under this row
            $(document).on('click', '.row-add-text', function(e) {
                e.preventDefault();
                var $tbody = $(this).closest('tbody');
                var $newBody = createTextBody();

                $newBody.insertAfter($tbody);
                $('.qb-row-menu').removeClass('show');
                renumberProposalLines();
                recalcTotals();
            });
        });
    </script>

    <script>
        // AUTO-POPULATION FOR EDIT MODE
        $(document).ready(function() {
            if (window.autoPopulationInitialized) return;
            window.autoPopulationInitialized = true;
            try {

            @if(isset($proposalData))
            var proposalData = @json($proposalData);
            console.log('Edit Mode - Proposal Data:', proposalData);

            // 1. POPULATE CUSTOMER
            if (proposalData.customer_id) {
                $('#customer').val(proposalData.customer_id).trigger('change');

                // Show and populate bill-to after AJAX completes
                if (proposalData.bill_to) {
                    setTimeout(function() {
                        $('textarea[name="bill_to"]').val(proposalData.bill_to);
                        $('#bill-to-section').show();
                    }, 600);
                }
            }

            // 2. POPULATE DATES AND TEXT FIELDS
            if (proposalData.issue_date) $('input[name="issue_date"]').val(proposalData.issue_date);
            if (proposalData.send_date) $('input[name="send_date"]').val(proposalData.send_date);
            if (proposalData.accepted_by) $('input[name="accepted_by"]').val(proposalData.accepted_by);
            if (proposalData.terms) $('textarea[name="terms"]').val(proposalData.terms);
            if (proposalData.memo) $('textarea[name="memo"]').val(proposalData.memo);
            if (proposalData.note) $('textarea[name="note"]').val(proposalData.note);

            // 3. POPULATE LOGO
            if (proposalData.logo) {
                var $logoPreview = $('.logo-preview');
                var $addText = $('.add-logo-text');
                var $sizeText = $('.logo-size-limit');
                var $removeBtn = $('#company_logo_remove');
                var $logoButton = $('#company_logo_button');

                $logoPreview.attr('src', proposalData.logo).removeClass('d-none');
                $addText.addClass('d-none');
                $sizeText.addClass('d-none');
                $removeBtn.removeClass('d-none');
                $logoButton.addClass('has-logo');
            }

            // 4. POPULATE ATTACHMENTS
            if (proposalData.attachments && proposalData.attachments.length > 0) {
                var $list = $('#attachments-list');
                var $header = $('#attachments-header');
                var attachLabel = @json(__('Attach to email'));

                proposalData.attachments.forEach(function(attachment, index) {
                    var sizeKB = Math.round(attachment.size / 1024);
                    var rowId = 'existing_att_' + index;

                    var $row = $(
                        '<div class="attachment-row" data-row-id="' + rowId + '">' +
                        '<div class="form-check">' +
                        '<input class="form-check-input attachment-email" type="checkbox" ' +
                        'name="attachments_email[' + rowId + ']" ' + (attachment.attach_to_email ? 'checked' : '') + '>' +
                        '<label class="form-check-label">' + attachLabel + '</label>' +
                        '</div>' +
                        '<span class="attachment-name">' + attachment.name + '</span>' +
                        '<span class="attachment-size">' + sizeKB + ' KB</span>' +
                        '<button type="button" class="attachment-remove" data-row-id="' + rowId + '">×</button>' +
                        '<input type="hidden" name="existing_attachments[]" value="' + attachment.name + '" data-row-id="' + rowId + '">' +
                        '</div>'
                    );
                    $list.append($row);
                });

                $header.removeClass('d-none');
                var $boxes = $list.find('.attachment-email');
                var $checked = $boxes.filter(':checked');
                $('#attachment_select_all').prop('checked', $boxes.length > 0 && $boxes.length === $checked.length);
            }

            // 5. POPULATE LINE ITEMS
            if (proposalData.items && proposalData.items.length > 0) {
                // Clear default empty row
                $('#sortable-table tbody[data-repeater-item]').remove();

                var delay = 0;
                proposalData.items.forEach(function(item, index) {
                    delay += 150;

                    if (item.type === 'product') {
                        setTimeout(function() {
                            $('[data-repeater-create]').first().trigger('click');

                            setTimeout(function() {
                                var $lastBody = $('#sortable-table tbody[data-repeater-item]').last();
                                var $row = $lastBody.find('.product-row');

                                $row.find('select.item').val(item.item || '');
                                $row.find('.pro_description').val(item.description || '');
                                $row.find('.quantity').val(item.quantity || '');
                                $row.find('.price').val(item.price || '');
                                $row.find('.discount').val(item.discount || '');
                                $row.find('.amount').val(item.amount || '');
                                $row.find('.tax').val(item.tax || '');
                                $row.find('.itemTaxPrice').val(item.itemTaxPrice || '');
                                $row.find('.itemTaxRate').val(item.itemTaxRate || '');

                                if (item.taxable == 1) {
                                    $row.find('.form-check-input[type="checkbox"]').prop('checked', true);
                                }
                            }, 50);
                        }, delay);

                    } else if (item.type === 'subtotal') {
                        setTimeout(function() {
                            $('#add-subtotal-line').trigger('click');
                            setTimeout(function() {
                                var $lastBody = $('#sortable-table tbody').last();
                                var $subtotalRow = $lastBody.find('.subtotal-row');
                                $subtotalRow.find('.subtotal-amount').text(item.amount || '0.00');
                            }, 50);
                        }, delay);

                    } else if (item.type === 'text') {
                        setTimeout(function() {
                            $('#add-text-line').trigger('click');
                            setTimeout(function() {
                                var $lastBody = $('#sortable-table tbody').last();
                                var $textRow = $lastBody.find('.text-row');
                                $textRow.find('input[type="text"]').val(item.description || item.text || '');
                            }, 50);
                        }, delay);
                    }
                });

                // Recalculate after all loaded
                setTimeout(function() {
                    if (typeof renumberProposalLines === 'function') renumberProposalLines();
                    if (typeof recalcTotals === 'function') recalcTotals();
                }, delay + 300);
            }
            @endif
            } catch (e) {
                console.error('Error in auto-population:', e);
            }
        });
    </script>
