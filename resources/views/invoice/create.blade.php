@extends('layouts.admin')
@section('page-title')
    {{ __('Invoice Create') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('invoice.index') }}">{{ __('Invoice') }}</a></li>
    <li class="breadcrumb-item">{{ __('Invoice Create') }}</li>
@endsection

@push('css-page')
    <style>
        /* Custom Design from invoiceDesign.php */
        .invoice-container {
            background: #fff;
            max-width: 100%;
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Section */
        .invoice-header {
            background: #fff;
            padding: 20px 32px;
            border-bottom: 1px solid #e4e4e7;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex: 1;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: 400;
            color: #0077c5;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .company-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .company-name {
            font-size: 15px;
            font-weight: 600;
            color: #393a3d;
        }

        .company-email {
            font-size: 14px;
            color: #6b6c72;
        }

        .edit-company-link {
            color: #0077c5;
            font-size: 14px;
            text-decoration: none;
            margin-top: 8px;
            display: inline-block;
            cursor: pointer;
        }

        .edit-company-link:hover {
            text-decoration: underline;
        }

        .header-right {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .balance-due {
            font-size: 14px;
            color: #6b6c72;
            text-align: right;
        }

        .balance-amount {
            font-weight: 600;
            color: #393a3d;
        }

        .logo-section {
            width: 120px;
            height: 120px;
            border: 2px dashed #d4d4d8;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fafafa;
            cursor: pointer;
        }

        /* Customer Section */
        .customer-section {
            background: #f7f8fa;
            padding: 24px 32px;
            border-bottom: 1px solid #e4e4e7;
        }

        .customer-row {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
        }

        .customer-field {
            flex: 2;
        }

        .invoice-field {
            flex: 1;
        }

        .form-label {
            display: block;
            font-size: 13px;
            color: #393a3d;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #c4c4c4;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
            color: #393a3d;
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #0077c5;
            box-shadow: 0 0 0 3px rgba(0, 119, 197, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'%3E%3Cpath fill='%23393a3d' d='M12.014 16.018a1 1 0 0 1-.708-.294L5.314 9.715A1.001 1.001 0 0 1 6.73 8.3l5.286 5.3 5.3-5.285a1 1 0 0 1 1.413 1.416l-6.009 5.995a1 1 0 0 1-.706.292'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 20px;
            padding-right: 36px;
        }

        textarea.form-control {
            resize: none;
            font-family: inherit;
            line-height: 1.5;
        }

        .link-button {
            color: #0077c5;
            font-size: 13px;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            margin-top: 8px;
            display: inline-block;
        }

        .link-button:hover {
            text-decoration: underline;
        }

        /* Transaction Details Grid */
        .transaction-details {
            padding: 24px 32px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            background: #f7f8fa;
            border-bottom: 1px solid #e4e4e7;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .terms-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .terms-label {
            font-size: 13px;
            color: #393a3d;
            font-weight: 500;
            min-width: 60px;
        }

        .terms-field {
            flex: 1;
        }

        /* Product Table */
        .product-section {
            padding: 24px 32px;
            background: #fff;
        }

        .section-heading {
            font-size: 15px;
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 16px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .product-table thead th {
            padding: 12px 8px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            border-bottom: 2px solid #e4e4e7;
            background: #fff;
        }

        .product-table thead th:first-child {
            width: 30px;
        }

        .product-table thead th:nth-child(2) {
            width: 30px;
        }

        .product-table thead th:nth-child(3) {
            width: 40px;
        }

        .product-table thead th:last-child {
            text-align: right;
            width: 120px;
        }

        .product-table tbody td {
            padding: 12px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .product-table tbody td:last-child {
            text-align: right;
        }

        .drag-handle {
            cursor: grab;
            color: #c4c4c4;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .line-number {
            font-size: 13px;
            color: #6b6c72;
        }

        .delete-icon {
            color: #c4c4c4;
            cursor: pointer;
            display: none;
            transition: color 0.2s;
        }

        .product-table tbody tr:hover .delete-icon {
            display: block;
        }

        .delete-icon:hover {
            color: #e81500;
        }

        /* Table Actions */
        .table-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
        }

        .btn-action {
            padding: 8px 16px;
            border: 1px solid #0077c5;
            background: #fff;
            color: #0077c5;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: #ebf4fa;
        }

        .btn-action.split-button {
            padding-right: 36px;
            position: relative;
        }

        .btn-action.split-button::after {
            content: '';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid #0077c5;
        }

        /* Bottom Section */
        .bottom-section {
            padding: 24px 32px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            background: #fff;
        }

        .left-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .info-field {
            display: flex;
            flex-direction: column;
        }

        .info-field label {
            font-size: 13px;
            font-weight: 600;
            color: #393a3d;
            margin-bottom: 8px;
        }

        .info-text {
            font-size: 13px;
            color: #6b6c72;
            line-height: 1.6;
            padding: 12px;
            background: #f7f8fa;
            border-radius: 4px;
            border: 1px solid #e4e4e7;
        }

        .attachment-zone {
            border: 2px dashed #c4c4c4;
            border-radius: 4px;
            padding: 32px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.2s;
        }

        .attachment-zone:hover {
            border-color: #0077c5;
            background: #f7f8fa;
        }

        .attachment-link {
            color: #0077c5;
            font-size: 14px;
            text-decoration: none;
            font-weight: 500;
        }

        .attachment-limit {
            color: #6b6c72;
            font-size: 12px;
            margin-top: 8px;
        }

        /* Totals Section */
        .totals-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding-top: 24px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.subtotal {
            color: #393a3d;
            padding-bottom: 12px;
        }

        .total-row.final {
            font-size: 16px;
            font-weight: 600;
            color: #393a3d;
            padding-top: 16px;
            border-top: 2px solid #e4e4e7;
        }

        /* Footer */
        .invoice-footer {
            background: #f7f8fa;
            padding: 16px 32px;
            border-top: 1px solid #e4e4e7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            position: sticky;
            bottom: 0;
        }

        .footer-link {
            color: #0077c5;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        .footer-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-secondary {
            background: #fff;
            color: #393a3d;
            border: 1px solid #c4c4c4;
        }

        .btn-secondary:hover {
            background: #f7f8fa;
        }

        .btn-primary {
            background: #0b7e3a;
            color: #fff;
            padding-right: 40px;
            position: relative;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid #fff;
        }

        .btn-primary:hover {
            background: #096830;
        }

        .input-right {
            text-align: right;
        }
    </style>
@endpush

@push('script-page')
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var invoiceModal = new bootstrap.Modal(document.getElementById('invoice-modal'), {
                backdrop: 'static',
                keyboard: false
            });
            invoiceModal.show();
        });
    </script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function() {
                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }
                    if ($('.select2').length) {
                        $('.select2').select2();
                    }

                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }
                        $('.subTotal').html(subTotal.toFixed(2));
                        $('.totalAmount').html(subTotal.toFixed(2));
                    }
                },
                ready: function(setIndexes) {

                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
            }

        }

        $(document).on('change', '#customer', function() {
            // $('#customer_detail').removeClass('d-none');
            // $('#customer_detail').addClass('d-block');
            // $('#customer-box').removeClass('d-block');
            // $('#customer-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function(data) {
                    if (data.html != '') {

                        // $('#customer_detail').html(data.html);

                        setTimeout(function() {
                            $('#bill_to').val(data.bill_to);                             
                        }, 50);

                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }

                },

            });
        });

        $(document).on('click', '#remove', function() {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        })

        $(document).on('change', '.item', function() {

            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id
                },
                cache: false,
                success: function(data) {
                    var item = JSON.parse(data);
                    console.log(el.parent().parent().find('.quantity'))
                    $(el.parent().parent().find('.quantity')).val(1);
                    $(el.parent().parent().find('.price')).val(item.product.sale_price);
                    $(el.parent().parent().parent().find('.pro_description')).val(item.product
                        .description);
                    // $('.pro_description').text(item.product.description);

                    var taxes = '';
                    var tax = [];

                    var totalItemTaxRate = 0;

                    if (item.taxes == 0) {
                        taxes += '-';
                    } else {
                        for (var i = 0; i < item.taxes.length; i++) {
                            taxes += '<span class="badge bg-primary mt-1 mr-2">' + item.taxes[i].name +
                                ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                            tax.push(item.taxes[i].id);
                            totalItemTaxRate += parseFloat(item.taxes[i].rate);
                        }
                    }
                    var itemTaxPrice = parseFloat((totalItemTaxRate / 100)) * parseFloat((item.product
                        .sale_price * 1));
                    $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                    $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                    $(el.parent().parent().find('.taxes')).html(taxes);
                    $(el.parent().parent().find('.tax')).val(tax);
                    $(el.parent().parent().find('.unit')).html(item.unit);
                    $(el.parent().parent().find('.discount')).val(0);

                    var inputs = $(".amount");
                    var subTotal = 0;
                    for (var i = 0; i < inputs.length; i++) {
                        subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                    }

                    var totalItemPrice = 0;
                    var priceInput = $('.price');
                    for (var j = 0; j < priceInput.length; j++) {
                        totalItemPrice += parseFloat(priceInput[j].value);
                    }

                    var totalItemTaxPrice = 0;
                    var itemTaxPriceInput = $('.itemTaxPrice');
                    for (var j = 0; j < itemTaxPriceInput.length; j++) {
                        totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                        $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount) +
                            parseFloat(itemTaxPriceInput[j].value));
                    }

                    var totalItemDiscountPrice = 0;
                    var itemDiscountPriceInput = $('.discount');

                    for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                        totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                    }

                    $('.subTotal').html(totalItemPrice.toFixed(2));
                    $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                    $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(
                        totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));

                    calculateTaxableSubtotal();
                    calculateSalesTax();
                    updateTotalAmount();


                },
            });
        });

        $(document).on('keyup', '.quantity', function() {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

            calculateTaxableSubtotal();
            calculateSalesTax();
            updateTotalAmount();

        })

        $(document).on('keyup change', '.price', function() {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();
            if (discount.length <= 0) {
                discount = 0;
            }
            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

            calculateTaxableSubtotal();
            calculateSalesTax();
            updateTotalAmount();


        })

        $(document).on('keyup change', '.discount', function() {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if (discount.length <= 0) {
                discount = 0;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;


            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html(parseFloat(itemTaxPrice) + parseFloat(amount));

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
            }


            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })

        var customerId = '{{ $customerId }}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }

        // Function to calculate taxable subtotal
        function calculateTaxableSubtotal() {
            var taxableSubtotal = 0;
            $('.taxable-checkbox:checked').each(function() {
                var row = $(this).closest('tr');
                var amount = parseFloat(row.find('.amount').html()) || 0;
                taxableSubtotal += amount;
            });
            $('.taxableSubtotal').html('£' + taxableSubtotal.toFixed(2));
            return taxableSubtotal;
        }

        // Function to calculate sales tax
        function calculateSalesTax() {
            var taxableSubtotal = calculateTaxableSubtotal();
            var taxRate = 0;
            var selectedTax = $('#sales_tax_id option:selected');
            if (selectedTax.length && selectedTax.val()) {
                taxRate = parseFloat(selectedTax.data('rate')) || 0;
            }
            var taxAmount = (taxableSubtotal * taxRate) / 100;
            $('#sales_tax_amount').val(taxAmount.toFixed(2));
            $('.totalTax').html('£' + taxAmount.toFixed(2));
            return taxAmount;
        }

        // Event for taxable checkbox change
        $(document).on('change', '.taxable-checkbox', function() {
            calculateTaxableSubtotal();
            calculateSalesTax();
            updateTotalAmount();
        });

        // Event for sales tax change
        $(document).on('change', '#sales_tax_id', function() {
            calculateSalesTax();
            updateTotalAmount();
        });

        // Update total amount including tax
        function updateTotalAmount() {
            
            var subTotal = parseFloat($('.subTotal').html().replace('', '')) || 0;
            var totalDiscount = parseFloat($('.totalDiscount').html().replace('£', '')) || 0;
            var totalTax = parseFloat($('.totalTax').html().replace('£', '')) || 0;
            var totalAmount = subTotal - totalDiscount + totalTax;
            $('.totalAmount').html('£' + totalAmount.toFixed(2));
        }
    </script>
    <script>
        $(document).on('click', '[data-repeater-delete]', function() {
            $(".price").change();
            $(".discount").change();
        });
    </script>
    <script>
        function toggleRecurringPanel() {
            const on = $('#recurring').val() === 'yes';
            $('#recurring-options').toggleClass('d-none', !on);

            // mark fields required when on
            $('#recurring_when, #recurring_start_date, #recurring_repeat, #recurring_every_n')
                .prop('required', on);

            // default behavior for "when to charge"
            handleWhenToCharge();
            handleEndType();
        }

        function handleWhenToCharge() {
            const when = $('#recurring_when').val();
            if (when === 'now') {
                const today = new Date().toISOString().slice(0, 10);
                $('#recurring_start_date').val(today).prop('disabled', true);
                $('#start-required').addClass('d-none');
            } else {
                $('#recurring_start_date').prop('disabled', false);
                // visual "Required" hint if empty
                if (!$('#recurring_start_date').val()) {
                    $('#start-required').removeClass('d-none');
                } else {
                    $('#start-required').addClass('d-none');
                }
            }
        }

        function handleEndType() {
            const type = $('#recurring_end_type').val();
            if (type === 'by') {
                $('#end-by-wrap').removeClass('d-none');
                $('#recurring_end_date').prop('required', true);
            } else {
                $('#end-by-wrap').addClass('d-none');
                $('#recurring_end_date').prop('required', false).val('');
            }
        }

        // init + listeners
        $(document).on('change', '#recurring', toggleRecurringPanel);
        $(document).on('change', '#recurring_when', handleWhenToCharge);
        $(document).on('change keyup', '#recurring_start_date', handleWhenToCharge);
        $(document).on('change', '#recurring_end_type', handleEndType);

        // run once in case of validation errors returning to page
        $(function() {
            toggleRecurringPanel();
        });
    </script>
    <script>
        /** ===== Minimal Schedule Preview =====
         * Renders:
         *  - under Start date:  "Next Invoice Date YYYY-MM-DD."
         *  - under Repeat:      "Last invoice date YYYY-MM-DD"
         *
         * Rules:
         *  - "Next invoice date" is ALWAYS start date + 12 months (1 year)
         *    (independent of the selected repeat) — per the example:
         *      start = 2025-01-01 -> next = 2026-01-01 even if user switches to "monthly".
         *  - "Last invoice date" uses the selected repeat interval and count
         *    (e.g., monthly/quarterly/6months/yearly with every_n = count).
         */

        // --- Helpers ---
        function addMonthsNoOverflow(date, months) {
            const d = new Date(date.getTime());
            const day = d.getDate();
            d.setDate(1);
            d.setMonth(d.getMonth() + months);
            // snap to last day if original day doesn't exist in target month
            const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
            d.setDate(Math.min(day, lastDay));
            return d;
        }

        function toISO(d) {
            // Format as YYYY-MM-DD (avoid TZ drift)
            const tz = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            return tz.toISOString().slice(0, 10);
        }

        function monthsForRepeat(repeat) {
            switch (repeat) {
                case 'monthly':
                    return 1;
                case 'quarterly':
                    return 3; // typical calendar quarterly = 3 months
                case '6months':
                    return 6;
                case 'yearly':
                    return 12;
                default:
                    return 1;
            }
        }

        function ensurePreviewHolders() {
            // small under Start date
            if (!$('#schedule-summary').length) {
                $('<small id="schedule-summary" class="text-muted d-block mt-1"></small>')
                    .insertAfter('#recurring_start_date');
            }
            // small under Repeat
            if (!$('#schedule-preview').length) {
                $('<small id="schedule-preview" class="text-muted d-block mt-1"></small>')
                    .insertAfter('#recurring_repeat');
            }
        }

        function computeSchedulePreview() {
            ensurePreviewHolders();

            const startVal = $('#recurring_start_date').val(); // e.g. "2025-01-01"
            const repeat = $('#recurring_repeat').val(); // monthly|quarterly|6months|yearly
            const everyRaw = $('#recurring_every_n').val(); // count
            let count = parseInt(everyRaw, 10);

            if (!startVal) {
                $('#schedule-summary').text('');
                $('#schedule-preview').text('');
                return;
            }
            if (isNaN(count) || count < 1) count = 1;

            const start = new Date(startVal + 'T00:00:00');


            // --- Last invoice date: based on repeat + count ---
            // If count = 1 -> last = start; otherwise add (count - 1) * interval
            const stepMonths = monthsForRepeat(repeat);
            // --- Next invoice date: ALWAYS start + 12 months (1 year) ---
            const nextDate = addMonthsNoOverflow(start, stepMonths);
            $('#schedule-summary').text('Next Invoice Date ' + toISO(nextDate) + '.');
            let lastDate = new Date(start.getTime());
            if (count > 1) {
                lastDate = addMonthsNoOverflow(start, stepMonths * (count));
            }
            $('#schedule-preview').text('Last invoice date ' + toISO(lastDate));


            // --- Next invoice date: based on start + 12 months ---

        }

        // Hook into changes on only the relevant fields (simple version)
        $(document).on('change keyup',
            '#recurring_start_date, #recurring_repeat, #recurring_every_n',
            computeSchedulePreview
        );

        // Init on load
        $(function() {
            computeSchedulePreview();
        });
    </script>

    <script>
        $(document).ready(function() {
            var currentSelect = null;

            function openAddNewModal($select) {
                if ($select.val() !== '__add__') return;
                $select.val(''); // reset dropdown
                currentSelect = $select; // save reference
                var url = $select.data('create-url');
                var title = $select.data('create-title') || 'Create New';

                // prevent duplicate modal
                if ($('#globalAddNewModal').length) {
                    $('#globalAddNewModal').modal('show');
                    return;
                }

                var $modal = $(`
            <div class="modal fade" id="globalAddNewModal" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">Loading...</div>
                </div>
              </div>
            </div>
        `);

                $('body').append($modal);

                $.get(url, function(html) {
                    $modal.find('.modal-body').html(html);

                    // z-index stacking
                    var zIndex = 1070 + ($('.modal:visible').length * 10);
                    $modal.css('z-index', zIndex);
                    setTimeout(function() {
                        $('.modal-backdrop').last().css('z-index', zIndex - 1).addClass(
                            'modal-stack');
                    }, 0);

                    $modal.modal('show');
                });

                $modal.on('hidden.bs.modal', function() {
                    $modal.remove();
                });
            }

            // Detect "Add New" selection
            $(document).on('change', 'select', function() {
                var $select = $(this);
                if ($select.val() === '__add__') {
                    openAddNewModal($select);
                }
            });

            // AJAX submit for dynamic modal
            $(document).off('submit', '#globalAddNewModal form').on('submit', '#globalAddNewModal form', function(
                e) {
                e.preventDefault();
                var $form = $(this);
                var $modal = $form.closest('#globalAddNewModal');

                // Find the select that triggered this modal
                var $select = currentSelect;

                $.ajax({
                    url: $form.attr('action'),
                    method: $form.attr('method') || 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            // 🔹 Insert new option before the "Add New" of the same select
                            var $addNewOption = $select.find('option[value="__add__"]').first();
                            var $newOption = $('<option>', {
                                value: response.data.id,
                                text: response.data.name
                            });

                            if ($addNewOption.length) {
                                $select.append($newOption);
                                // $newOption.insertBefore($addNewOption);
                            } else {
                                $select.append($newOption);
                            }

                            $select.val(response.data.id).trigger('change');
                            $modal.modal('hide');
                        } else {
                            alert(response.message || 'Something went wrong!');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $form.find('.invalid-feedback').remove();
                            $.each(errors, function(key, msgs) {
                                $form.find('[name="' + key + '"]').after(
                                    `<small class="invalid-feedback text-danger">${msgs[0]}</small>`
                                );
                            });
                        } else {
                            alert('Server error!');
                        }
                    }
                });
            });

        });
    </script>
