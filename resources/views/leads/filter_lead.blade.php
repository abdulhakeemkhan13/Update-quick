{{ Form::open(['route' => 'leads.index', 'method' => 'get']) }}
<div class="modal-body">
    <div id="dynamic-rows-container">
        <div class="row mb-2" data-row-index="0">
            <div class="col-md-4">
                <select name="fields[{{ $rowIndex }}][]" class="form-control field-select"
                    onchange="handleFieldChange(this)">
                    <option value="">Select Field</option>
                    @foreach ($customFields as $field)
                        <option value="{{ $field->name }}" data-field-id="{{ $field->id }}"
                            data-type="{{ $field->type }}">
                            {{ $field->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="operators[{{ $rowIndex }}][]" class="form-control operator-select">
                    <option value=""></option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="values[{{ $rowIndex }}][]" class="form-control value-input">
            </div>
            <div class="col-md-2 ">
                <button type="button" class="btn btn-primary float-end" onclick="addRow(this)">
                    <i class="ti ti-plus"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Save') }}" class="btn  btn-primary">
    </div>
</div>


<script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
<script>
    let rowIndex = 1; 
    function handleFieldChange(selectElement) {
        const row = selectElement.closest('.row');
        let valueField = row.querySelector('.value-input');
        const operatorSelect = row.querySelector('.operator-select');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const fieldType = selectedOption.getAttribute('data-type');
        const fieldId = selectedOption.getAttribute('data-field-id');
        if (valueField) {
            const choicesWrapper = valueField.closest('.choices');
            if (choicesWrapper) {
                const selectElement = choicesWrapper.querySelector('select');
                if (selectElement && selectElement.choicesInstance) {
                    selectElement.choicesInstance.destroy();
                }
                choicesWrapper.remove();
            } else {
                valueField.remove();
            }
        }

        let newField;
        if (fieldType === 'list') {
            const route = "{{ route('getlistfields', ':id') }}".replace(':id', fieldId);
            fetch(route)
                .then(response => response.json())
                .then(data => {
                    newField = document.createElement('select');
                    newField.name = `values[${rowIndex}][]`; 
                    newField.className = 'form-control value-input values';
                    newField.multiple = true;

                    const uniqueId = `values-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                    newField.id = uniqueId;

                    if (data.success) {
                        newField.innerHTML = data.options.map(option =>
                            `<option value="${option}">${option}</option>`
                        ).join('');
                    } else {
                        newField.innerHTML = '<option value=""></option>';
                    }

                    if (valueField && operatorSelect) {
                        row.querySelectorAll('div')[2].append(newField);
                        new Choices(`#${uniqueId}`, {
                            removeItemButton: true,
                        });
                    } else {
                        console.log("Error: valueField or operatorSelect not found");
                    }
                })
                .catch(error => console.error('Error fetching options:', error));
        } else {
            newField = document.createElement('input');
            newField.name = `values[${rowIndex}][]`; 
            newField.className = 'form-control value-input';

            if (fieldType === 'date') {
                newField.type = 'date';
            } else if (fieldType === 'number') {
                newField.type = 'number';
            } else {
                newField.type = 'text';
            }

            if (valueField && operatorSelect) {
                row.querySelectorAll('div')[2].append(newField);
            } else {
                console.log("Error: valueField or operatorSelect not found");
            }
        }
        const operators = {
            text: ['=', 'LIKE', '%LIKE%'],
            number: ['=', '>', '<', '>=', '<='],
            date: ['=', '>', '<', '>=', '<='],
            select: ['='],
        };

        const newOperators = operators[fieldType] || ['='];
        operatorSelect.innerHTML = '';
        newOperators.forEach(op => {
            const option = document.createElement('option');
            option.value = op;
            option.textContent = op;
            operatorSelect.appendChild(option);
        });
    }

    function addRow(buttonElement) {
        const container = document.getElementById('dynamic-rows-container');
        const currentRow = buttonElement.closest('.row');
        buttonElement.classList.replace('btn-primary', 'btn-danger');
        buttonElement.innerHTML = '<i class="ti ti-trash"></i>';
        buttonElement.setAttribute('onclick', 'deleteRow(this)');
        const newRow = currentRow.cloneNode(true);
        newRow.setAttribute('data-row-index', rowIndex++);
        const fieldSelect = newRow.querySelector('.field-select');
        const operatorSelect = newRow.querySelector('.operator-select');
        const valueField = newRow.querySelector('.value-input');
        fieldSelect.setAttribute('name', `fields[${rowIndex}][]`);
        operatorSelect.setAttribute('name', `operators[${rowIndex}][]`);
        valueField.setAttribute('name', `values[${rowIndex}][]`);
        fieldSelect.selectedIndex = 0;
        operatorSelect.selectedIndex = 0;
        if (valueField.tagName === 'SELECT' || valueField.closest('.choices')) {
            const choicesWrapper = valueField.closest('.choices');
            const parentContainer = choicesWrapper ? choicesWrapper.parentNode : null;

            if (choicesWrapper) {
                const selectElement = choicesWrapper.querySelector('select');
                if (selectElement && selectElement.choicesInstance) {
                    selectElement.choicesInstance.destroy();
                }
                choicesWrapper.remove();
            }

            // Create a new input field if necessary
            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = `values[${rowIndex}][]`; // Add the row index here
            newInput.className = 'form-control value-input';
            parentContainer.appendChild(newInput);
        } else {
            valueField.value = ''; // Clear the value if it's a simple input
        }

        // Reset the button to allow adding another row
        const newButton = newRow.querySelector('.btn');
        newButton.classList.replace('btn-danger', 'btn-primary');
        newButton.innerHTML = '<i class="ti ti-plus"></i>';
        newButton.setAttribute('onclick', 'addRow(this)');
        container.appendChild(newRow);
        const newFieldSelect = newRow.querySelector('.value-input.values');
        if (newFieldSelect) {
            newFieldSelect.choicesInstance = new Choices(newFieldSelect, {
                removeItemButton: true,
            });
        }
    }

    // function addRow(buttonElement) {
    //     const container = document.getElementById('dynamic-rows-container');
    //     const currentRow = buttonElement.closest('.row');
    //     buttonElement.classList.replace('btn-primary', 'btn-danger');
    //     buttonElement.innerHTML = '<i class="ti ti-trash"></i>';
    //     buttonElement.setAttribute('onclick', 'deleteRow(this)');
    //     const newRow = currentRow.cloneNode(true);
    //     newRow.setAttribute('data-row-index', rowIndex++);
    //     const fieldSelect = newRow.querySelector('.field-select');
    //     const operatorSelect = newRow.querySelector('.operator-select');
    //     const valueField = newRow.querySelector('.value-input');
    //     fieldSelect.selectedIndex = 0;
    //     operatorSelect.selectedIndex = 0;

    //     if (valueField.tagName === 'SELECT' || valueField.closest('.choices')) {
    //         const choicesWrapper = valueField.closest('.choices');
    //         const parentContainer = choicesWrapper.parentNode;

    //         if (choicesWrapper) {
    //             const selectElement = choicesWrapper.querySelector('select');
    //             if (selectElement && selectElement.choicesInstance) {
    //                 selectElement.choicesInstance.destroy();
    //             }
    //             choicesWrapper.remove();
    //         }
    //         const newInput = document.createElement('input');
    //         newInput.type = 'text';
    //         newInput.name = 'values[]';
    //         newInput.className = 'form-control value-input';
    //         parentContainer.appendChild(newInput);
    //     } else {
    //         valueField.value = '';
    //     }
    //     const newButton = newRow.querySelector('.btn');
    //     newButton.classList.replace('btn-danger', 'btn-primary');
    //     newButton.innerHTML = '<i class="ti ti-plus"></i>';
    //     newButton.setAttribute('onclick', 'addRow(this)');
    //     container.appendChild(newRow);
    //     const newFieldSelect = newRow.querySelector('.value-input.values');
    //     if (newFieldSelect) {
    //         newFieldSelect.choicesInstance = new Choices(newFieldSelect, {
    //             removeItemButton: true,
    //         });
    //     }
    // }
    function deleteRow(buttonElement) {
        const row = buttonElement.closest('.row');
        row.remove();
    }
</script>

{{ Form::close() }}
