<script src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/jquery.repeater.min.js') }}"></script>
<script src="{{ asset('js/jquery-searchbox.js') }}"></script>
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

                // for item SearchBox ( this function is  custom Js )
                JsSearchBox();

                $('.select2').select2();
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
                // console.log(item)

                $(el.parent().parent().find('.quantity')).val(1);
                $(el.parent().parent().find('.price')).val(item.product.purchase_price);
                $(el.parent().parent().parent().find('.pro_description')).val(item.product
                    .description);

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
                var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (item.product
                    .purchase_price * 1));

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


                var accountinputs = $(".accountamount");
                var accountSubTotal = 0;
                for (var i = 0; i < accountinputs.length; i++) {
                    var currentInputValue = parseFloat(accountinputs[i].innerHTML);
                    if (!isNaN(currentInputValue)) {
                        accountSubTotal += currentInputValue;
                    }
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


                $('.subTotal').html((totalItemPrice + accountSubTotal).toFixed(2));
                $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(
                    totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));


                var totalAmount = parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice) +
                    parseFloat(totalItemTaxPrice);
                $('.totalAmount').val(totalAmount.toFixed(2));




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


        var totalAccount = 0;
        var accountInput = $('.accountAmount');

        for (var j = 0; j < accountInput.length; j++) {
            if (typeof accountInput[j].value != 'undefined') {
                var accountInputPrice = parseFloat(accountInput[j].value);

                if (isNaN(accountInputPrice)) {
                    totalAccount = 0;
                } else {
                    totalAccount += accountInputPrice;
                }
            }
        }

        var inputs = $(".amount");
        var subTotal = 0;
        for (var i = 0; i < inputs.length; i++) {
            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
        }


        var sumAmount = totalItemPrice + totalAccount;

        $('.subTotal').html((sumAmount).toFixed(2));
        $('.totalTax').html(totalItemTaxPrice.toFixed(2));
        $('.totalAmount').html((parseFloat(subTotal) + totalAccount).toFixed(2));

        //get hidden value of totalAmount
        var totalAmount = (parseFloat(subTotal) + totalAccount);
        $('.totalAmount').val(totalAmount.toFixed(2));



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


        var totalAccount = 0;
        var accountInput = $('.accountAmount');

        for (var j = 0; j < accountInput.length; j++) {
            if (typeof accountInput[j].value != 'undefined') {
                var accountInputPrice = parseFloat(accountInput[j].value);

                if (isNaN(accountInputPrice)) {
                    totalAccount = 0;
                } else {
                    totalAccount += accountInputPrice;
                }
            }
        }

        var inputs = $(".amount");
        var subTotal = 0;
        for (var i = 0; i < inputs.length; i++) {
            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
        }


        $('.subTotal').html((totalItemPrice + totalAccount).toFixed(2));
        $('.totalTax').html(totalItemTaxPrice.toFixed(2));
        $('.totalAmount').html((parseFloat(subTotal) + totalAccount).toFixed(2));

        //get hidden value of totalAmount
        var totalAmount = (parseFloat(subTotal) + totalAccount);
        $('.totalAmount').val(totalAmount.toFixed(2));


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

        var totalAccount = 0;
        var accountInput = $('.accountAmount');

        for (var j = 0; j < accountInput.length; j++) {
            if (typeof accountInput[j].value != 'undefined') {
                var accountInputPrice = parseFloat(accountInput[j].value);

                if (isNaN(accountInputPrice)) {
                    totalAccount = 0;
                } else {
                    totalAccount += accountInputPrice;
                }
            }
        }


        // $('.subTotal').html(totalItemPrice.toFixed(2));
        $('.subTotal').html((totalItemPrice + totalAccount).toFixed(2));

        $('.totalTax').html(totalItemTaxPrice.toFixed(2));

        $('.totalAmount').html((parseFloat(subTotal) + totalAccount).toFixed(2));
        $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));


        //get hidden value of totalAmount
        var totalAmount = (parseFloat(subTotal) + totalAccount);
        $('.totalAmount').val(totalAmount.toFixed(2));


    })

    $(document).on('keyup change', '.accountAmount', function() {

        var el1 = $(this).parent().parent().parent().parent();
        var el = $(this).parent().parent().parent().parent().parent();

        var quantityDiv = $(el.find('.quantity'));
        var priceDiv = $(el.find('.price'));
        var discountDiv = $(el.find('.discount'));

        var itemSubTotal = 0;
        for (var p = 0; p < priceDiv.length; p++) {
            var quantity = quantityDiv[p].value;
            var price = priceDiv[p].value;
            var discount = discountDiv[p].value;
            if (discount.length <= 0) {
                discount = 0;
            }
            itemSubTotal += (quantity * price) - (discount);
        }

        var totalItemTaxPrice = 0;
        var itemTaxPriceInput = $('.itemTaxPrice');

        for (var j = 0; j < itemTaxPriceInput.length; j++) {
            var parsedValue = parseFloat(itemTaxPriceInput[j].value);

            if (!isNaN(parsedValue)) {
                totalItemTaxPrice += parsedValue;
            }
        }


        var amount = $(this).val();
        el1.find('.accountamount').html(amount);
        var totalAccount = 0;
        var accountInput = $('.accountAmount');
        for (var j = 0; j < accountInput.length; j++) {
            totalAccount += (parseFloat(accountInput[j].value));
        }


        var inputs = $(".accountamount");
        var subTotal = 0;
        for (var i = 0; i < inputs.length; i++) {

            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
        }


        $('.subTotal').text((totalAccount + itemSubTotal).toFixed(2));
        $('.totalAmount').text((parseFloat((subTotal + itemSubTotal) + (totalItemTaxPrice))).toFixed(2));


        //get hidden value of totalAmount
        var totalAmount = (parseFloat((subTotal + itemSubTotal) + (totalItemTaxPrice)));
        $('.totalAmount').val(totalAmount.toFixed(2));


    })




    var id = '{{ $Id }}';
    if (id > 0) {
        $('#vender').val(id).change();
    }
</script>
<script>
    $(document).on('click', '[data-repeater-delete]', function() {
        $(".price").change();
        $(".discount").change();
    });
</script>
{{-- start for user select --}}
<script>
    $(document).ready(function() {
        $('input[name=type]:first').prop('checked', true);
    });


    $('input[name="type"]:radio').on('change', function(e) {
        var type = $(this).val();
        if (type == 'employee') {
            $('.employee').addClass('d-block');
            $('.employee').removeClass('d-none');
            $('.customer').addClass('d-none');
            $('.customer').removeClass('d-block');
            $('.vendor').addClass('d-none');
            $('.vendor').removeClass('d-block');
        } else if (type == 'customer') {
            $('.customer').addClass('d-block');
            $('.customer').removeClass('d-none');
            $('.employee').addClass('d-none');
            $('.employee').removeClass('d-block');
            $('.vendor').addClass('d-none');
            $('.vendor').removeClass('d-block');
        } else {
            $('.vendor').addClass('d-block');
            $('.vendor').removeClass('d-none');
            $('.employee').addClass('d-none');
            $('.employee').removeClass('d-block');
            $('.customer').addClass('d-none');
            $('.customer').removeClass('d-block');
        }
    });

    $('input[name="type"]:radio:checked').trigger('change');

    $(document).on('change', '#employee', function() {

        $('#employee_detail').removeClass('d-none');
        $('#employee_detail').addClass('d-block');
        $('#employee-box').removeClass('d-block');
        $('#employee-box').addClass('d-none');

        var cId = $(this).val();
        var url = $('#employee').data('url');

        $.ajax({
            url: url,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': jQuery('#token').val()
            },
            data: {
                'id': cId
            },
            cache: false,
            success: function(data) {

                if (data != '') {
                    $('#employee_detail').html(data);

                } else {
                    $('#employee-box').removeClass('d-none');
                    $('#employee-box').addClass('d-block');
                    $('#employee_detail').removeClass('d-block');
                    $('#employee_detail').addClass('d-none');
                }

            },
        });
    });

    $(document).on('change', '#customer', function() {
        $('#customer_detail').removeClass('d-none');
        $('#customer_detail').addClass('d-block');
        $('#customer-box').removeClass('d-block');
        $('#customer-box').addClass('d-none');
        var id = $(this).val();
        var url = $('#customer').data('url');
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

                if (data != '') {
                    $('#customer_detail').html(data);
                } else {
                    $('#customer-box').removeClass('d-none');
                    $('#customer-box').addClass('d-block');
                    $('#customer_detail').removeClass('d-block');
                    $('#customer_detail').addClass('d-none');
                }
            },
        });
    });

    $(document).on('change', '#vender', function() {
        $('#vender_detail').removeClass('d-none');
        $('#vender_detail').addClass('d-block');
        $('#vender-box').removeClass('d-block');
        $('#vender-box').addClass('d-none');
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
                if (data != '') {
                    $('#vender_detail').html(data);
                } else {
                    $('#vender-box').removeClass('d-none');
                    $('#vender-box').addClass('d-block');
                    $('#vender_detail').removeClass('d-block');
                    $('#vender_detail').addClass('d-none');
                }
            },
        });
    });


    $(document).on('click', '#remove', function() {
        $('#vender-box').removeClass('d-none');
        $('#vender-box').addClass('d-block');
        $('#vender_detail').removeClass('d-block');
        $('#vender_detail').addClass('d-none');

        $('#customer-box').removeClass('d-none');
        $('#customer-box').addClass('d-block');
        $('#customer_detail').removeClass('d-block');
        $('#customer_detail').addClass('d-none');

        $('#employee-box').removeClass('d-none');
        $('#employee-box').addClass('d-block');
        $('#employee_detail').removeClass('d-block');
        $('#employee_detail').addClass('d-none');

    })