@endpush

@section('content')
    <div class="modal fade" id="invoice-modal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="invoice-container">
                    {{ Form::open(['url' => 'invoice', 'id' => 'invoice-form']) }}
                    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

                    {{-- Header --}}
                    <div class="invoice-header">
                        <div class="header-left">
                            <h1 class="invoice-title">{{ __('Invoice') }} {{ $invoice_number }}</h1>

                            <div class="company-info">
                                <div class="company-name">{{ Auth::user()->name }}</div>
                                <div class="company-email">{{ Auth::user()->email }}</div>
                                <a href="#" class="edit-company-link">{{ __('Edit company') }}</a>
                            </div>
                        </div>

                        <div class="header-right">
                            <div class="balance-due">
                                {{ __('Balance due (hidden):') }}<br>
                                <span class="balance-amount">£0.00</span>
                            </div>
                            <div class="logo-section">
                                <span style="color: #c4c4c4; font-size: 12px;">{{ __('Add logo') }}</span>
                            </div>
                            <button type="button" class="btn-close"
                                onclick="location.href = '{{ route('invoice.index') }}';" aria-label="Close"></button>
                        </div>
                    </div>

                    {{-- Customer Section --}}
                    <div class="customer-section">
                        <div class="customer-row">
                            <div class="customer-field">
                                {{ Form::select('customer_id', $customers, $customerId ?? '', [
                                    'class' => 'form-select',
                                    'id' => 'customer',
                                    'data-url' => route('invoice.customer'),
                                    'required' => 'required',
                                    'data-create-url' => route('customer.create'),
                                    'data-create-title' => __('Create New Customer'),
                                ]) }}
                            </div>
                        </div>

                        <div class="customer-row">
                            <div class="customer-field">
                                {{ Form::email('customer_email', '', [
                                    'class' => 'form-control',
                                    'id' => 'customer_email',
                                    'placeholder' => 'Customer email',
                                ]) }}
                            </div>
                            <a href="#" class="link-button">{{ __('Cc/Bcc') }}</a>
                        </div>
                        <div id="customer_detail" class="d-none small text-muted"></div>
                    </div>

                    {{-- Transaction Details --}}
                    <div class="transaction-details">
                        <div class="detail-group">
                            <div class="field-group">
                                <label class="form-label">{{ __('Bill to') }}</label>
                                {{ Form::textarea('bill_to', '', [
                                    'class' => 'form-control',
                                    'id' => 'bill_to',
                                    'rows' => 3,
                                ]) }}
                                <a href="#" class="link-button">{{ __('Edit Customer') }}</a>
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="field-group">
                                <label class="form-label">{{ __('Invoice no.') }}</label>
                                {{ Form::text('invoice_number', $invoice_number, [
                                    'class' => 'form-control',
                                    'required' => 'required',
                                    'readonly' => 'readonly',
                                ]) }}
                            </div>

                            <div class="terms-group">
                                <span class="terms-label">{{ __('Terms') }}</span>
                                <div class="terms-field">
                                    {{ Form::select(
                                        'terms',
                                        [
                                            'net_10' => 'Net 10',
                                            'net_15' => 'Net 15',
                                            'net_30' => 'Net 30',
                                            'net_60' => 'Net 60',
                                            'Due on receipt' => 'Due on receipt',
                                        ],
                                        'Net 30',
                                        ['class' => 'form-select'],
                                    ) }}
                                </div>
                            </div>

                            <div class="field-group">
                                <label class="form-label">{{ __('Invoice date') }}</label>
                                {{ Form::date('issue_date', date('Y-m-d'), [
                                    'class' => 'form-control',
                                    'required' => 'required',
                                ]) }}
                            </div>

                            <div class="field-group">
                                <label class="form-label">{{ __('Due date') }}</label>
                                {{ Form::date('due_date', date('Y-m-d', strtotime('+30 days')), [
                                    'class' => 'form-control',
                                    'required' => 'required',
                                ]) }}
                            </div>

                            {{-- <div class="field-group">
                                <label class="form-label">{{ __('Category') }}</label>
                                {{ Form::select('category_id', $category, null, ['class' => 'form-select', 'id' => 'category_id', 'data-create-url' => route('product-category.create'), 'data-create-title' => __('Create New Category')]) }}
                            </div>

                            <div class="field-group">
                                <label class="form-label">{{ __('Ref Number') }}</label>
                                {{ Form::text('ref_number', '', ['class' => 'form-control']) }}
                            </div>

                            {{-- Recurring Toggle
                            <div class="field-group">
                                <label class="form-label">{{ __('Recurring') }}</label>
                                {{ Form::select('recurring', ['no' => 'No', 'yes' => 'Yes'], null, ['class' => 'form-select', 'id' => 'recurring']) }}
                            </div> --}}
                        </div>
                    </div>

                    {{-- Recurring Options (Hidden by default) --}}
                    <div class="transaction-details d-none" id="recurring-options"
                        style="background: #eef2f5; border-top: 0;">
                        <div class="detail-group">
                            <div class="field-group">
                                <label class="form-label">{{ __('When to charge') }}</label>
                                {{ Form::select('recurring_when', ['future' => 'Select future date', 'now' => 'Immediately'], null, ['class' => 'form-select', 'id' => 'recurring_when']) }}
                            </div>
                            <div class="field-group">
                                <label class="form-label">{{ __('Start date') }}</label>
                                {{ Form::date('recurring_start_date', null, ['class' => 'form-control', 'id' => 'recurring_start_date']) }}
                                <small class="text-danger d-none" id="start-required">{{ __('Required') }}</small>
                            </div>
                        </div>
                        <div class="detail-group">
                            <div class="field-group">
                                <label class="form-label">{{ __('Repeat') }}</label>
                                {{ Form::select('recurring_repeat', ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', '6months' => '6 Months', 'yearly' => 'Yearly'], 'monthly', ['class' => 'form-select', 'id' => 'recurring_repeat']) }}
                                <small id="next-date-preview" class="text-muted d-block mt-1"></small>
                            </div>
                            <div class="field-group">
                                <label class="form-label">{{ __('Invoice Count') }}</label>
                                {{ Form::number('recurring_every_n', 1, ['class' => 'form-control', 'id' => 'recurring_every_n', 'min' => 1]) }}
                            </div>
                        </div>
                    </div>

                    {{-- Product Section --}}
                    <div class="product-section repeater">
                        <h2 class="section-heading">{{ __('Product or service') }}</h2>

                        <table class="product-table" id="sortable-table" data-repeater-list="items">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>#</th>
                                    <th>{{ __('Product/service') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Rate') }}</th>
                                    <th>{{ __('Discount') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Tax') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-repeater-item>
                                <tr class="product-row">
                                    <td>
                                        <div style="opacity: 0;">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#c4c4c4">
                                                <circle cx="8" cy="6" r="2" />
                                                <circle cx="16" cy="6" r="2" />
                                                <circle cx="8" cy="12" r="2" />
                                                <circle cx="16" cy="12" r="2" />
                                                <circle cx="8" cy="18" r="2" />
                                                <circle cx="16" cy="18" r="2" />
                                            </svg>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="drag-handle sort-handler">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <circle cx="8" cy="6" r="2" />
                                                <circle cx="16" cy="6" r="2" />
                                                <circle cx="8" cy="12" r="2" />
                                                <circle cx="16" cy="12" r="2" />
                                                <circle cx="8" cy="18" r="2" />
                                                <circle cx="16" cy="18" r="2" />
                                            </svg>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="line-number">1</span>
                                    </td>
                                    <td>
                                        {{ Form::select('item', $product_services, '', [
                                            'class' => 'form-select item',
                                            'placeholder' => 'Select a product/service',
                                            'data-url' => route('invoice.product'),
                                            'required' => 'required',
                                        ]) }}
                                    </td>
                                    <td>
                                        {{ Form::textarea('description', null, [
                                            'class' => 'form-control pro_description',
                                            'rows' => '1',
                                            'placeholder' => '',
                                        ]) }}
                                    </td>
                                    <td>
                                        {{ Form::text('quantity', '', [
                                            'class' => 'form-control input-right quantity',
                                            'placeholder' => '',
                                            'required' => 'required',
                                        ]) }}
                                    </td>
                                    <td>
                                        {{ Form::text('price', '', [
                                            'class' => 'form-control input-right price',
                                            'placeholder' => '',
                                            'required' => 'required',
                                        ]) }}
                                    </td>
                                    <td>
                                        {{ Form::text('discount', '', [
                                            'class' => 'form-control input-right discount',
                                            'placeholder' => '0.00',
                                        ]) }}
                                    </td>
                                    <td>
                                        <input type="checkbox" class="taxable-checkbox" name="taxable[]" value="1" style="margin-bottom: 5px;">
                                        <div class="taxes small"></div>
                                        {{ Form::hidden('tax', '', ['class' => 'form-control tax']) }}
                                        {{ Form::hidden('itemTaxPrice', '', ['class' => 'form-control itemTaxPrice']) }}
                                        {{ Form::hidden('itemTaxRate', '', ['class' => 'form-control itemTaxRate']) }}
                                    </td>
                                    <td>
                                        <span class="amount">0.00</span>
                                    </td>
                                    <td>
                                        <span class="delete-icon" title="Delete line" data-repeater-delete>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z" />
                                            </svg>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="table-actions">
                            <button type="button" class="btn-action split-button" data-repeater-create>
                                {{ __('Add product or service') }}
                            </button>
                            <button type="button" class="btn-action" id="clear-lines">
                                {{ __('Clear all lines') }}
                            </button>
                        </div>

                        {{-- Bottom Section --}}
                        <div class="bottom-section">
                            <div class="left-section">
                                <div class="info-field">
                                    <label>{{ __('Customer payment options') }}</label>
                                    <div class="info-text">
                                        {{ __('Tell your customer how you want to get paid. To keep instructions same for all future invoices, you can specify your payment preferences by clicking on "Edit default".') }}
                                    </div>
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Note to customer') }}</label>
                                    {{ Form::textarea('note', '', [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => 'Thank you for your business.',
                                    ]) }}
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Memo on statement (hidden)') }}</label>
                                    {{ Form::textarea('memo', '', [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => 'This memo will not show up on your invoice, but will appear on the statement.',
                                    ]) }}
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Attachments') }}</label>
                                    <div class="attachment-zone">
                                        <a href="#" class="attachment-link">{{ __('Add attachment') }}</a>
                                        <div class="attachment-limit">{{ __('Max file size: 20 MB') }}</div>
                                    </div>
                                </div>

                                <div class="info-field">
                                    <label>{{ __('Sales Tax') }}</label>
                                    <select class="form-select" id="sales_tax_id" name="sales_tax_id">
                                        <option value="">Select Tax</option>
                                        @foreach($taxes ?? [] as $tax)
                                        <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <input type="text" class="form-control" id="sales_tax_amount" readonly placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div class="totals-section">
                                <div class="total-row subtotal">
                                    <span>{{ __('Subtotal') }}</span>
                                    <span class="subTotal">£0.00</span>
                                </div>
                                <div class="total-row">
                                    <span>{{ __('Taxable Subtotal') }}</span>
                                    <span class="taxableSubtotal">£0.00</span>
                                </div>
                                <div class="total-row">
                                    <span>{{ __('Discount') }}</span>
                                    <span class="totalDiscount">£0.00</span>
                                </div>
                                <div class="total-row">
                                    <span>{{ __('Tax') }}</span>
                                    <span class="totalTax">£0.00</span>
                                </div>

                                <div class="total-row final">
                                    <span>{{ __('Invoice total') }}</span>
                                    <span class="totalAmount">£0.00</span>
                                </div>

                                <a href="#" class="link-button">{{ __('Edit totals') }}</a>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="invoice-footer">
                        <a href="#" class="footer-link">{{ __('Print or download') }}</a>

                        <div class="footer-actions">
                            <button type="button" class="btn btn-secondary"
                                onclick="location.href = '{{ route('invoice.index') }}';">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-secondary">{{ __('Save') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Review and send') }}</button>
                        </div>
                    </div>

                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection
