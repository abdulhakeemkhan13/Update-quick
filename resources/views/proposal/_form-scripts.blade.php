@push('script-page')
    <script>
        // Handle Product Selection
        $(document).on('change', '.item', function() {
            var $input = $(this);
            var url = $input.data('url');
            var $row = $input.closest('tr');

            if ($input.val() === '') {
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
    </script>
@endpush