</script>
{{-- end for user select --}}
<script>
    $(document).ready(function() {
        var currentSelect = null;
        var currentType = null;

        function openAddNewModal($select) {

            let v = $select.val();

            if (!(v === '__add__' || v.startsWith('__add_'))) {
                return;
            }
            $select.val(''); // reset dropdown
            currentSelect = $select; // save reference
            if (v.startsWith('__add_')) {
                var url = $select.attr("data-create-url");
                var title = $select.attr("data-create-title");
                currentType = $select.attr("data-create-type");
            } else {
                var url = $select.data('create-url');
                var title = $select.data('create-title') || 'Create New';

            }
            console.log(url, title, currentType, 'ad');

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
            } else if ($select.val().startsWith("__add_")) {
                let selected = $select.find(":selected");

                // Move attributes to SELECT so your global function remains SAME
                $select.attr("data-create-url", selected.data("create-url"));
                $select.attr("data-create-title", selected.data("create-title"));
                $select.attr("data-create-type", selected.data("create-type"));
                openAddNewModal($select);
            }
        });
        // $(document).on('change', '#payee_all', function () {
        //     let $select = $(this);
        //     let value = $select.val();

        //     // detect any add option
        //     if (value === "__add__" || value.startsWith("__add_")) {
        //         // Force modal to use THIS option's data attributes
        //         let selected = $select.find(":selected");

        //         // Move attributes to SELECT so your global function remains SAME
        //         $select.attr("data-create-url", selected.data("create-url"));
        //         $select.attr("data-create-title", selected.data("create-title"));
        //         // Call your existing global modal function
        //         openAddNewModal($select);

        //     }
        // });

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
                        if (currentType != null) {
                            console.log(currentType);
                            let $targetGroup = $('optgroup[label="' + currentType + '"]',
                                $select);

                            let $newOption = $('<option>', {
                                value: currentType + '_' + response.data
                                    .id, // group prefix
                                text: response.data.name
                            });

                            // Insert after __add_type
                            $targetGroup.find('option[value="__add_' + currentType + '"]')
                                .after($newOption);

                            // Select new value
                            $select.val(currentType + '_' + response.data.id).trigger(
                                'change');

                        } else {


                            // 🔹 Insert new option before the "Add New" of the same select
                            var $addNewOption = $select.find('option[value="__add__"]')
                                .first();
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
                        }
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

<script>
    $(document).ready(function() {

        // Category Repeater
        // Function to enable sortable on category table
        function initCategorySortable() {
            $('#category-table tbody').sortable({
                handle: '.drag-handle',
                helper: 'clone',
                axis: 'y',
                update: function() {
                    updateRowNumbers('category-table');
                }
            });
        }

        function initCategorySortable() {
            $('#item-table tbody').sortable({
                handle: '.drag-handle',
                helper: 'clone',
                axis: 'y',
                update: function() {
                    updateRowNumbers('item-table');
                }
            });
        }

        function updateRowNumbers(tableId) {
            $('#' + tableId + ' tbody tr').each(function(index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        // Call it once on page load
        initCategorySortable();

        // Then, inside your repeater show callback:
        var categoryRepeater = $('#category-repeater').repeater({
            initEmpty: false,
            isFirstItemUndeletable: false,
            show: function() {
                $(this).slideDown();
                $(this).find('.select2').select2();
                updateRowNumbers('category-table');

                // Re-init sortable so the new row is draggable
                initCategorySortable();
            },
            hide: function(deleteElement) {
                if (confirm('Delete this row?')) {
                    $(this).slideUp(deleteElement);
                    setTimeout(() => updateRowNumbers('category-table'), 300);
                }
            }
        });


        // Item Repeater
        var itemRepeater = $('#item-repeater').repeater({
            initEmpty: false,
            isFirstItemUndeletable: false,
            show: function() {
                $(this).slideDown();
                $(this).find('.select2').select2();
                updateRowNumbers('item-table');
            },
            hide: function(deleteElement) {
                if (confirm('Delete this row?')) {
                    $(this).slideUp(deleteElement);
                    setTimeout(() => updateRowNumbers('item-table'), 300);
                }
            }
        });


    });
</script>
<script>
    $(document).ready(function() {
        let categoryLineCount = 3;
        let itemLineCount = 1;

        // Add Category Line
        $('#add-category-line').on('click', function() {
            const newRow = `
                    <tr class="category-row">
                        <td>
                            <span class="text-muted me-2 drag-handle"
                                style="cursor: move; font-size: 18px;"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" color="#babec5" width="16px"
                                    height="16px" focusable="false" aria-hidden="true"
                                    data-testid="nine-dots-account-line-2">
                                    <path fill="currentColor"
                                        d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                    </path>
                                    <path fill="currentColor"
                                        d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0">
                                    </path>
                                    <path fill="currentColor"
                                        d="m10.636 10.565-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M4.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M10.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                    </path>
                                    <path fill="currentColor"
                                        d="m10.636 16.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 16.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                    </path>
                                </svg></span>

                        </td>
                        <td class="qbo-line-number">${++categoryLineCount}</td>

                        <td>
                            <select name="category[${categoryLineCount}][account_id]" class="form-control category-account">
                                <option value="">{{ __('Select account') }}</option>
                                @foreach ($chartAccounts as $id => $account)
                                    <option value="{{ $id }}">{{ $account }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><textarea name="category[${categoryLineCount}][description]" class="form-control" rows="1"></textarea></td>
                        <td><input type="number" name="category[${categoryLineCount}][amount]" class="form-control category-amount text-end" step="0.01" value="0.00"></td>
                        <td class="text-center text-center"><input type="checkbox" name="category[${categoryLineCount}][billable]" class="qbo-checkbox form-check-input" value="1"></td>
                        <td class="text-center"><input type="checkbox " name="category[${categoryLineCount}][tax]" class="qbo-checkbox category-tax "></td>
                        <td>
                            <select name="category[${categoryLineCount}][customer_id]" class="form-control customer-select">
                                <option value="">-</option>
                                
                                @foreach ($customers as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="row-actions"><i class="fas fa-trash delete-row"></i></td>
                    </tr>
                `;
            $('#category-tbody').append(newRow);
            updateLineNumbers();
        });

        // Add Item Line
        $('#add-item-line').on('click', function() {
            const newRow = `
                    <tr class="item-row">
                        <td>
                            <span class="text-muted me-2 drag-handle"
                                style="cursor: move; font-size: 18px;"><svg
                                    xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" color="#babec5" width="16px"
                                    height="16px" focusable="false" aria-hidden="true"
                                    data-testid="nine-dots-account-line-2">
                                    <path fill="currentColor"
                                        d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                    </path>
                                    <path fill="currentColor"
                                        d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0">
                                    </path>
                                    <path fill="currentColor"
                                        d="m10.636 10.565-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M4.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M10.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                    </path>
                                    <path fill="currentColor"
                                        d="m10.636 16.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 16.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                    </path>
                                </svg></span>

                        </td>
                        <td class="qbo-line-number">${++itemLineCount}</td>
                        <td>
                            <select name="items[${itemLineCount}][product_id]" class="form-control item-product">
                                <option value="">{{ __('Select Item') }}</option>
                                @foreach ($product_services as $id => $product)
                                    <option value="{{ $id }}">{{ $product }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><textarea name="items[${itemLineCount}][description]" class="form-control item-description" rows="1"></textarea></td>
                        <td><input type="number" name="items[${itemLineCount}][quantity]" class="form-control item-qty" step="1" value="1"></td>
                        <td><input type="number" name="items[${itemLineCount}][price]" class="form-control item-rate" step="0.01" value="0.00"></td>
                        <td><input type="number" name="items[${itemLineCount}][amount]" class="form-control item-amount" step="0.01" value="0.00" readonly></td>
                        <td class="text-center"><input type="checkbox" name="items[${itemLineCount}][billable]" class="qbo-checkbox form-check-input"></td>
                        <td class="text-center"><input type="checkbox" name="items[${itemLineCount}][tax]" class="qbo-checkbox item-tax form-check-input"></td>
                        <td>
                            <select name="items[${itemLineCount}][customer_id]" class="form-control customer-select">
                                <option value="">-</option>
                                @foreach ($customers as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="row-actions"><i class="fas fa-trash delete-row"></i></td>
                    </tr>
                `;
            $('#item-tbody').append(newRow);
            updateLineNumbers();
        });

        // Delete Row
        $(document).on('click', '.delete-row', function() {
            $(this).closest('tr').remove();
            updateLineNumbers();
            calculateTotal();
        });

        // Clear Lines
        $('#clear-category-lines').on('click', function() {
            $('#category-tbody').empty();
            categoryLineCount = 0;
            calculateTotal();
        });

        $('#clear-item-lines').on('click', function() {
            $('#item-tbody').empty();
            itemLineCount = 0;
            calculateTotal();
        });

        // Update Line Numbers
        function updateLineNumbers() {
            $('.category-row').each(function(index) {
                $(this).find('.qbo-line-number').text(index + 1);
            });
            $('.item-row').each(function(index) {
                $(this).find('.qbo-line-number').text(index + 1);
            });
        }

        // Calculate Item Amount (Qty × Rate)
        $(document).on('input', '.item-qty, .item-rate', function() {
            const row = $(this).closest('tr');
            const qty = parseFloat(row.find('.item-qty').val()) || 0;
            const rate = parseFloat(row.find('.item-rate').val()) || 0;
            row.find('.item-amount').val((qty * rate).toFixed(2));
            calculateTotal();
        });

        // Category Amount Change
        $(document).on('input', '.category-amount', function() {
            calculateTotal();
        });
        $(document).on('input', '.item-amount', function() {
            calculateTotal();
        });

        // Calculate Total
        function calculateTotal() {
            let subtotal = 0;
            $('.category-amount').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            $('.item-amount').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });

            $('#subtotal').val(subtotal.toFixed(2));
            $('#total').val(subtotal.toFixed(2));
            $('#subtotal-display').text('$' + subtotal.toFixed(2));
            $('#total-display').text('$' + subtotal.toFixed(2));
            $('.grand-total-display').text('$' + subtotal.toFixed(2));
            $('#total').val(subtotal.toFixed(2));
        }

        // File Upload Display
        $('#attachments').on('change', function() {
            const files = this.files;
            $('#attachment-list').empty();
            for (let i = 0; i < files.length; i++) {
                $('#attachment-list').append(
                    `<div class="small text-muted"><i class="fas fa-paperclip"></i> ${files[i].name}</div>`
                );
            }
        });

        // Product Auto-fill - loads product details when selected
        $(document).on('change', '.item-product', function() {
            const productId = $(this).val();
            const currentRow = $(this).closest('tr');

            if (!productId) return;

            $.ajax({
                url: '{{ route('invoice.product') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    product_id: productId
                },
                success: function(response) {
                    // Parse JSON if it's a string
                    const data = typeof response === 'string' ? JSON.parse(response) :
                        response;

                    if (data.product) {
                        // Get description (or name if description doesn't exist)
                        const description = data.product.description || data.product.name ||
                            '';
                        currentRow.find('.item-description').val(description);

                        // Get purchase price
                        const rate = parseFloat(data.product.purchase_price) || 0;
                        currentRow.find('.item-rate').val(rate.toFixed(2));

                        // Set quantity to 1
                        const qty = 1;
                        currentRow.find('.item-qty').val(qty);

                        // Calculate amount (qty × rate)
                        const amount = qty * rate;
                        currentRow.find('.item-amount').val(amount.toFixed(2));

                        // Recalculate subtotal and grand total
                        calculateTotal();
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching product:', xhr);
                    show_toastr('Error',
                        'Failed to load product details. Please try again.', 'error');
                }
            });
        });

        // Form Submit
        $('#bill-form').on('submit', function(e) {
            e.preventDefault();
            $('.btn-qbo-save').prop('disabled', true).text('{{ __('Updating...') }}');

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status === 'success') {
                        $('#commonModalOver').modal('hide');
                        if (typeof show_toastr === 'function') {
                            show_toastr('success',
                                '{{ __('Expense updated successfully') }}', 'success');
                        }
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        show_toastr('success', response.message ||
                            '{{ __('Expense updated successfully') }}', 'success');
                        $('.btn-qbo-save').prop('disabled', false).text(
                            '{{ __('Save') }}');
                    }
                },
                error: function(xhr) {
                    let message = '{{ __('Error updating expense') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    show_toastr('error', message, 'error');

                    $('.btn-qbo-save').prop('disabled', false).text('{{ __('Save') }}');
                }
            });
        });

        // Initialize
        calculateTotal();
    });
