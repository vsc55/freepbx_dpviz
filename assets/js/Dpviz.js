function loadScript(url, callback) {
    const script = document.createElement('script');
    script.src = url;
    script.type = 'text/javascript';
    script.onload = function() {
        // console.log(`✅ Script loaded: ${url}`);
        if (callback) callback();
    };
    script.onerror = function() {
        console.error(`❌ Failed to load script: ${url}`);
    };
    document.head.appendChild(script);
}

function enableToolbarButtons() {
    $('.btn-toolbar').find('button, input, select, textarea').prop('disabled', false);
}

function disableToolbarButtons() {
    $('.btn-toolbar').find('button, input, select, textarea').prop('disabled', true);
}

$(document).ready(function() {

    // loadScript('modules/dpviz/assets/js/viz.min.js', function() {
    //     console.log('Script is ready, you can call its functions now.');
    // });

    loadScript('modules/dpviz/assets/js/viz.min.js');
    loadScript('modules/dpviz/assets/js/full.render.js');
    loadScript('modules/dpviz/assets/js/panzoom.min.js');
    loadScript('modules/dpviz/assets/js/html2canvas.min.js');

    // create namespaces
    window.dpviz                   = window.dpviz || {};
    window.dpviz.settings          = window.dpviz.settings || {};
    window.dpviz.settingsLoaded    = false;
    window.dpviz.settingsIsLoading = false;

    window.dpviz.viz              = null;
    window.dpviz.isFocused        = false;
    window.dpviz.svgContainer     = null;
    window.dpviz.selectedNodeId   = null;
    window.dpviz.originalLinks    = null;
    window.dpviz.highlightedEdges = null;
    window.dpviz.ext              = '';
    window.dpviz.cid              = '';

    // Read settings
    getSettings();

    //load side bar if svgContainer is empty
    if (!window.dpviz.svgContainer)
    {
        // Wait for the element to exist before modifying it
        let checkExist = setInterval(function () {
            let $navbar = $('#floating-nav-bar');
            if ($navbar.length) {
                $navbar.addClass('show');
                clearInterval(checkExist);
            }
        }, 500); // Check every 500m
    }

    //github update check
    $('#check-update-btn').click(function() {
        $('#update-result').html('<div style="margin-top: 10px;">Checking...</div>');

        const $btn = $(this);
        $btn.removeClass("btn-primary btn-success btn-danger").addClass("btn-primary");

        const post_data = {
            'module': 'dpviz',
            'command': 'check_update'
        };

        $.ajax({
            url: window.FreePBX.ajaxurl,
            method: 'POST',
            data: post_data,
            dataType: 'json',

            success: function(response)
            {
                $('#update-result').html('');
                if (response.status === 'success')
                {
                    $btn.addClass("btn-success");
                    fpbxToast(response.message, '', 'success');
                    if (response.up_to_date)
                    {
                        // $('#update-result').html('<div style="margin-top: 10px;">You are up to date.</div>');
                    }
                    else
                    {
                        $('#update-result').html(
                            '<a href="https://github.com/madgen78/dpviz/releases/latest" target="_blank" class="btn btn-default">' + response.latest + ' available! View on <i class="fa fa-github"></i> GitHub <i class="fa fa-external-link" aria-hidden="true"></i></a>'
                        );
                    }
                }
                else
                {
                    $btn.addClass("btn-danger");
                    fpbxToast(response.message, '', 'error');
                    // $('#update-result').html('Error: ' + response.message);
                }

                // Optional: Reset the button after a delay
                setTimeout(() => {
                    $btn.removeClass("btn-success btn-danger").addClass("btn-primary");
                }, 3000);
            },
            error: function(xhr, status, error) {
                $btn.addClass("btn-danger");
                // $('#update-result').html('AJAX error: ' + error);
                fpbxToast('AJAX error: ' + error, '', 'error' );

                // Optional: Reset the button after a delay
                setTimeout(() => {
                    $btn.removeClass("btn-danger").addClass("btn-primary");
                }, 3000);
            }
        });
    });




    /**
     * Download dialplan diagram as image
     */
    $(document).on('click', '.export-option-scale', function (e)
    {
        e.preventDefault();

        const scale    = parseFloat($(this).data('scale') || 1);
        const filename = $('#filename_input').val() || '';
        if (! filename)
        {
            fpbxToast(_("Error: Filename is Empty!"), '', 'error');
            return false;
        }

        const container = $('#vizContainer')[0];
        html2canvas(container, {
            scale: scale,
            useCORS: true,
            allowTaint: true
        }).then(function(canvas) {
            const imgData = canvas.toDataURL("image/png");
            triggerDownload(imgData, filename);
        }).catch(function(error) {
            console.error(_('❌ Error exporting image:'), error);
        });
    });

    function triggerDownload(uri, filename)
    {
        const link = document.createElement('a');
        if ('download' in link)
        {
            link.href     = uri;
            link.download = filename;

            //Firefox requires the link to be in the body
            document.body.appendChild(link);
            //simulate click
            link.click();
            //remove the link when done
            document.body.removeChild(link);
        }
        else
        {
            window.open(uri);
        }
    }




    /**
     * Code settings
     */

    /**
     * Retrieves the backend settings for the dpviz module and updates window.dpviz.
     *
     * This function ensures only one active request runs at a time by caching the promise
     * in window.dpviz.settingsPromise. It optionally clears or validates the current settings
     * object before making the request. On completion, it updates window.dpviz.settings
     * and status flags like window.dpviz.settingsLoaded and window.dpviz.settingsIsLoading.
     *
     * @param {Object} [options={}] - Optional configuration object.
     * @param {boolean} [options.clear=false] - If true, forcibly resets window.dpviz.settings to an empty object before loading.
     *                                          If false (default), checks if window.dpviz.settings is a plain object; if not, resets it.
     *
     * @returns {Promise<boolean>} - A promise that:
     *                               - resolves to true if the settings were successfully loaded,
     *                               - resolves to false if the backend responded with a logical error,
     *                               - or rejects with an Error if a network or unexpected error occurs.
     *
     * Usage example:
     * getSettings({ clear: true })
     *     .then(success => {
     *         if (success) {
     *             console.log("✅ Settings loaded:", window.dpviz.settings);
     *         } else {
     *             console.warn("⚠ Settings response contained a logical error.");
     *         }
     *     })
     *     .catch(error => {
     *         console.error("❌ Failed to load settings:", error.message);
     *     });
     *
     * Simple usage example:
     * getSettings().then(success => {
     *     if (success) {
     *         console.log("✅ Settings loaded");
     *     }
     * });
     *
     */
    function getSettings(options = {})
    {
        const { clear = false } = options;

        // Check if getSettings is running and return the promise if it is
        if (window.dpviz.settingsPromise) {
            return window.dpviz.settingsPromise;
        }

        if (clear)
        {
            window.dpviz.settings = {};
        }
        else if (Object.prototype.toString.call(window.dpviz.settings) !== '[object Object]')
        {
            window.dpviz.settings = {};
        }

        window.dpviz.settingsLoaded    = false;
        window.dpviz.settingsIsLoading = true;

        const promise = new Promise((resolve, reject) => {
            const post_data = {
                module: 'dpviz',
                command: 'get_settings'
            };

            $.post(window.FreePBX.ajaxurl, post_data, 'json')
            .done(function (response)
            {
                try
                {
                    if (response && response.status === "success" && typeof response.settings === 'object')
                    {
                        window.dpviz.settings       = response.settings;
                        window.dpviz.settingsLoaded = true;
                        return resolve(true);
                    }

                    const err_msg = response ? response.message || _("⚠ Something went wrong") : _("⚠ Received empty or invalid response");
                    fpbxToast(err_msg, '', 'error');
                    // console.warn(err_msg);
                    return resolve(false);
                }
                catch (error)
                {
                    const err_msg  = sprintf(_("⚠ An unexpected error occurred: %s"), error.message);
                    fpbxToast(err_msg, '', 'error');
                    // console.error(err_msg);
                    return reject(new Error(err_msg));
                }
            })
            .fail(function (xhr, status, error)
            {
                fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
                console.error("Network error:", error || status);
                return reject(new Error("Network error: " + (error || status)));
            })
            .always(function()
            {
                window.dpviz.settingsIsLoading = false;
                window.dpviz.settingsPromise   = null;
            });
        });

        window.dpviz.settingsPromise = promise;
        return promise;
    }

    /**
     * Retrieves the 'panzoom' setting from window.dpviz.settings, ensuring it returns a valid number.
     *
     * This function:
     * - Calls getSettings() to load the latest backend settings.
     * - If the 'panzoom' setting is present and can be converted to a valid number, returns that number.
     * - If 'panzoom' is null, undefined, non-numeric, or if there is an error fetching settings,
     *   it safely returns the provided default value (1).
     *
     * @returns {Promise<number>} A promise that resolves to the numeric value of the 'panzoom' setting,
     *                            or the default value if unavailable or invalid.
     *
     * Example usage:
     * const zoomLevel = await getSettingPanZoom();
     * console.log("PanZoom value:", zoomLevel);
     */
    async function getSettingPanZoom()
    {
        const def_value = 1; // Default value
        try
        {
            if (await getSettings())
            {
                let value = window.dpviz.settings['panzoom'];
                if (value === null) {
                    value = undefined; // force to NaN on Number()
                }
                const num = Number(value);
                return (!isNaN(num)) ? num : def_value;
            }
            return def_value;
        }
        catch (error)
        {
            return def_value;
        }
    }

    $(document).on('click', '#settings_reset', function (e) {
        e.preventDefault();
        fpbxConfirm(
            _("Are you sure you want to reset all settings to default?"),
            _("Yes"),_("No"),
            function() {
                const post_data = {
                    module: 'dpviz',
                    command: 'reset_setting_default'
                };
                $.post(window.FreePBX.ajaxurl, post_data)
                .done(function(response) {
                    if (response.status === "success")
                    {
                        fpbxToast(response.message, '', 'success');
                        location.href = window.location.pathname + window.location.search + '#settings';
                        location.reload();
                    }
                    else
                    {
                        fpbxToast(response.message, '', 'error');
                    }
                }, 'json')
                .fail(function() {
                    fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
                });
            }
        );
    });

    $(document).on('click', '#settings_submit', function (e) {
        e.preventDefault();

        const $form = $(this).closest('form');

        fpbxConfirm(
            _("Are you sure you want to save the settings?"),
            _("Yes"),_("No"),
            function() {

                const formArray = $form.serializeArray();
                const formData = {};
                formArray.forEach(item => {
                    formData[item.name] = item.value;
                });

                const post_data = {
                    module: 'dpviz',
                    command: 'save_settings',
                    data: formData
                };

                $.post(window.FreePBX.ajaxurl, post_data, function (response) {
                    if (response.status === "success")
                    {
                        fpbxToast(response.message, '', 'success');
                        getSettings();

                        //TODO: show question to reload page
                        setTimeout(function() {
                            const pan = getSettingPanZoom();
                            generateVisualization('');
                        }, 1000);

                    }
                    else
                    {
                        fpbxToast(response.message || _("⚠ Something went wrong"), '', 'error');
                    }
                }, 'json').fail(function () {
                    fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
                });

            }
        );
    });


    $('#reload-dpp').on('click', function (e)
    {
        e.preventDefault();
        generateVisualization('');
    });

    function generateVisualization(jump = '')
    {
        disableToolbarButtons();

        const ext = window.dpviz.ext || '';
        const cid = window.dpviz.cid || '';

        const post_data = {
            module: 'dpviz',
            command: 'make',
            ext: ext,
            cid: cid,
            jump: jump,
            clickedNodeTitle: jump
        };

        $.post(window.FreePBX.ajaxurl, post_data, 'json')
        .done(function (response)
        {
            if (response && response.status === "success")
            {
                const $vizContainerHeader = $('#vizContainerHeader');
                const $vizContainerBody   = $('#vizContainerBody');
                const $floatingNavBar     = $('#floating-nav-bar');


                $floatingNavBar.removeClass('show');
                $vizContainerBody.empty();

                $('#vizContainerTitle').html(response.title);
                $('#vizContainerDatetime').html(response.datetime);
                $('#filename_input').val(response.basefilename);

                // Initialize the Viz instance if it doesn't exist
                if (window.dpviz.viz === null)
                {
                    window.dpviz.viz              = new Viz();
                    window.dpviz.isFocused        = false;
                    window.dpviz.svgContainer     = null;
                    window.dpviz.selectedNodeId   = null;
                    window.dpviz.originalLinks    = new Map();
                    window.dpviz.highlightedEdges = new Set(); // Track highlighted edges
                }

                if (response.gtext)
                {
                    let dot = response.gtext
                    .replace(/\\\\n/g, '\n')
                    .replace(/\\n/g, '\n')
                    .replace(/\\\\l/g, '\l')
                    .replace(/\\l/g, '\l');

                    window.dpviz.viz.renderSVGElement(dot)
                    .then(function(element) {
                        window.dpviz.isFocused = false;
                        window.dpviz.svgContainer = element;
                        $vizContainerBody.append(element);

                        const $svgElement = $('#graph0');
                        if ($svgElement.length && response.panzoom === "1")
                        {
                            panzoom($svgElement[0], {
                                zoomDoubleClickSpeed: 1, // disables double click to zoom
                            });
                        }

                        $(element).find('g.node').each(function () {
                            $(this).on('click', function (e) {

                                if (e.ctrlKey || e.metaKey) {  // Ctrl on Windows/Linux, Command on Mac
                                    e.preventDefault();

                                    let titleElement = $(this).find('title');
                                    if (titleElement.length)
                                    {
                                        let titleText = titleElement.text() || titleElement.html();
                                        generateVisualization(titleText);
                                    }
                                    return false;
                                }

                                if (window.dpviz.isFocused) {
                                    window.dpviz.selectedNodeId = this.id;
                                    highlightPathToNode(this.id);
                                    e.preventDefault();
                                    e.stopPropagation();
                                    return false;
                                }
                            });
                        });

                        $(element).find('g.edge').each(function () {
                            $(this).on('click', function (e) {
                                if (window.dpviz.isFocused) {
                                    toggleEdgeHighlight(this.id);
                                    e.preventDefault();
                                    e.stopPropagation();
                                    return false;
                                }
                            });
                        });

                        enableToolbarButtons();
                        fpbxToast(response.message, '', 'success');
                    })
                    .catch(error => {
                        fpbxToast('Viz.js render error: ' + error , '', 'error');
                        console.error('Viz.js render error:', error);
                    });
                }
                else
                {
                    fpbxToast('No gtext found in response.', '', 'error');
                    console.error('No gtext found in response.');
                }
            }
            else
            {
                const err_msg = response.message || _("⚠ Something went wrong");
                fpbxToast(err_msg, '', 'error');
                // console.warn(err_msg);
            }
        })
        .fail(function (xhr, status, error)
        {
            fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
            console.error("AJAX network error:", error || status);
        });
    }





    // focus
    // Use the most reliable way to prevent default for focus button
    $(document).on('click', '#toolbar_btn_focus', function (e) {
        e.stopPropagation();
        // Prevent the default action
	    e.preventDefault();

        // Toggle focus mode
        toggleFocusMode();

        // Return false for extra measure
	    return false;
    });

    function toggleEdgeHighlight(edgeId) {
        if (!window.dpviz.svgContainer) return;

        const $edge = $('#' + edgeId);
        if (!$edge.length) return;

        // Check if this edge is already highlighted
        if (window.dpviz.highlightedEdges.has(edgeId))
        {
            // Remove highlight
            window.dpviz.highlightedEdges.delete(edgeId);

            // Reset edge style
            $edge.find('path').css({ stroke: '', strokeWidth: '' });
            // Reset arrowhead
            $edge.find('polygon').css({ fill: '', stroke: '' });
            // Reset edge text
            $edge.find('text').css({ fill: '', fontWeight: '' });
        }
        else
        {
            // Add highlight
            window.dpviz.highlightedEdges.add(edgeId);

            // Highlight edge
            $edge.find('path').css({ stroke: 'red', strokeWidth: '3px' });
            // Highlight arrowhead
            $edge.find('polygon').css({ fill: 'red', stroke: 'red' });
            // Highlight edge text
            $edge.find('text').css({ fill: 'red', fontWeight: 'bold' });
        }
    }

    function resetEdges() {
        if (!window.dpviz.svgContainer) return;

        // Clear highlighted edges set
        window.dpviz.highlightedEdges.clear();

        // Reset only edge paths
        $(window.dpviz.svgContainer).find('g.edge path').css({ stroke: '', strokeWidth: '' });
        // Reset only arrowheads in edges
        $(window.dpviz.svgContainer).find('g.edge polygon').css({ fill: '', stroke: '' });
        // Reset edge text (labels)
        $(window.dpviz.svgContainer).find('g.edge text').css({ fill: '', fontWeight: '' });
    }

    function toggleFocusMode() {
        if (!window.dpviz.svgContainer) return;

        const $btn = $('#toolbar_btn_focus');

        if (window.dpviz.isFocused) {
            // Exit focus mode
            resetEdges();
            restoreLinks();
            window.dpviz.isFocused = false;

            $btn.text(_('Highlight Paths'))
                .removeClass('active')
                .addClass('btn-default');
        }
        else
        {
            // Enter focus mode
            disableLinks();
            window.dpviz.isFocused = true;

            $btn.text(_('Remove Highlights'))
                .addClass('active')
                .removeClass('btn-default');
        }
    }

    function disableLinks() {
        if (!window.dpviz.svgContainer) return;

        // Block all node clicks to their URL destinations
        window.dpviz.svgContainer.querySelectorAll("g.node a").forEach(link => {
            if (link.hasAttribute("xlink:href")) {
                window.dpviz.originalLinks.set(link, link.getAttribute("xlink:href"));
                link.setAttribute("xlink:href", "javascript:void(0);");
            }
        });
    }

    function restoreLinks() {
        if (!window.dpviz.svgContainer) return;

        // Restore original hrefs
        window.dpviz.svgContainer.querySelectorAll("g.node a").forEach(link => {
            const originalHref = window.dpviz.originalLinks.get(link);
            if (originalHref) {
                link.setAttribute("xlink:href", originalHref);
            }
        });

        // Clear stored links
        window.dpviz.originalLinks.clear();
    }

    function highlightPathToNode(nodeId) {
        if (!window.dpviz.svgContainer) return;

        // First reset all edges
        resetEdges();

        // Get the title content of the node to find its name
        const node = document.getElementById(nodeId);
        if (!node) return;

        const nodeTitle = node.querySelector("title");
        if (!nodeTitle) return;

        const targetNodeName = nodeTitle.textContent;

        // Track all nodes that are part of the path
        const visitedNodes = new Set([targetNodeName]);
        // Track all edges we've processed to avoid duplicates
        const processedEdges = new Set();

        // Recursively find all nodes that lead to our target
        function findConnectedNodes(nodeName) {
            window.dpviz.svgContainer.querySelectorAll("g.edge").forEach(edge => {
                // Skip edges we've already processed
                if (processedEdges.has(edge.id)) return;

                const edgeTitle = edge.querySelector("title");
                if (!edgeTitle || !edgeTitle.textContent.includes("->")) return;

                const [sourceNode, destNode] = edgeTitle.textContent.split("->");

                // If this edge points to our node, highlight it regardless of whether we've visited the source
                if (destNode.trim() === nodeName) {
                    // Mark this edge as processed
                    processedEdges.add(edge.id);

                    // Add the source to our visited set
                    const sourceNodeName = sourceNode.trim();
                    visitedNodes.add(sourceNodeName);

                    // Highlight this edge
                    const edgePath = edge.querySelector("path");
                    if (edgePath) {
                        edgePath.style.stroke = "red";
                        edgePath.style.strokeWidth = "3px";
                    }

                    // Highlight arrowhead
                    const polygon = edge.querySelector("polygon");
                    if (polygon) {
                        polygon.style.fill = "red";
                        polygon.style.stroke = "red";
                    }

                    // Highlight edge text (labels)
                    const textElements = edge.querySelectorAll("text");
                    textElements.forEach(text => {
                        text.style.fill = "red";
                        text.style.fontWeight = "bold";
                    });

                    // Recursively find nodes that lead to this source
                    findConnectedNodes(sourceNodeName);
                }
            });
        }

        // Start the recursive search from our target node
        findConnectedNodes(targetNodeName);
    }




    /**
     * Code rnav (bootstrap table)
     * dpviz.rnav – Destination column control for BootstrapTable
     *
     * - Loads destination data via AJAX (only once per session).
     * - After loading, updates visible table cells with the corresponding destination info.
     * - While loading, displays "Loading..." with a spinner icon.
     * - If loading fails after several retries, displays "⚠ Error" in the cell.
     * - Retries are handled per-cell with a defined delay and max attempts.
     * - All state (data and flags) is stored in `window.dpviz.rnav` for global access.
     * - Includes a formatter for the "extension / cidnum" column.
     * - Handles row click for redirection and shows the navbar if needed.
     */

    // Namespace for rnav
    window.dpviz.rnav = window.dpviz.rnav || {};

    // Set default values
    window.dpviz.rnav.destinations       = {};
    window.dpviz.rnav.destinationsReady  = false;
    window.dpviz.rnav.destinationsLoaded = false;

    function dpvizRNavLoadDestinations()
    {
        if (window.dpviz.rnav.destinationsLoaded) return;

        window.dpviz.rnav.destinationsLoaded = true;
        window.dpviz.rnav.destinationsReady  = false;
        window.dpviz.rnav.destinations       = {};

        const post_data = {
            'module' : 'dpviz',
            'command': 'get_destinations'
        };
        $.post(window.FreePBX.ajaxurl, post_data, function (response)
        {
            if (response.status === "success" && response.destinations)
            {
                window.dpviz.rnav.destinations      = response.destinations;
                window.dpviz.rnav.destinationsReady = true;
            }
            else
            {
                fpbxToast(response.message || _("⚠ Unknown error while loading destinations"), '', 'error');
            }
        }, 'json').fail(function ()
        {
            fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
        });
    }

    // Formatter the "destination" column
    window.DIDdestFormatter = function (value, row, index)
    {
        if (!value) return _("No Destination");

        if (!window.dpviz.rnav.destinationsReady)
        {
            if (value === '__dpviz_error__') {
                return '<span class="text-danger">' + _('⚠ Error loading destination') + '</span>';
            }

            retryUpdateDestinationCell(index, value);
            return '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + _('Loading...') + '</span>';
        }

        const dest = window.dpviz.rnav.destinations?.[value];
        if (!dest) return value;

        const prefix = dest.category || dest.name;
        return `${prefix}: ${dest.description}`;
    };

    function retryUpdateDestinationCell(index, value, attempt = 0)
    {
        // Maximum number of attempts and delay, e.g., 10 attempts with 500ms delay = 5 seconds
        const MAX_ATTEMPTS = 10;
        const DELAY_MS     = 500; // 500ms delay

        if (window.dpviz.rnav.destinationsReady)
        {
            $('#dpviz-side').bootstrapTable('updateCell', { index: index, field: 'destination', value: value });
        }
        else if (attempt < MAX_ATTEMPTS)
        {
            setTimeout(() => { retryUpdateDestinationCell(index, value, attempt + 1); }, DELAY_MS);
        }
        else
        {
            $('#dpviz-side').bootstrapTable('updateCell', { index: index, field: 'destination', value: '__dpviz_error__' });
        }
    }

    // Formatter for "extension / cidnum" column
    window.bootnavvizFormatter = function (value, row)
    {
        const extension = decodeURIComponent(row['extension'] || "").trim() || "ANY";
        const cidnum    = decodeURIComponent(row['cidnum'] || "").trim();
        return cidnum ? `${extension} / ${cidnum}` : extension;
        return cidnum ? sprintf("%s / %s", extension, cidnum) : extension;
    };

    // Click over row → redirect
    $(document).on('click-row.bs.table', '#dpviz-side', function (e, row)
    {
        //e.preventDefault();

        window.dpviz.ext = decodeURIComponent(row['extension'] || '');
        window.dpviz.cid = decodeURIComponent(row['cidnum'] || '');

        generateVisualization('');
    });

    $('#dpviz-side').on('post-body.bs.table', function () {
        dpvizRNavLoadDestinations();
    });
});
