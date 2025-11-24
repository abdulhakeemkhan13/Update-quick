document.addEventListener("DOMContentLoaded", function () {
    var rightcard = false;
    var tempblock;
    var tempblock2;
    const blockData = {
        tab1: {
            triggers: [
                {
                    value: 1,
                    title: "New visitor",
                    desc: "Triggers when somebody visits a specified page",
                    icon: "assets/eye.svg",
                },
                {
                    value: 2,
                    title: "Action is performed",
                    desc: "Triggers when somebody performs a specified action",
                    icon: "assets/action.svg",
                },
            ],
            actions: [
                {
                    value: 5,
                    title: "New database entry in Tab 1",
                    desc: "Adds a new entry to a specified database in Tab 1",
                    icon: "assets/database.svg",
                },
                {
                    value: 6,
                    title: "Update database in Tab 1",
                    desc: "Edits and deletes database entries in Tab 1",
                    icon: "assets/database.svg",
                },
            ],
            loggers: [
                {
                    value: 9,
                    title: "Add new log entry in Tab 1",
                    desc: "Adds a new log entry to this project in Tab 1",
                    icon: "assets/log.svg",
                },
                {
                    value: 10,
                    title: "Update logs in Tab 1",
                    desc: "Edits and deletes log entries in this project in Tab 1",
                    icon: "assets/log.svg",
                },
            ],
        },
        tab2: {
            triggers: [
                {
                    value: 1,
                    title: "New visitor in Tab 2",
                    desc: "Triggers when somebody visits a specified page in Tab 2",
                    icon: "assets/eye.svg",
                },
                {
                    value: 2,
                    title: "Action is performed in Tab 2",
                    desc: "Triggers when somebody performs a specified action in Tab 2",
                    icon: "assets/action.svg",
                },
            ],
            actions: [
                {
                    value: 5,
                    title: "New database entry in Tab 2",
                    desc: "Adds a new entry to a specified database in Tab 2",
                    icon: "assets/database.svg",
                },
                {
                    value: 6,
                    title: "Update database in Tab 2",
                    desc: "Edits and deletes database entries in Tab 2",
                    icon: "assets/database.svg",
                },
            ],
            loggers: [
                {
                    value: 9,
                    title: "Add new log entry in Tab 2",
                    desc: "Adds a new log entry to this project in Tab 2",
                    icon: "assets/log.svg",
                },
                {
                    value: 10,
                    title: "Update logs in Tab 2",
                    desc: "Edits and deletes log entries in this project in Tab 2",
                    icon: "assets/log.svg",
                },
            ],
        },
        tab3: {
            triggers: [
                {
                    value: 1,
                    title: "New visitor in Tab 3",
                    desc: "Triggers when somebody visits a specified page in Tab 3",
                    icon: "assets/eye.svg",
                },
                {
                    value: 2,
                    title: "Action is performed in Tab 3",
                    desc: "Triggers when somebody performs a specified action in Tab 3",
                    icon: "assets/action.svg",
                },
            ],
            actions: [
                {
                    value: 5,
                    title: "New database entry in Tab 3",
                    desc: "Adds a new entry to a specified database in Tab 3",
                    icon: "assets/database.svg",
                },
                {
                    value: 6,
                    title: "Update database in Tab 3",
                    desc: "Edits and deletes database entries in Tab 3",
                    icon: "assets/database.svg",
                },
            ],
            loggers: [
                {
                    value: 9,
                    title: "Add new log entry in Tab 3",
                    desc: "Adds a new log entry to this project in Tab 3",
                    icon: "assets/log.svg",
                },
                {
                    value: 10,
                    title: "Update logs in Tab 3",
                    desc: "Edits and deletes log entries in this project in Tab 3",
                    icon: "assets/log.svg",
                },
            ],
        },
        tab4: {
            triggers: [
                {
                    value: 1,
                    title: "New visitor in Tab 4",
                    desc: "Triggers when somebody visits a specified page in Tab 4",
                    icon: "assets/eye.svg",
                },
                {
                    value: 2,
                    title: "Action is performed in Tab 4",
                    desc: "Triggers when somebody performs a specified action in Tab 4",
                    icon: "assets/action.svg",
                },
            ],
            actions: [
                {
                    value: 5,
                    title: "New database entry in Tab 4",
                    desc: "Adds a new entry to a specified database in Tab 4",
                    icon: "assets/database.svg",
                },
                {
                    value: 6,
                    title: "Update database in Tab 4",
                    desc: "Edits and deletes database entries in Tab 4",
                    icon: "assets/database.svg",
                },
            ],
            loggers: [
                {
                    value: 9,
                    title: "Add new log entry in Tab 4",
                    desc: "Adds a new log entry to this project in Tab 4",
                    icon: "assets/log.svg",
                },
                {
                    value: 10,
                    title: "Update logs in Tab 4",
                    desc: "Edits and deletes log entries in this project in Tab 4",
                    icon: "assets/log.svg",
                },
            ],
        },
    };
    // document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/eye.svg"></div><div class="blocktext">                        <p class="blocktitle">New visitor</p><p class="blockdesc">Triggers when somebody visits a specified page</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="2"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Action is performed</p><p class="blockdesc">Triggers when somebody performs a specified action</p></div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="3"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/time.svg"></div><div class="blocktext">                        <p class="blocktitle">Time has passed</p><p class="blockdesc">Triggers after a specified amount of time</p>          </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="4"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Error prompt</p><p class="blockdesc">Triggers when a specified error happens</p>              </div></div></div>';
    // Initialize Flowy with drag, release, and snapping functions
    flowy(document.getElementById("leadcanvas"), drag, release, snapping,onRearrange);

    function addEventListenerMulti(type, listener, capture, selector) {
        const nodes = document.querySelectorAll(selector);
        for (let i = 0; i < nodes.length; i++) {
            nodes[i].addEventListener(type, listener, capture);
        }
    }


    // Snapping function, triggered when a block is dropped on the canvas
    function snapping(drag, first,parent) {
        const blockType = drag.querySelector(".blockelemtype").value;
        let parentTabContent = drag.closest(".tab-content");

        if (!parentTabContent) {
            parentTabContent = document.querySelector(".tab-content.active-content");
        }

        const tabDataIndex = parentTabContent ? parentTabContent.getAttribute("data-index") : null;

        if (!tabDataIndex) {
            console.error("Could not find a valid tab-data index. Check the tab-content structure.");
            return false;
        }

        const grab = drag.querySelector(".grabme");
        if (grab) grab.remove();
        const blockin = drag.querySelector(".blockin");
        if (blockin) blockin.remove();

        if (blockData['tab' + tabDataIndex]) {
            const data = blockData['tab' + tabDataIndex];
            const tempGrab = tempblock2.querySelector(".grabme");
            const tempBlockin = tempblock2.querySelector(".blockin");

            if (tempGrab) {
                drag.appendChild(tempGrab.cloneNode(true));
            }
            if (tempBlockin) {
                drag.appendChild(tempBlockin.cloneNode(true));
            }
        }
        //     console.log('onsnap',block);
        return true;
    }

    // Drag function, triggered when a block is picked up for dragging
    function drag(block) {
        block.classList.add("blockdisabled");
        tempblock2 = block;
        
    }

    // Release function, triggered when a block is dropped
    function release() {        
        if (tempblock2) {
            tempblock2.classList.remove("blockdisabled");
        }
    }
    // function onSnap(block, first, parent){
    //     // When a block snaps with another one
    //     console.log('onsnap',parent);
    //     console.log('onsnap',first);
    //     console.log('onsnap',block);
        
    // }
    function onRearrange(block, parent){
        // When a block is rearranged
        console.log('onRearrange',parent);
        console.log('onRearrange',block);

    }
    var disabledClick = function () {
        // console.log('workspace');

        const parentTab = this.closest(".tab-content");
        const dataid = parentTab.getAttribute("data-index");
        // Disable the currently active navigation
        document.querySelector(".navactive").classList.add("navdisabled");
        document.querySelector(".navactive").classList.remove("navactive");
        this.classList.add("navactive");
        this.classList.remove("navdisabled");

        const blocklist = document.getElementById(`blocklist-tab${dataid}`);
        blocklist.innerHTML = "";
        if (blockData["tab" + dataid]) {
            const category = this.getAttribute("id");

            if (blockData["tab" + dataid][category]) {
                blockData["tab" + dataid][category].forEach((item) => {
                    blocklist.innerHTML += `
                        <div class="blockelem create-flowy noselect">
                            <input type="hidden" name="blockelemtype" class="blockelemtype" value="${item.value}">
                            <input type="hidden" name="tabid" class="tabid" value="${dataid}">
                            <input type="hidden" name="subtabid" class="subtabid" value="${category}">
                            <div class="grabme"><img src="assets/grabme.svg"></div>
                            <div class="blockin">
                                <div class="blockico"><span></span><img src="${item.icon}"></div>
                                <div class="blocktext">
                                    <p class="blocktitle">${item.title}</p>
                                    <p class="blockdesc">${item.desc}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        }
    };
    addEventListenerMulti("click", disabledClick, false, ".side");
    document.getElementById("calculatelevels").addEventListener("click", function () {
        const mostparentElement = document.getElementById("leadcanvas");
        const blockElems = Array.from(mostparentElement.getElementsByClassName("blockelem"));
        const levels = [];  // Array to hold levels with same-level elements grouped together
        
        let currentLevelTopPosition = parseFloat(blockElems[0].style.top);  // Initial level top position
        let currentLevel = [];  // Array to hold elements of the current level
    
        blockElems.forEach((blockElem) => {
            const blockTopPosition = parseFloat(blockElem.style.top);
    
            // If the top position of this element is equal to the current level's position, add it to the current level
            if (blockTopPosition === currentLevelTopPosition) {
                currentLevel.push(blockElem);
            } 
            // If the top position is greater, we finalize the current level and start a new one
            else if (blockTopPosition > currentLevelTopPosition) {
                levels.push(currentLevel);  // Save the completed level
                currentLevel = [blockElem];  // Start a new level with this element
                currentLevelTopPosition = blockTopPosition;  // Update to new level's top position
            }
        });
    
        // Push the last level if it has elements
        if (currentLevel.length > 0) {
            levels.push(currentLevel);
        }
    
        // Logging the levels with elements grouped by level
        console.log("Levels defined:");
        levels.forEach((level, index) => {
            console.log(`Level ${index + 1}:`, level.map(elem => elem.outerHTML));
        });

        const dataflow = flowy.output();
        console.log(dataflow);
        
    });
    
    




    document.getElementById("close").addEventListener("click", function () {
        if (rightcard) {
            rightcard = false;
            document.getElementById("properties").classList.remove("expanded");
            setTimeout(function () {
                document.getElementById("propwrap").classList.remove("itson");
            }, 300);
            tempblock.classList.remove("selectedblock");
        }
    });

    // document.getElementById("removeblock").addEventListener("click", function () {
    //     flowy.deleteBlocks();
    // });
    var aclick = false;
    var noinfo = false;
    var beginTouch = function (event) {
        aclick = true;
        noinfo = false;
        if (event.target.closest(".create-flowy")) {
            noinfo = true;
        }
    };
    var checkTouch = function (event) {
        aclick = false;
    };
    var doneTouch = function (event) {
        if (event.type === "mouseup" && aclick && !noinfo) {
            if (
                !rightcard &&
                event.target.closest(".block") &&
                !event.target.closest(".block").classList.contains("dragging")
            ) {
                tempblock = event.target.closest(".block");
                rightcard = true;
                document.getElementById("properties").classList.add("expanded");
                document.getElementById("propwrap").classList.add("itson");
                tempblock.classList.add("selectedblock");

                const propwarp = document.getElementById("propwrap");
                if (propwarp.querySelector('#actionform')) {
                    return;
                }

                var cardData = {
                    id: tempblock.querySelector('input[name="blockid"]').value,
                    temptype: tempblock.querySelector('input[name="blockelemtype"]').value,
                };
                const title = tempblock.querySelector('.blocktitle').innerHTML;
                const description = tempblock.querySelector('.blockdesc').innerHTML;
                propwarp.querySelector('#blockname').innerHTML = title;
                propwarp.querySelector('#blockdesc').innerHTML = description;

                const propwrapform = propwarp.querySelector('.form');
                const formelement = document.createElement('form');
                formelement.id = 'actionform';
                propwrapform.appendChild(formelement);

                // User select
                const userSelect = document.createElement('select');
                userSelect.id = 'userSelect';
                userSelect.multiple = true;
                userSelect.classList.add('form-control', 'mt-2', 'js-example-basic-multiple');

                // Department select with placeholder
                const departmentSelect = document.createElement('select');
                departmentSelect.id = 'departmentSelect';
                departmentSelect.classList.add('form-control', 'mt-2');
                const departmentPlaceholder = document.createElement('option');
                departmentPlaceholder.value = '';
                departmentPlaceholder.text = 'Select Department';
                departmentPlaceholder.disabled = true;
                departmentPlaceholder.selected = true;
                departmentSelect.appendChild(departmentPlaceholder);

                // Designation select with placeholder
                const designationSelect = document.createElement('select');
                designationSelect.id = 'designationSelect';
                designationSelect.classList.add('form-control', 'mt-2', 'mb-2');
                const designationPlaceholder = document.createElement('option');
                designationPlaceholder.value = '';
                designationPlaceholder.text = 'Select Designation';
                designationPlaceholder.disabled = true;
                designationPlaceholder.selected = true;
                designationSelect.appendChild(designationPlaceholder);

                // Populate selects
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.name;
                    userSelect.appendChild(option);
                });

                departments.forEach(department => {
                    const option = document.createElement('option');
                    option.value = department.id;
                    option.text = department.name;
                    departmentSelect.appendChild(option);
                });

                designations.forEach(designation => {
                    const option = document.createElement('option');
                    option.value = designation.id;
                    option.text = designation.name;
                    designationSelect.appendChild(option);
                });

                // Append selects and button to the form
                formelement.appendChild(departmentSelect);
                formelement.appendChild(designationSelect);
                formelement.appendChild(userSelect);

                // Create and append the button
                const buttondiv = document.createElement('div');
                buttondiv.id = 'removeblock';
                document.querySelector('#properties').appendChild(buttondiv);

                const getValuesButton = document.createElement('button');
                getValuesButton.type = 'button';
                getValuesButton.innerText = 'Get Selected Values';
                getValuesButton.classList.add('btn', 'btn-primary');
                getValuesButton.onclick = logSelectedValues;
                buttondiv.appendChild(getValuesButton);
                // $(function () {
                //     $('#userSelect').each(function () {
                //         $(this).select2({
                //             theme: 'bootstrap4',
                //             width: 'style',
                //             placeholder: $(this).attr('placeholder'),
                //             allowClear: Boolean($(this).data('allow-clear')),
                //         });
                //     });
                // });
                $('.js-example-basic-multiple').select2();
                $('.js-example-basic-multiple').select2({
                    placeholder: "Select Users"
                });

                // Initialize Choices.js for userSelect
                // if ($("#userSelect").length > 0) {
                //     $(document).ready(function () {
                //         $("#userSelect").each(function (index, element) {
                //             const id = $(element).attr('id');
                //             const choices = new Choices(`#${id}`, {
                //                 removeItemButton: true,
                //                 placeholder: true,
                //                 placeholderValue: 'Select users',
                //                 searchEnabled: true,
                //             });
                //         });
                //     });
                // }
            }
        }
    };


    // Function to log selected values
    function logSelectedValues() {
        const userSelect = document.getElementById('userSelect');
        const departmentSelect = document.getElementById('departmentSelect');
        const designationSelect = document.getElementById('designationSelect');
        const selectedUsers = Array.from(userSelect.selectedOptions).map(option => option.text); // Get text instead of value
        const selectedDepartment = departmentSelect.options[departmentSelect.selectedIndex].text;
        const selectedDesignation = designationSelect.options[designationSelect.selectedIndex].text;

        // Log the selected values
        // console.log('tempblock', tempblock);
        // console.log("Selected Users:", selectedUsers);
        // console.log("Selected Department:", selectedDepartment);
        // console.log("Selected Designation:", selectedDesignation);

        // Clear existing labels to avoid duplicates
        let labelsContainer = tempblock.querySelector('.labels');
        if (!labelsContainer) {
            labelsContainer = document.createElement('div');
            labelsContainer.className = 'labels';
            labelsContainer.style.margin = '0px 0px -25px';
            labelsContainer.style.display = 'flex';
            labelsContainer.style.flexWrap = 'wrap';
            labelsContainer.style.position = 'relative';
            labelsContainer.style.top = '-10px';
            tempblock.querySelector('.blocktext').appendChild(labelsContainer);
        } else {
            labelsContainer.innerHTML = '';
        }
        const usermsg = document.createElement('p');
        usermsg.innerHTML = 'Assigned to user';

        labelsContainer.appendChild(usermsg);
        // Append label for the selected department
        // if (selectedDepartment) {
        //     const departmentLabel = document.createElement('div');
        //     departmentLabel.className = 'label';
        //     departmentLabel.style.backgroundColor = 'green';
        //     departmentLabel.style.color = '#fff';
        //     departmentLabel.style.width = 'fit-content';
        //     departmentLabel.style.padding = '0px 5px';
        //     departmentLabel.style.marginRight = '5px';
        //     departmentLabel.innerText = selectedDepartment;
        //     labelsContainer.appendChild(departmentLabel);
        // }

        // // Append label for the selected designation
        // if (selectedDesignation) {
        //     const designationLabel = document.createElement('div');
        //     designationLabel.className = 'label';
        //     designationLabel.style.backgroundColor = 'purple';
        //     designationLabel.style.color = '#fff';
        //     designationLabel.style.width = 'fit-content';
        //     designationLabel.style.padding = '0px 5px';
        //     designationLabel.style.marginRight = '5px';
        //     designationLabel.innerText = selectedDesignation;
        //     labelsContainer.appendChild(designationLabel);
        // }
        // // Append labels for selected users
        // selectedUsers.forEach(user => {
        //     const label = document.createElement('div');
        //     label.className = 'label';
        //     label.style.backgroundColor = 'blue';
        //     label.style.color = '#fff';
        //     label.style.width = 'fit-content';
        //     label.style.padding = '0px 5px';
        //     label.style.marginRight = '5px';
        //     label.innerText = user;
        //     labelsContainer.appendChild(label);
        // });
    }


    function deleteBlock(blockId) {
        // console.log(
        //     document.querySelector(
        //         'input[name="blockid"][value="' + blockId + '"]'
        //     )
        // );

        var blockToRemove = document
            .querySelector('input[name="blockid"][value="' + blockId + '"]')
            .closest(".block");
        if (blockToRemove) {
            blockToRemove.remove();
        }
        document.getElementById("properties").classList.remove("expanded");
        document.getElementById("propwrap").classList.remove("itson");
        rightcard = false;
    }

    addEventListener("mousedown", beginTouch, false);
    addEventListener("mousemove", checkTouch, false);
    addEventListener("mouseup", doneTouch, false);
    addEventListenerMulti("touchstart", beginTouch, false, ".block");
    // $(document).ready(function () {
    //     $('#userSelect').select2({
    //         placeholder: "Select users",
    //         allowClear: true
    //     });
    // });


});