</script>

<div class="row">
    <div class="d-flex justify-content-between align-items-center border-bottom"
        style="
                                font-size: 15px;
                                font-weight: 600;
                                height: 55px;
                                background: #f4f5f8;
                                position: fixed;
                                top: 0;
                                left: 0;
                                right: 0;
                                z-index: 999;
                                padding: 0 10px;
                            ">
        <div class="TrowserHeader d-flex align-items-center">
            <a href="#" class="text-dark me-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false"
                    aria-hidden="true" class="">
                    <path fill="currentColor"
                        d="M13.007 7a1 1 0 0 0-1 1L12 12a1 1 0 0 0 1 1l3.556.006a1 1 0 0 0 0-2L14 11l.005-3a1 1 0 0 0-.998-1">
                    </path>
                    <path fill="currentColor"
                        d="M19.374 5.647A8.94 8.94 0 0 0 13.014 3H13a8.98 8.98 0 0 0-8.98 8.593l-.312-.312a1 1 0 0 0-1.416 1.412l2 2a1 1 0 0 0 1.414 0l2-2a1 1 0 0 0-1.412-1.416l-.272.272A6.984 6.984 0 0 1 13 5h.012A7 7 0 0 1 13 19h-.012a7 7 0 0 1-4.643-1.775 1 1 0 1 0-1.33 1.494A9 9 0 0 0 12.986 21H13a9 9 0 0 0 6.374-15.353">
                    </path>
                </svg></a>
            <h5 class="mb-0" style="font-size: 1.2rem;">Expense</h5>
        </div>
        <div class="TrowserHeader d-flex align-items-center">
            <button type="button" class="header-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" color="currentColor"
                    width="24px" height="24px" focusable="false" aria-hidden="true" class="">
                    <path fill="currentColor"
                        d="M14.35 2a1 1 0 0 1 0 2H6.49a2.54 2.54 0 0 0-2.57 2.5v7A2.54 2.54 0 0 0 6.49 16h1.43a1 1 0 0 1 1 1v1.74l2.727-2.48c.184-.167.424-.26.673-.26h5.03a2.54 2.54 0 0 0 2.57-2.5v-4a1 1 0 0 1 2 0v4a4.54 4.54 0 0 1-4.57 4.5h-4.643l-4.114 3.74A1.002 1.002 0 0 1 6.92 21v-3h-.43a4.54 4.54 0 0 1-4.57-4.5v-7A4.54 4.54 0 0 1 6.49 2zm6.414.6.725.726c.79.791.79 2.074 0 2.865l-5.812 5.794c-.128.128-.29.219-.465.263l-2.9.721q-.121.03-.247.031a.998.998 0 0 1-.969-1.244l.73-2.9a1 1 0 0 1 .263-.463L17.9 2.6a2.027 2.027 0 0 1 2.864 0m-1.412 1.413-.763.724L13.7 9.612l-.255 1.015 1.016-.252 5.616-5.6V4.74z">
                    </path>
                </svg>
                Feedback
            </button>
            <button type="button" class="header-action-btn">
                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="" width="24px"
                    height="24px" fill="currentColor">
                    <path d="M12.024 7.982h-.007a4 4 0 100 8 4 4 0 10.007-8zm-.006 6a2 2 0 01.002-4 2 2 0 110 4h-.002z">
                    </path>
                    <path
                        d="M20.444 13.4l-.51-.295a7.557 7.557 0 000-2.214l.512-.293a2.005 2.005 0 00.735-2.733l-1-1.733a2.005 2.005 0 00-2.731-.737l-.512.295a8.071 8.071 0 00-1.915-1.113v-.59a2 2 0 00-2-2h-2a2 2 0 00-2 2v.6a8.016 8.016 0 00-1.911 1.1l-.52-.3a2 2 0 00-2.725.713l-1 1.73a2 2 0 00.728 2.733l.509.295a7.75 7.75 0 00-.004 2.22l-.51.293a2 2 0 00-.738 2.73l1 1.732a2 2 0 002.73.737l.513-.295A8.07 8.07 0 009.01 19.39v.586a2 2 0 002 2h2a2 2 0 002-2V19.4a8.014 8.014 0 001.918-1.107l.51.3a2 2 0 002.734-.728l1-1.73a2 2 0 00-.728-2.735zm-2.593-2.8a5.8 5.8 0 010 2.78 1 1 0 00.472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 00-1.187.137 6.02 6.02 0 01-2.4 1.387 1 1 0 00-.716.957v1.294h-2v-1.293a1 1 0 00-.713-.96 5.991 5.991 0 01-2.4-1.395 1.006 1.006 0 00-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 00.475-1.1 5.945 5.945 0 01-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 00-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a.97.97 0 00.186.063.9.9 0 00.2.04c.02 0 .039.011.059.011a1.08 1.08 0 00.136-.025.98.98 0 00.17-.032A1.02 1.02 0 007.7 7.75a.986.986 0 00.157-.1c.015-.013.034-.017.048-.03a6.011 6.011 0 012.4-1.39.453.453 0 00.049-.026.938.938 0 00.183-.1.87.87 0 00.15-.1.953.953 0 00.122-.147c.038-.049.071-.1.1-.156a1.01 1.01 0 00.055-.173.971.971 0 00.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 00.713.96c.897.273 1.72.75 2.4 1.395a1 1 0 001.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 00-.465 1.096z">
                    </path>
                </svg>

            </button>
            <div class="TrowserHeader">
                <a href="#" class="text-dark me-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" color="currentColor" width="24px" height="24px" focusable="false"
                        aria-hidden="true" class="">
                        <path fill="currentColor"
                            d="M12 15a1 1 0 1 0 0 2 1 1 0 0 0 0-2M15 10a3.006 3.006 0 0 0-3-3 3 3 0 0 0-2.9 2.27 1 1 0 1 0 1.937.494A1.02 1.02 0 0 1 12 9a1.006 1.006 0 0 1 1 1c0 .013.007.024.007.037s-.007.023-.007.036a.5.5 0 0 1-.276.447l-1.172.584A1 1 0 0 0 11 12v1a1 1 0 1 0 2 0v-.383l.619-.308a2.52 2.52 0 0 0 1.381-2.3z">
                        </path>
                        <path fill="currentColor"
                            d="M19.082 4.94A9.93 9.93 0 0 0 12.016 2H12a10 10 0 0 0-.016 20H12a10 10 0 0 0 7.082-17.06m-1.434 12.725A7.94 7.94 0 0 1 12 20h-.013A8 8 0 1 1 12 4h.012a8 8 0 0 1 5.636 13.665">
                        </path>
                    </svg></a>

            </div>
            <div class="TrowserHeader">
                <a href="{{ route('expense.index') }}" class="text-dark me-2"><svg xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24" color="currentColor" width="24px" height="24px"
                        focusable="false" aria-hidden="true" class="">
                        <path fill="currentColor"
                            d="m13.432 11.984 5.3-5.285a1 1 0 1 0-1.412-1.416l-5.3 5.285-5.285-5.3A1 1 0 1 0 5.319 6.68l5.285 5.3L5.3 17.265a1 1 0 1 0 1.412 1.416l5.3-5.285L17.3 18.7a1 1 0 1 0 1.416-1.412z">
                        </path>
                    </svg></a>

            </div>

        </div>
    </div>
    {{ Form::open(['route' => ['expense.update', $expense->id], 'method' => 'PUT', 'class' => 'w-100', 'style' => 'padding: 30px 30px; background: #ffffff;', 'id' => 'bill-form']) }}
    <div class="col-12">
        <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
        <input type="hidden" name="expense_id" value="{{ $expense->id }}">
        <div class="card">
            <div class="card-body" style="background:#f4f5f8;">

                <!-- ========== TOP SECTION ========== -->
                <div class="row align-items-center">

                    <!-- LEFT: PAYEE + RADIO -->
                    <div class="col-10">
                        <div class="row">
                            {{-- Single Payee Dropdown --}}
                            <div class="col-12 form-group mb-3">
                                <div class="row">
                                    <div class="col-3">
                                        <label for="payee_all" class="form-label">{{ __('Payee') }}</label>

                                        <select id="payee_all" name="payee" class="form-control select" required
                                            data-selected-payee="{{ $selected_payee ?? '' }}">
                                            <!-- selected_payee={{ $selected_payee ?? 'EMPTY' }} -->
                                            <option value="">Who did you pay?</option>

                                            {{-- Employees --}}
                                            <optgroup label="employee">
                                                <option value="__add_employee" data-create-type="employee"
                                                    data-create-url="{{ route('user.create') }}"
                                                    data-create-title="Add New Employee">
                                                    ➕ Add New Employee
                                                </option>
                                                @foreach ($employees as $id => $name)
                                                    <option value="employee_{{ $id }}"
                                                        {{ ($selected_payee ?? '') == 'employee_'.$id ? 'selected' : '' }}>
                                                        Employee - {{ $name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>

                                            {{-- Customers --}}
                                            <optgroup label="customer">
                                                <option value="__add_customer" data-create-type="customer"
                                                    data-create-url="{{ route('customer.create') }}"
                                                    data-create-title="Add New Customer">
                                                    ➕ Add New Customer
                                                </option>
                                                @foreach ($customers as $id => $name)
                                                    <option value="customer_{{ $id }}"
                                                        {{ ($selected_payee ?? '') == 'customer_'.$id ? 'selected' : '' }}>
                                                        Customer - {{ $name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>

                                            {{-- Vendors --}}
                                            <optgroup label="vendor">
                                                <option value="__add_vendor" data-create-type="vendor"
                                                    data-create-url="{{ route('vender.create') }}"
                                                    data-create-title="Add New Vendor">
                                                    ➕ Add New vendor
                                                </option>
                                                @foreach ($venders as $id => $name)
                                                    <option value="vendor_{{ $id }}"
                                                        {{ ($selected_payee ?? '') == 'vendor_'.$id ? 'selected' : '' }}>
                                                        Vendor - {{ $name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>

                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            {{ Form::label('account_id', __('Payment Account'), ['class' => 'form-label']) }}
                                            {{ Form::select('account_id', $accounts, $bankAccount->id ?? null, [
                                                'class' => 'form-control',
                                                'required' => 'required',
                                                'data-create-url' => route('bank-account.create'),
                                                'data-create-title' => __('Create New Account'),
                                            ]) }}
                                        </div>
                                        {{-- <div class="" style="margin-bottom: 0.7rem;">
                                            <span class="small text-secondary fw-normal text-nowrap">Balance
                                                $1,201.00</span>
                                        </div> --}}
                                    </div>
                                </div>
                            </div>


                        </div>

                    </div>

                    <!-- RIGHT: AMOUNT (screenshot style) -->
                    <div class="col-2 text-end" style="margin-top: -34px;">
                        <div class="d-flex flex-column align-items-end">
                            <label class="form-label mb-0" style="color:#6b6c72;">AMOUNT</label>
                            <p class="h3 mb-0 grand-total-display" style="font-size:36px;font-weight:900;"=>$0.00</p>
                        </div>
                    </div>
                </div>

                <!-- ========== BOTTOM FIELDS ========== -->
                <div class="row " style="margin-top: -20px;">
                    <div class="row">
                        <div class="col-6 d-flex g-3" style="gap: 15px;">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {{ Form::label('payment_date', __('Payment Date'), ['class' => 'form-label']) }}
                                    {{ Form::date('payment_date', $expense->bill_date, ['class' => 'form-control', 'required' => 'required']) }}
                                </div>
                            </div>


                            <div class="col-md-3">
                                <div class="form-group">
                                    {{ Form::label('category_id', __('Payment Method'), ['class' => 'form-label']) }}
                                    {{ Form::select('category_id', $category, $expense->category_id, [
                                        'class' => 'form-control select',
                                        'data-create-url' => route('product-category.create'),
                                        'data-create-title' => __('Create New Category'),
                                    ]) }}
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="d-flex flex-column">
                                        <label for="ref_no" class="qb-label" style="margin-bottom: 5px;">Ref
                                            no.</label>
                                        <input type="text" id="ref_no" name="reference_no" value="{{ $expense->ref_number }}" class="form-control qb-input w-100">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>



    <div class="containerbox mb-3" style="background-color: white;">
        <!-- ======================== CATEGORY TABLE ======================== -->
        <div class="col-12">
            <div class="custom-accordion">
                <div class="accordion-header d-flex " onclick="toggleAccordion(this)">
                    <div class="accordion-arrow"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" color="currentColor" width="24px" height="24px"
                            focusable="false" aria-hidden="true"
                            class="AccordionItemHeader-iconLeft-3757853 AccordionItemHeader-chevronLeftExpanded-3757853 AccordionItemHeader-chevron-3757853 AccordionItemHeader-signifierLabel-3757853">
                            <path fill="currentColor"
                                d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a1 1 0 0 1-.706.292">
                            </path>
                        </svg></div>
                    <h5 class="mb-0">{{ __('Category details') }}</h5>
                    <!-- arrow -->
                </div>
                <div class="accordion-content" style="display: block;">
                    <div class="lineitem-toolbar" style="margin-top: -56px;">
                        <div class="toolbar-buttons">

                            <!-- Export to Excel -->
                            <button class="toolbar-btn" type="button" title="Export to Excel">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="20px" height="20px" focusable="false"
                                    aria-hidden="true" class="">
                                    <path fill="currentColor"
                                        d="m18.016 2.01-12-.019a3 3 0 0 0-3 3l-.022 14a3 3 0 0 0 3 3l12 .018a3 3 0 0 0 3-3 1 1 0 1 0-2 0 1 1 0 0 1-1 1l-12-.018a1 1 0 0 1-1-1l.022-14a1 1 0 0 1 1-1l12 .018a1 1 0 0 1 1 1L19 8.961a1 1 0 0 0 2 0l.011-3.954a3 3 0 0 0-2.995-2.998">
                                    </path>
                                    <path fill="currentColor"
                                        d="M16.3 17.7a1 1 0 0 0 1.414 0l2.995-3.005a1 1 0 0 0 0-1.414l-3-2.995a1.002 1.002 0 1 0-1.42 1.414l1.3 1.291h-2.647A4.946 4.946 0 0 0 10 17.971a1 1 0 0 0 1 .993h.006A1 1 0 0 0 12 17.958a2.946 2.946 0 0 1 2.941-2.965h2.646l-1.287 1.29a1 1 0 0 0 0 1.417">
                                    </path>
                                </svg>
                            </button>

                            <!-- Paste line items -->
                            <button class="toolbar-btn" type="button" title="Paste line items">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="20px" height="20px" focusable="false"
                                    aria-hidden="true" class="">
                                    <path fill="currentColor" fill-rule="evenodd"
                                        d="M10 4a1 1 0 0 0-1 1v1h4V5a1 1 0 0 0-1-1zm5 2v1a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h3.283A3.3 3.3 0 0 1 9 18.65v-6.3C9 10.5 10.5 9 12.35 9H17V7a1 1 0 0 0-1-1zm4 3.283a3.35 3.35 0 0 1 2 3.067v6.3C21 20.5 19.5 22 17.65 22H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1.172A3 3 0 0 1 10 2h2a3 3 0 0 1 2.828 2H16a3 3 0 0 1 3 3zM17.65 20A1.35 1.35 0 0 0 19 18.65v-6.3A1.35 1.35 0 0 0 17.65 11h-5.3A1.35 1.35 0 0 0 11 12.35v6.3c0 .746.604 1.35 1.35 1.35zM12 14a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2h-4a1 1 0 0 1-1-1m0 3a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2h-2a1 1 0 0 1-1-1"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>

                            <!-- Column hide settings -->
                            <button class="toolbar-btn" type="button" title="Column Settings">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class=""
                                    width="20px" height="20px" fill="currentColor">
                                    <path
                                        d="M12.024 7.982h-.007a4 4 0 100 8 4 4 0 10.007-8zm-.006 6a2 2 0 01.002-4 2 2 0 110 4h-.002z">
                                    </path>
                                    <path
                                        d="M20.444 13.4l-.51-.295a7.557 7.557 0 000-2.214l.512-.293a2.005 2.005 0 00.735-2.733l-1-1.733a2.005 2.005 0 00-2.731-.737l-.512.295a8.071 8.071 0 00-1.915-1.113v-.59a2 2 0 00-2-2h-2a2 2 0 00-2 2v.6a8.016 8.016 0 00-1.911 1.1l-.52-.3a2 2 0 00-2.725.713l-1 1.73a2 2 0 00.728 2.733l.509.295a7.75 7.75 0 00-.004 2.22l-.51.293a2 2 0 00-.738 2.73l1 1.732a2 2 0 002.73.737l.513-.295A8.07 8.07 0 009.01 19.39v.586a2 2 0 002 2h2a2 2 0 002-2V19.4a8.014 8.014 0 001.918-1.107l.51.3a2 2 0 002.734-.728l1-1.73a2 2 0 00-.728-2.735zm-2.593-2.8a5.8 5.8 0 010 2.78 1 1 0 00.472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 00-1.187.137 6.02 6.02 0 01-2.4 1.387 1 1 0 00-.716.957v1.294h-2v-1.293a1 1 0 00-.713-.96 5.991 5.991 0 01-2.4-1.395 1.006 1.006 0 00-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 00.475-1.1 5.945 5.945 0 01-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 00-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a.97.97 0 00.186.063.9.9 0 00.2.04c.02 0 .039.011.059.011a1.08 1.08 0 00.136-.025.98.98 0 00.17-.032A1.02 1.02 0 007.7 7.75a.986.986 0 00.157-.1c.015-.013.034-.017.048-.03a6.011 6.011 0 012.4-1.39.453.453 0 00.049-.026.938.938 0 00.183-.1.87.87 0 00.15-.1.953.953 0 00.122-.147c.038-.049.071-.1.1-.156a1.01 1.01 0 00.055-.173.971.971 0 00.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 00.713.96c.897.273 1.72.75 2.4 1.395a1 1 0 001.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 00-.465 1.096z">
                                    </path>
                                </svg>
                            </button>

                        </div>
                    </div>

                    <!-- block = open by default -->
                    <div class="card repeater" id="category-repeater">
                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table align-middle" id="category-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="1%"></th>
                                            <th width="1%">#</th>
                                            <th width="20%">{{ __('CATEGORY') }}</th>
                                            <th width="25%">{{ __('DESCRIPTION') }}</th>
                                            <th width="12%" class="text-end">{{ __('AMOUNT') }}</th>
                                            <th width="5%" class="text-center">{{ __('BILLABLE') }}</th>
                                            <th width="5%" class="text-center">{{ __('TAX') }}</th>
                                            <th width="13%">{{ __('CUSTOMER') }}</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-repeater-list="categories" id="category-tbody">
                                        @if (!empty($categoriesAccountData) && count($categoriesAccountData) > 0)
                                            @foreach ($categoriesAccountData as $i => $category)
                                                <tr data-repeater-item class="category-row">
                                                    <td>
                                                        <input type="hidden" name="categories[${i}][id]"
                                                            value="{{ $category['id'] }}">
                                                        <span class="text-muted me-2 drag-handle"
                                                            style="cursor: move; font-size: 18px;"><svg
                                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" color="#babec5" width="16px"
                                                                height="16px" focusable="false" aria-hidden="true"
                                                                data-testid="nine-dots-account-line-2">
                                                                <path fill="currentColor"
                                                                    d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="m10.636 10.565-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M4.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M10.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="m10.636 16.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 16.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                </path>
                                                            </svg></span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="row-number qbo-line-number">{{ $i + 1 }}</span>
                                                    </td>
                                                    <td>
                                                        <select name="categories[{{ $i }}][account_id]"
                                                            class="form-control select2 category-select category-account">
                                                            <option value="">{{ __('Select account') }}</option>
                                                            @foreach ($chartAccounts as $id => $account)
                                                                <option value="{{ $id }}"
                                                                    {{ ($category['chart_account_id'] ?? '') == $id ? 'selected' : '' }}>
                                                                    {{ $account }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        {{ Form::textarea("categories[{$i}][description]", $category['description'] ?? '', ['class' => 'form-control', 'rows' => 1]) }}
                                                    </td>
                                                    <td>
                                                        {{ Form::number("categories[{$i}][amount]", $category['amount'] ?? 0, ['class' => 'form-control text-end category-amount', 'step' => '0.01']) }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ Form::checkbox("categories[{$i}][billable][]", 1, ($category['billable'] ?? 0) == 1, ['class' => 'form-check-input']) }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ Form::checkbox("categories[{$i}][tax]", 1, ($category['tax'] ?? 0) == 1, ['class' => 'form-check-input category-tax']) }}
                                                    </td>
                                                    <td>
                                                        <select name="categories[{{ $i }}][customer_id]"
                                                            class="form-control select2 customer-select">
                                                            <option value="">-</option>
                                                            @foreach ($customers as $id => $name)
                                                                <option value="{{ $id }}"
                                                                    {{ ($category['customer_id'] ?? '') == $id ? 'selected' : '' }}>
                                                                    {{ $name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="row-actions">
                                                        <i class="fas fa-trash delete-row"></i>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif

                                    </tbody>
                                </table>
                                <div class="txp-capability-gridButtons-Uh9M+ d-flex"
                                    style="margin-left: 8px;margin-top: 15px;gap: 7px;background-color: white;">
                                    <div>
                                        <button type="button" id="add-category-line"
                                            style="background-color: white;border-radius: 5px;background: #fff;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 14px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;white-space: nowrap;"
                                            class="qbo-add-line-btn idsTSButton idsF Button-button-6a785d2 Button-size-medium-6a785d2 Button-purpose-standard-6a785d2 Button-priority-primary-6a785d2">
                                            <span class="Button-label-6a785d2">Add line</span>
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" id="clear-category-lines"
                                            style="background-color: white;border-radius: 5px;background: #fff;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 12px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;white-space: nowrap;"
                                            class="qbo-clear-btn idsTSButton idsF Button-button-6a785d2 Button-size-medium-6a785d2 Button-purpose-standard-6a785d2 Button-priority-primary-6a785d2">
                                            <span class="Button-label-6a785d2">Clear all lines</span>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>


                        {{-- <div class="card-footer bg-transparent d-flex justify-content-between">
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-category-lines">
                                    {{ __('Add lines') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clear-category-lines">
                                    {{ __('CLEAR ALL LINES') }}
                                </button>
                            </div>

                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
        <!-- ======================== ITEM TABLE ======================== -->
        <div class="col-12">
            <div class="custom-accordion">
                <div class="accordion-header d-flex" onclick="toggleAccordion(this)">
                    <div class="accordion-arrow" onclick="toggleAccordion(this)"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" color="currentColor" width="24px" height="24px"
                            focusable="false" aria-hidden="true"
                            class="AccordionItemHeader-iconLeft-3757853 AccordionItemHeader-chevronLeftExpanded-3757853 AccordionItemHeader-chevron-3757853 AccordionItemHeader-signifierLabel-3757853">
                            <path fill="currentColor"
                                d="M9.009 19.013a1 1 0 0 1-.709-1.708l5.3-5.285-5.281-5.3a1 1 0 1 1 1.416-1.413l5.991 6.01a1 1 0 0 1 0 1.413l-6.011 5.991a1 1 0 0 1-.706.292">
                            </path>
                        </svg></div> <!-- arrow -->
                    <h5 class="mb-0">{{ __('Item details') }}</h5>

                </div>
                <div class="accordion-content" style="display: block;">
                    <div class="lineitem-toolbar" style="margin-top: -56px;">
                        <div class="toolbar-buttons">

                            <!-- Export to Excel -->
                            <button class="toolbar-btn" type="button" title="Export to Excel">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="20px" height="20px" focusable="false"
                                    aria-hidden="true" class="">
                                    <path fill="currentColor"
                                        d="m18.016 2.01-12-.019a3 3 0 0 0-3 3l-.022 14a3 3 0 0 0 3 3l12 .018a3 3 0 0 0 3-3 1 1 0 1 0-2 0 1 1 0 0 1-1 1l-12-.018a1 1 0 0 1-1-1l.022-14a1 1 0 0 1 1-1l12 .018a1 1 0 0 1 1 1L19 8.961a1 1 0 0 0 2 0l.011-3.954a3 3 0 0 0-2.995-2.998">
                                    </path>
                                    <path fill="currentColor"
                                        d="M16.3 17.7a1 1 0 0 0 1.414 0l2.995-3.005a1 1 0 0 0 0-1.414l-3-2.995a1.002 1.002 0 1 0-1.42 1.414l1.3 1.291h-2.647A4.946 4.946 0 0 0 10 17.971a1 1 0 0 0 1 .993h.006A1 1 0 0 0 12 17.958a2.946 2.946 0 0 1 2.941-2.965h2.646l-1.287 1.29a1 1 0 0 0 0 1.417">
                                    </path>
                                </svg>
                            </button>

                            <!-- Paste line items -->
                            <button class="toolbar-btn" type="button" title="Paste line items">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    color="currentColor" width="20px" height="20px" focusable="false"
                                    aria-hidden="true" class="">
                                    <path fill="currentColor" fill-rule="evenodd"
                                        d="M10 4a1 1 0 0 0-1 1v1h4V5a1 1 0 0 0-1-1zm5 2v1a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h3.283A3.3 3.3 0 0 1 9 18.65v-6.3C9 10.5 10.5 9 12.35 9H17V7a1 1 0 0 0-1-1zm4 3.283a3.35 3.35 0 0 1 2 3.067v6.3C21 20.5 19.5 22 17.65 22H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1.172A3 3 0 0 1 10 2h2a3 3 0 0 1 2.828 2H16a3 3 0 0 1 3 3zM17.65 20A1.35 1.35 0 0 0 19 18.65v-6.3A1.35 1.35 0 0 0 17.65 11h-5.3A1.35 1.35 0 0 0 11 12.35v6.3c0 .746.604 1.35 1.35 1.35zM12 14a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2h-4a1 1 0 0 1-1-1m0 3a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2h-2a1 1 0 0 1-1-1"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>

                            <!-- Column hide settings -->
                            <button class="toolbar-btn" type="button" title="Column Settings">
                                <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class=""
                                    width="20px" height="20px" fill="currentColor">
                                    <path
                                        d="M12.024 7.982h-.007a4 4 0 100 8 4 4 0 10.007-8zm-.006 6a2 2 0 01.002-4 2 2 0 110 4h-.002z">
                                    </path>
                                    <path
                                        d="M20.444 13.4l-.51-.295a7.557 7.557 0 000-2.214l.512-.293a2.005 2.005 0 00.735-2.733l-1-1.733a2.005 2.005 0 00-2.731-.737l-.512.295a8.071 8.071 0 00-1.915-1.113v-.59a2 2 0 00-2-2h-2a2 2 0 00-2 2v.6a8.016 8.016 0 00-1.911 1.1l-.52-.3a2 2 0 00-2.725.713l-1 1.73a2 2 0 00.728 2.733l.509.295a7.75 7.75 0 00-.004 2.22l-.51.293a2 2 0 00-.738 2.73l1 1.732a2 2 0 002.73.737l.513-.295A8.07 8.07 0 009.01 19.39v.586a2 2 0 002 2h2a2 2 0 002-2V19.4a8.014 8.014 0 001.918-1.107l.51.3a2 2 0 002.734-.728l1-1.73a2 2 0 00-.728-2.735zm-2.593-2.8a5.8 5.8 0 010 2.78 1 1 0 00.472 1.1l1.122.651-1 1.73-1.123-.65a1 1 0 00-1.187.137 6.02 6.02 0 01-2.4 1.387 1 1 0 00-.716.957v1.294h-2v-1.293a1 1 0 00-.713-.96 5.991 5.991 0 01-2.4-1.395 1.006 1.006 0 00-1.188-.142l-1.125.648-1-1.733 1.125-.647a1 1 0 00.475-1.1 5.945 5.945 0 01-.167-1.387c.003-.467.06-.933.17-1.388a1 1 0 00-.471-1.1l-1.123-.65 1-1.73 1.124.651c.019.011.04.01.06.02a.97.97 0 00.186.063.9.9 0 00.2.04c.02 0 .039.011.059.011a1.08 1.08 0 00.136-.025.98.98 0 00.17-.032A1.02 1.02 0 007.7 7.75a.986.986 0 00.157-.1c.015-.013.034-.017.048-.03a6.011 6.011 0 012.4-1.39.453.453 0 00.049-.026.938.938 0 00.183-.1.87.87 0 00.15-.1.953.953 0 00.122-.147c.038-.049.071-.1.1-.156a1.01 1.01 0 00.055-.173.971.971 0 00.04-.2c0-.018.012-.034.012-.053V3.981h2v1.294a1 1 0 00.713.96c.897.273 1.72.75 2.4 1.395a1 1 0 001.186.141l1.126-.647 1 1.733-1.125.647a1 1 0 00-.465 1.096z">
                                    </path>
                                </svg>
                            </button>

                        </div>
                    </div>
                    <!-- block = open by default -->
                    <div class="card repeater" id="item-repeater">
                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table align-middle" id="item-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="1%"></th>
                                            <th width="1%">#</th>
                                            <th width="20%">{{ __('PRODUCT/SERVICE') }}</th>
                                            <th width="20%">{{ __('DESCRIPTION') }}</th>
                                            <th width="8%" class="text-end">{{ __('QTY') }}</th>
                                            <th width="10%" class="text-end">{{ __('RATE') }}</th>
                                            <th width="10%" class="text-end">{{ __('AMOUNT') }}</th>
                                            <th width="5%" class="text-center">{{ __('BILLABLE') }}</th>
                                            <th width="5%" class="text-center">{{ __('TAX') }}</th>
                                            <th width="10%">{{ __('CUSTOMER') }}</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-repeater-list="items" id="item-tbody">
                                        @if (!empty($items) && count($items) > 0)
                                            @foreach ($items as $i => $item)
                                                <tr data-repeater-item class="item-row">
                                                    <td>
                                                        <input type="hidden" name="items[${i}][id]"
                                                            value="{{ $item['id'] }}">
                                                        <span class="text-muted me-2 drag-handle"
                                                            style="cursor: move; font-size: 18px;"><svg
                                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" color="#babec5" width="16px"
                                                                height="16px" focusable="false" aria-hidden="true"
                                                                data-testid="nine-dots-account-line-2">
                                                                <path fill="currentColor"
                                                                    d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="m10.636 4.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 4.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M16.636 4.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.071-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M10.636 10.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="m10.636 10.565-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.729 0M4.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 10.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M10.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                </path>
                                                                <path fill="currentColor"
                                                                    d="m10.636 16.565-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M4.636 16.565l-.071.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.729 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0M16.636 16.565l-.07.071a1.93 1.93 0 0 0 0 2.728l.07.07a1.93 1.93 0 0 0 2.728 0l.07-.07a1.93 1.93 0 0 0 0-2.728l-.07-.07a1.93 1.93 0 0 0-2.728 0">
                                                                </path>
                                                            </svg></span>

                                                    </td>
                                                    <td>
                                                        <span
                                                            class="row-number qbo-line-number">{{ $i + 1 }}</span>
                                                    </td>
                                                    <td>
                                                        {{ Form::select("items[{$i}][item_id]", $product_services ?? [], $item['product_id'] ?? null, [
                                                            'class' => 'form-control select2 item-select item-product',
                                                            'placeholder' => 'Select Product/Service',
                                                        ]) }}
                                                    </td>
                                                    <td>
                                                        {{ Form::textarea("items[{$i}][description]", $item['description'] ?? '', [
                                                            'class' => 'form-control item-description',
                                                            'rows' => 1,
                                                        ]) }}
                                                    </td>
                                                    <td>
                                                        {{ Form::number("items[{$i}][quantity]", $item['quantity'] ?? 1, [
                                                            'class' => 'form-control text-center item-qty',
                                                            'min' => 1,
                                                        ]) }}
                                                    </td>
                                                    <td>
                                                        {{ Form::number("items[{$i}][price]", $item['price'] ?? 0, [
                                                            'class' => 'form-control text-end item-rate',
                                                            'step' => '0.01',
                                                            'placeholder' => '0.00',
                                                        ]) }}
                                                    </td>

                                                    <td>
                                                        {{ Form::number("items[{$i}][amount]", $item['line_total'] ?? 0, ['class' => 'form-control text-end item-amount', 'step' => '0.01', 'readonly' => true]) }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ Form::checkbox("items[{$i}][billable][]", 1, ($item['billable'] ?? 0) == 1, ['class' => 'form-check-input']) }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ Form::checkbox("items[{$i}][tax]", 1, ($item['tax'] ?? 0) == 1, ['class' => 'form-check-input item-tax']) }}
                                                    </td>

                                                    <td>
                                                        <select name="items[{{ $i }}][customer_id]"
                                                            class="form-control select2 customer-select">
                                                            <option value="">-</option>
                                                            @foreach ($customers as $id => $name)
                                                                <option value="{{ $id }}"
                                                                    {{ ($item['customer_id'] ?? '') == $id ? 'selected' : '' }}>
                                                                    {{ $name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="row-actions">
                                                        <i class="fas fa-trash delete-row"></i>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                                <div class="txp-capability-gridButtons-Uh9M+ d-flex"
                                    style="margin-left: 8px;margin-top: 15px;gap: 7px;background-color: white;">
                                    <div>
                                        <button type="button" id="add-item-line"
                                            style="background-color: white;border-radius: 5px;background: #fff;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 14px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;white-space: nowrap;"
                                            class="qbo-add-line-btn idsTSButton idsF Button-button-6a785d2 Button-size-medium-6a785d2 Button-purpose-standard-6a785d2 Button-priority-primary-6a785d2">
                                            <span class="Button-label-6a785d2">Add line</span>
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" id="clear-item-lines"
                                            style="background-color: white;border-radius: 5px;background: #fff;border: 2px solid #8D9096;color: #393A3D;font-weight: 600;padding: 0px 12px;border-radius: 4px;margin-left: 10px;cursor: pointer;font-size: 14px;white-space: nowrap;"
                                            class="qbo-clear-btn idsTSButton idsF Button-button-6a785d2 Button-size-medium-6a785d2 Button-purpose-standard-6a785d2 Button-priority-primary-6a785d2">
                                            <span class="Button-label-6a785d2">Clear all lines</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
                <div class="card-footer bg-transparent ">
                    <div class="text-end d-flex justify-content-end"
                        style="margin-top: -5px;margin-right: 135px;font-size: 19px;gap: 23px;position: relative;top: -46px;left: 18px;width: -38px;">
                        <div style="margin-right: 16px;">
                            <strong>{{ __('Total') }}:</strong>
                        </div>
                        <div>
                            <span class="h5 text-primary grand-total-display">0.00</span>
                            <input type="hidden" name="total" id="total" value="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ======================== MEMO AND ATTACHMENT TABLE ======================== -->
        <div class="row" style="padding:20px;">

            <div class="col-md-3">
                <div class="form-group">
                    <label for="bill_memo" class="form-label">{{ __('Memo') }}</label>
                    {{-- Using Form::textarea for the Memo field --}}
                    {{ Form::textarea('memo', $expense->note, [
                        'class' => 'form-control',
                        'rows' => '5',
                        'id' => 'bill_memo',
                        'maxlength' => '4000',
                        'placeholder' => '' /* Placeholder is empty as per QBO design */,
                    ]) }}
                </div>
            </div>

            <div class="col-md-3">
                <style>
                    .attachments-header {
                        display: flex;
                        justify-content: flex-end;
                        align-items: center;
                        margin-bottom: 4px;
                        font-size: 13px;
                        color: #393a3d;
                    }

                    #attachments-list {
                        margin-bottom: 8px;
                    }

                    .attachment-row {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        padding: 6px 8px;
                        border: 1px solid #e4e4e7;
                        border-radius: 4px;
                        margin-bottom: 4px;
                        font-size: 13px;
                        background: #ffffff;
                    }

                    .attachment-row .form-check {
                        margin-bottom: 0;
                    }

                    body.theme-6 .form-check-input:checked {
                        background-color: #2ca01c;
                        border-color: #2ca01c;
                    }

                    .form-check {
                        padding-left: 2.75em !important;
                    }

                    .attachment-name {
                        flex: 1;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .attachment-size {
                        font-size: 12px;
                        color: #6b6c72;
                    }

                    .attachment-remove {
                        border: none;
                        background: none;
                        cursor: pointer;
                        padding: 0 4px;
                        font-size: 16px;
                        line-height: 1;
                        color: #6b6c72;
                    }

                    .attachment-remove:hover {
                        color: #e81500;
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
                        border-color: #2ca01c;
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
                </style>

                <script>
                    $(function() {
                        var attachLabel = @json(__('Attach to email'));
                        var maxFileSize = 20 * 1024 * 1024; // 20 MB

                        var $zone = $('#attachment-zone');
                        var $addLink = $('#attachment-add-link');
                        var $header = $('#attachments-header');
                        var $list = $('#attachments-list');
                        var $inputsContainer = $('#attachment-file-inputs');
                        var currentInput = null;

                        function updateSelectAllState() {
                            var $boxes = $list.find('.attachment-email');
                            var $checked = $boxes.filter(':checked');
                            $('#attachment_select_all').prop('checked',
                                $boxes.length > 0 && $boxes.length === $checked.length
                            );
                        }

                        function toggleHeader() {
                            if ($list.find('.attachment-row').length) {
                                $header.removeClass('d-none');
                            } else {
                                $header.addClass('d-none');
                                $('#attachment_select_all').prop('checked', false);
                            }
                        }

                        function createAttachmentInput() {
                            var $input = $('<input type="file" class="single-attachment-input d-none">');
                            $inputsContainer.append($input);
                            currentInput = $input;

                            $input.on('change', function() {
                                if (!this.files || !this.files.length) return;

                                var file = this.files[0];

                                if (file.size > maxFileSize) {
                                    alert('Max file size is 20 MB');
                                    $input.val('');
                                    return;
                                }

                                var rowId = 'att_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                                // bind the name now so Laravel gets an associative array: attachments[rowId]
                                $input.attr('name', 'attachments[' + rowId + ']');

                                var sizeKB = Math.round(file.size / 1024);

                                var $row = $(
                                    '<div class="attachment-row" data-row-id="' + rowId + '">' +
                                    '<span class="attachment-name">' + file.name + '</span>' +
                                    '<span class="attachment-size">' + sizeKB + ' KB</span>' +
                                    '<button type="button" class="attachment-remove" data-row-id="' + rowId +
                                    '">&times;</button>' +
                                    '</div>'
                                );


                                // move the actual file input into this row (so the file is submitted)
                                $row.append($input);
                                $list.append($row);

                                toggleHeader();
                                updateSelectAllState();

                                // prepare a fresh empty input for the next "Add attachment"
                                createAttachmentInput();
                            });
                        }

                        // first empty input
                        createAttachmentInput();

                        // clicking the link or the zone opens current file input
                        $addLink.on('click', function(e) {
                            e.preventDefault();
                            if (currentInput) currentInput.trigger('click');
                        });
                        $zone.on('click', function(e) {
                            if ($(e.target).is('#attachment-add-link') ||
                                $(e.target).closest('.attachment-row').length) {
                                return;
                            }
                            if (currentInput) currentInput.trigger('click');
                        });

                        // "Select All" checkbox
                        $('#attachment_select_all').on('change', function() {
                            var checked = $(this).is(':checked');
                            $list.find('.attachment-email').prop('checked', checked);
                        });

                        // single checkbox change updates select-all state
                        $(document).on('change', '.attachment-email', function() {
                            updateSelectAllState();
                        });

                        // remove one attachment (also removes its file input)
                        $(document).on('click', '.attachment-remove', function() {
                            var rowId = $(this).data('row-id');
                            var $row = $list.find('.attachment-row[data-row-id="' + rowId + '"]');
                            $row.remove();
                            toggleHeader();
                            updateSelectAllState();
                        });
                    });
                </script>

                <div class="info-field">
                    <label>{{ __('Attachments') }}</label>

                    {{-- header with "Select all" - hidden until first file is added --}}
                    {{-- <div class="attachments-header d-none" id="attachments-header">
                        <div class="form-check" style="padding-left: 2.75em !important;">
                            <input class="form-check-input" type="checkbox" id="attachment_select_all">
                            <label class="form-check-label" for="attachment_select_all">
                                {{ __('Select All') }}
                            </label>
                        </div>
                    </div> --}}

                    {{-- rows get injected here when files are added --}}
                    <div id="attachments-list"></div>

                    {{-- QBO-like drop zone --}}
                    <div class="attachment-zone" id="attachment-zone">
                        <a href="#" class="attachment-link" id="attachment-add-link">
                            {{ __('Add attachment') }}
                        </a>
                        <div class="attachment-limit">{{ __('Max file size: 20 MB') }}</div>
                    </div>

                    {{-- we keep our hidden file inputs here --}}
                    <div id="attachment-file-inputs" class="d-none"></div>
                </div>
            </div>


        </div>

    </div>


    <!-- ======================== CATEGORY TABLE style and script ======================== -->
    <style>
        /* .custom-accordion {
                                                    border: 1px solid #ddd;
                                                    border-radius: 5px;
                                                    overflow: hidden;
                                                    margin-bottom: 1rem;
                                                } */
        #category-table {
            background: white;
            border-right: 1px solid #d4d7dc !important;
            border-bottom: 1px solid #d4d7dc !important;
            border-left: 0px !important;
            /* Left side open */
            border-top: 0px !important;
            /* Top side open */
            border-collapse: collapse;
        }

        .lineitem-toolbar {
            display: flex;
            justify-content: flex-end;
            /* Pushes content to the right */
            padding: 10px;
            /* optional spacing */
        }


        .toolbar-buttons {
            display: flex;
            gap: 5px;
        }

        .toolbar-btn {
            border: none;
            padding: 6px;
            border-radius: 2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            transition: 0.2s;
            background: transparent;
        }

        .toolbar-btn:hover {
            background: #f5f5f5;
            border-color: #bcbcbc;
        }

        .toolbar-btn svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }


        #category-table thead tr {
            border-bottom: 1px solid #cfd1d7 !important;
        }

        #category-table th {
            border-right: 1px solid #e3e5eb !important;
            border-bottom: 1px solid #e3e5eb !important;
            border-top: 0px !important;
            /* Header top open */
            border-left: 0px !important;
            /* Header left open */
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #5a5b5f;
        }

        #category-table td {
            border-right: 1px solid #e3e5eb !important;
            border-bottom: 1px solid #e3e5eb !important;
            border-top: 0px !important;
            /* Cells top open */
            border-left: 0px !important;
            /* Cells left open */
            padding: 10px 8px;
            font-size: 13px;
            color: #2b2c30;
            vertical-align: middle;
        }

        #category-table tbody tr {
            background: white;
        }

        #category-table tbody tr:hover {
            background: #fafafa;
        }



        .size {
            width: 270px;
        }

        .field {
            width: 185px;
        }

        .field1 {
            width: 165px;
        }

        .TrowserHeader {
            padding: 10px;
        }

        .accordion-header {
            padding: 0.75rem 1rem;

            cursor: pointer;
            user-select: none;
        }

        .accordion.open .accordion-content {
            display: block;
        }

        .accordion-arrow svg {
            transition: transform 0.3s;
            transform: rotate(90deg);
            /* Default > */
        }

        .accordion.open .accordion-arrow svg {
            transform: rotate(180deg);
            /* v */
        }

        .accordion-content {
            padding: 0.5rem 1rem;
            display: none;
        }

        .accordion-content.collapsed {
            display: none;
        }

        .txp-capability-expenseLayout-N1jWN header[class*='TrowserHeader-header'] button svg {
            width: 33px;
            height: 33px;
        }

        /* Container targeting */
        .txp-capability-gridWrapper-dVsAS .txp-capability-gridButtons-Uh9M\+ [class*='Button-priority-primary'] {
            background-color: var(--color-container-background-primary);
            color: var(--color-text-primary);
            border-color: var(--color-container-border-primary);
            border-radius: 4px;
            /* optional for rounded corners */
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        /* Padding for label */
        .Button-size-medium-6a785d2 .Button-label-6a785d2 {
            padding-left: var(--padding-inline, 8px);
            padding-right: var(--padding-inline, 8px);
            display: inline-block;
        }

        /* Optional: Hover effect */
        .Button-priority-primary-6a785d2:hover {
            background-color: #6c6464c0;
            /* QuickBooks blue hover */
            color: #000000ff;

        }
    </style>

    <script>
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const arrow = header.querySelector('.accordion-arrow svg');

            if (content.style.display === "block") {
                // Hide
                content.style.display = "none";
                arrow.style.transform = "rotate(0deg)"; // > arrow
            } else {
                // Show
                content.style.display = "block";
                arrow.style.transform = "rotate(360deg)"; // v arrow
            }
        }
    </script>


    <!-- ======================== ITEM TABLE style and script ======================== -->
    <style>
        /* .custom-accordion {
                                                    border: 1px solid #ddd;
                                                    border-radius: 5px;
                                                    overflow: hidden;
                                                    margin-bottom: 1rem;
                                                } */

        .accordion-header {
            padding: 0.75rem 1rem;

            cursor: pointer;
            user-select: none;
        }

        .accordion.open .accordion-content {
            display: block;
        }

        .accordion-arrow svg {
            transition: transform 0.3s;
            transform: rotate(90deg);
            /* Default > */
        }

        .accordion.open .accordion-arrow svg {
            transform: rotate(180deg);
            /* v */
        }

        .accordion-content {
            padding: 0.5rem 1rem;
            display: none;
        }

        .accordion-content.collapsed {
            display: none;
        }

        #item-table {
            background: white;
            border-right: 1px solid #d4d7dc !important;
            border-bottom: 1px solid #d4d7dc !important;
            border-left: 0px !important;
            /* Left side open */
            border-top: 0px !important;
            /* Top side open */
            border-collapse: collapse;
        }

        #item-table thead tr {
            border-bottom: 1px solid #cfd1d7 !important;
        }

        #item-table th {
            border-right: 1px solid #e3e5eb !important;
            border-bottom: 1px solid #e3e5eb !important;
            border-top: 0px !important;
            /* Header top open */
            border-left: 0px !important;
            /* Header left open */
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #5a5b5f;
        }

        #item-table td {
            border-right: 1px solid #e3e5eb !important;
            border-bottom: 1px solid #e3e5eb !important;
            border-top: 0px !important;
            /* cells top open */
            border-left: 0px !important;
            /* cells left open */
            padding: 10px 8px;
            font-size: 13px;
            color: #2b2c30;
            vertical-align: middle;
        }

        #item-table tbody tr {
            background: white;
        }

        #item-table tbody tr:hover {
            background: #fafafa;
        }

        .header-action-btn {
            padding: 6px 12px;
            font-weight: 700;
            font-size: 18px;
            color: #6B6C72;
            background: transparent;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-action-btn:hover {
            background: rgba(107, 108, 114, 0.1);
            border-color: rgba(107, 108, 114, 0.1);
        }
    </style>

    <script>
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const arrow = header.querySelector('.accordion-arrow svg');

            if (content.style.display === "block") {
                // Hide
                content.style.display = "none";
                arrow.style.transform = "rotate(0deg)"; // > arrow
            } else {
                // Show
                content.style.display = "block";
                arrow.style.transform = "rotate(360deg)"; // v arrow
            }
        }
    </script>





    <div class="modal-footer-custom fixed-footer">
        <!-- Left section: secondary actions -->
        <div class="footer-left d-flex align-items-center" style="gap:0px;">
            <button type="button" class="btn btn-link text-success p-2 btn-cancel-custom" data-bs-dismiss="modal"
                style="
                    background: #fff;
                    border: 2px solid #00892E;
                    color: #00892E;
                    font-weight: 600;
                    padding: 6px 12px !important;
                    border-radius: 4px;
                    margin-left: 10px;
                    cursor: pointer;
                    font-size: 14px;
                    white-space: nowrap;
                ">Cancel</button>
            <button type="button" class="btn btn-link text-success p-2 btn-cancel-custom" data-bs-dismiss="modal"
                style="
                    background: #fff;
                    border: 2px solid #00892E;
                    color: #00892E;
                    font-weight: 600;
                    padding: 6px 12px !important;
                    border-radius: 4px;
                    margin-left: 10px;
                    cursor: pointer;
                    font-size: 14px;
                    white-space: nowrap;
                ">Clear</button>
        </div>

        <!-- Center section -->
        <div class="footer-center d-flex align-items-center">
            <a href="#" class="small-text-link text-dark">{{ __('Make recurring') }}</a>
        </div>

        <!-- Right section: primary actions -->
        <div class="footer-right d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-light btn-sm-qbo">{{ __('Save') }}</button>
            <div class="btn-group">
                <button type="submit" class="btn btn-success btn-sm-qbo">{{ __('Save and close') }}</button>
                <button type="button" class="btn btn-success btn-sm-qbo dropdown-toggle dropdown-toggle-split"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">{{ __('Save and new') }}</a></li>
                    {{-- <li><a class="dropdown-item" href="#">{{ __('Save and print') }}</a></li> --}}
                </ul>
            </div>
        </div>
    </div>

    <style>
        /* Sticky footer at the bottom of the viewport */
        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1050;
            /* above other content */
        }

        /* Left section links/buttons */
        .fixed-footer .footer-left {
            display: flex;
            align-items: center;
            /* gap: 1rem; */
        }

        /* Center section */
        .fixed-footer .footer-center {
            display: flex;
            align-items: center;
        }

        /* Right section action buttons */
        .fixed-footer .footer-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Cancel button custom style */
        .btn-cancel-custom {
            border: 1px solid #00892e;
            color: #00892e;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            background-color: transparent;
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }

        .btn-cancel-custom:hover {
            background-color: #00892e;
            color: #fff;
        }

        /* Primary success button style */
        .btn-success {
            background-color: #2ca01c;
            border-color: #2ca01c;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #25861b;
            border-color: #25861b;
        }

        /* Optional: adjust dropdown menu for split button */
        .fixed-footer .btn-group .dropdown-menu {
            min-width: auto;
        }
    </style>


    <style>
        .modal-footer-custom {
            background: #ffffff;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            /* Left / center / right */
            align-items: center;
        }

        /* Left section links/buttons */
        .modal-footer-custom .footer-left {
            display: flex;
            align-items: center;
            /* gap: 1rem; */
        }

        /* Center section (optional) */
        .modal-footer-custom .footer-center {
            display: flex;
            align-items: center;
        }

        /* Right section action buttons */
        .modal-footer-custom .footer-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Primary success button style */
        .modal-footer-custom .btn-success {
            background-color: #00892e;
            border-color: #00892e;
            color: #fff;
        }

        .modal-footer-custom .btn-success:hover {
            background-color: #25861b;
            border-color: #25861b;
        }

        /* Split button dropdown adjustments */
        .modal-footer-custom .btn-group .dropdown-menu {
            min-width: auto;
        }

        .btn-cancel-custom {
            border: 1px solid #00892e;
            /* green border */
            color: #00892e;
            /* text color green */
            padding: 0.25rem 0.75rem;
            /* some padding to look like a button */
            border-radius: 4px;
            /* slightly rounded corners */
            background-color: transparent;
            /* keep background white/transparent */
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }
    </style>


    {{ Form::close() }}
</div>
