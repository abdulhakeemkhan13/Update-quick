// bill.js

// Track line counts
let categoryLineCount = 0;
let itemLineCount = 0;

// ===== Add Category Line =====
$('#add-category-line').on('click', function() {
    categoryLineCount++;
    const newRow = `
        <tr class="category-row">
            <td class="qbo-line-number">${categoryLineCount}</td>
            <td>
                <select name="category[${categoryLineCount}][account_id]" class="form-control category-account">
                    <option value="">Select account</option>
                    ${window.chartAccountsOptions || ''}
                </select>
            </td>
            <td>
                <textarea name="category[${categoryLineCount}][description]" class="form-control" rows="1"></textarea>
            </td>
            <td>
                <input type="number" name="category[${categoryLineCount}][amount]" class="form-control category-amount" step="0.01" value="0.00">
            </td>
            <td class="text-center">
                <input type="checkbox" name="category[${categoryLineCount}][billable]" class="qbo-checkbox" value="1">
            </td>
            <td>
                <select name="category[${categoryLineCount}][tax_id]" class="form-control category-tax">
                    <option value="">-</option>
                    ${window.taxesOptions || ''}
                </select>
            </td>
            <td>
                <select name="category[${categoryLineCount}][customer_id]" class="form-control customer-select">
                    <option value="">-</option>
                    ${window.customersOptions || ''}
                </select>
            </td>
            <td class="row-actions">
                <i class="fas fa-trash delete-row"></i>
            </td>
        </tr>
    `;
    $('#category-tbody').append(newRow);
    updateLineNumbers();
});

// ===== Add Item Line =====
$('#add-item-line').on('click', function() {
    itemLineCount++;
    const newRow = `
        <tr class="item-row">
            <td class="qbo-line-number">${itemLineCount}</td>
            <td>
                <select name="items[${itemLineCount}][product_id]" class="form-control item-product">
                    <option value="">Select product/service</option>
                    ${window.productsOptions || ''}
                </select>
            </td>
            <td>
                <textarea name="items[${itemLineCount}][description]" class="form-control item-description" rows="1"></textarea>
            </td>
            <td>
                <input type="number" name="items[${itemLineCount}][quantity]" class="form-control item-qty" step="1" value="1">
            </td>
            <td>
                <input type="number" name="items[${itemLineCount}][price]" class="form-control item-rate" step="0.01" value="0.00">
            </td>
            <td>
                <input type="number" name="items[${itemLineCount}][amount]" class="form-control item-amount" step="0.01" value="0.00" readonly>
            </td>
            <td class="text-center">
                <input type="checkbox" name="items[${itemLineCount}][billable]" class="qbo-checkbox" value="1">
            </td>
            <td>
                <select name="items[${itemLineCount}][tax_id]" class="form-control item-tax">
                    <option value="">-</option>
                    ${window.taxesOptions || ''}
                </select>
            </td>
            <td>
                <select name="items[${itemLineCount}][customer_id]" class="form-control customer-select">
                    <option value="">-</option>
                    ${window.customersOptions || ''}
                </select>
            </td>
            <td class="row-actions">
                <i class="fas fa-trash delete-row"></i>
            </td>
        </tr>
    `;
    $('#item-tbody').append(newRow);
    updateLineNumbers();
});

// ===== Delete Row =====
$(document).on('click', '.delete-row', function() {
    $(this).closest('tr').remove();
    updateLineNumbers();
    calculateTotal();
});

// ===== Clear Lines =====
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

// ===== Update Line Numbers =====
function updateLineNumbers() {
    $('.category-row').each(function(index) {
        $(this).find('.qbo-line-number').text(index + 1);
    });
    $('.item-row').each(function(index) {
        $(this).find('.qbo-line-number').text(index + 1);
    });
}

// ===== Calculate Item Amount =====
$(document).on('input', '.item-qty, .item-rate', function() {
    const row = $(this).closest('tr');
    const qty = parseFloat(row.find('.item-qty').val()) || 0;
    const rate = parseFloat(row.find('.item-rate').val()) || 0;
    row.find('.item-amount').val((qty * rate).toFixed(2));
    calculateTotal();
});

// ===== Category Amount Change =====
$(document).on('input', '.category-amount', function() {
    calculateTotal();
});

// ===== Calculate Total =====
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
    $('#subtotal-display, #total-display, #grand-total-display').text('$' + subtotal.toFixed(2));
}

// ===== File Upload Display =====
$('#attachments').on('change', function() {
    const files = this.files;
    $('#attachment-list').empty();
    for (let i = 0; i < files.length; i++) {
        $('#attachment-list').append(`<div class="small text-muted"><i class="fas fa-paperclip"></i> ${files[i].name}</div>`);
    }
});

// ===== Product Auto-fill via AJAX =====
$(document).on('change', '.item-product', function() {
    const productId = $(this).val();
    if (!productId) return;

    $.ajax({
        url: window.productRoute || '/invoice/product',
        method: 'POST',
        data: { _token: window.csrfToken || '', product_id: productId },
        success: function(data) {
            if (data.product) {
                const row = $(`select[value="${productId}"]`).closest('tr');
                row.find('.item-description').val(data.product.description || '');
                row.find('.item-rate').val(data.product.purchase_price || 0);
                row.find('.item-qty').trigger('input'); // Recalculate
            }
        }
    });
});

// ===== Form Submit =====
$('#bill-form').on('submit', function(e) {
    e.preventDefault();
    $('.btn-qbo-save').prop('disabled', true).text('Saving...');
    const formData = new FormData(this);

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                window.location.href = window.billIndexRoute || '/bill';
            } else {
                alert(response.message || 'Error creating bill');
                $('.btn-qbo-save').prop('disabled', false).text('Save');
            }
        },
        error: function(xhr) {
            let message = 'Error creating bill';
            if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
            alert(message);
            $('.btn-qbo-save').prop('disabled', false).text('Save');
        }
    });
});

// ===== Initialize =====
$(document).ready(function() {
    calculateTotal();
});
