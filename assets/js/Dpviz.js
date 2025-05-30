function loadScript(url, callback)
{
    const script = document.createElement('script');
    script.src = url;
    script.type = 'text/javascript';
    script.onload = function()
    {
        console.log(sprintf(_("✅ Script loaded: %s"), url));
        if (callback) callback();
    };
    script.onerror = function()
    {
        console.error(sprintf(_("❌ Failed to load script: %s"), url));
    };
    document.head.appendChild(script);
}

function loadScripts(urls, callback)
{
    const promises = urls.map(url => new Promise((resolve, reject) => {
        loadScript(url, resolve);
    }));
    Promise.all(promises).then(callback).catch(err => console.error(_("❌ Script loading error:"), err));
}

function enableToolbarButtons()
{
    $('.btn-toolbar').find('button, input, select, textarea, spinner').prop('disabled', false);
}

function disableToolbarButtons()
{
    // $('.btn-toolbar').find('button, input, select, textarea, spinner').prop('disabled', true);
    $('.btn-toolbar').find('button, input, select, textarea, spinner').not('#list_inbound_routes_reload, #list_inbound_routes *').prop('disabled', true);
}

function safeDecode(value, defaultValue = '')
{
    return decodeURIComponent(value || defaultValue).trim();
}

function i18nLoadStrings()
{
    const post_data = {
        module: 'dpviz',
        command: 'get_i18n'
    };
    return $.post(window.FreePBX.ajaxurl, post_data, 'json')
    .done(function (response)
    {
        if (response.status === "success" && response.i18n)
        {
            window.dpviz.i18nStrings = response.i18n;
            console.log(_("✅ i18n strings loaded successfully"));
        }
        else
        {
            const err_msg = response ? response.message || _("⚠ Something went wrong") : _("⚠ Received empty or invalid response");
            fpbxToast(err_msg, '', 'error');
            console.error(err_msg);
        }
    })
    .fail(function (xhr, status, error)
    {
        fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
        console.error(_("❌ Network error:"), error || status);
    });
}
async function i18nAwait(key)
{
    if (! window.dpviz.i18nStrings || ! window.dpviz.i18nStrings[key])
    {
        await i18nLoadStrings();
    }
    if (window.dpviz.i18nStrings && window.dpviz.i18nStrings[key])
    {
        return window.dpviz.i18nStrings[key];
    }
    return key;
}

function i18nStr(key)
{
    if (window.dpviz.i18nStrings && window.dpviz.i18nStrings[key])
    {
        return window.dpviz.i18nStrings[key];
    }
    return key;
}

$(document).ready(function()
{
    loadScripts([
        'modules/dpviz/assets/js/viz.min.js',
        'modules/dpviz/assets/js/full.render.js',
        'modules/dpviz/assets/js/panzoom.min.js',
        'modules/dpviz/assets/js/svg-pan-zoom.min.js',
        'modules/dpviz/assets/js/html2canvas.min.js',
    ]);

    // create namespaces
    window.dpviz = Object.assign({
        settings: {},
        rnav: {
            destinations: {},
            destinationsReady: false,
            destinationsLoaded: false
        },
        settingsLoaded: false,
        settingsIsLoading: false,
        viz: null,
        isFocused: false,
        svgContainer: null,
        selectedNodeId: null,
        originalLinks: new Map(),
        highlightedEdges: new Set(),
        ext: '',
        cid: '',
        i18nStrings: {}
    }, window.dpviz);

    // Load i18n strings
    i18nLoadStrings();

    // Read settings
    getSettings();

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

                    const err_msg = response ? response.message || i18nStr('ajax_response_status_err') : i18nStr('ajax_response_empty');
                    fpbxToast(err_msg, '', 'error');
                    // console.warn(err_msg);
                    return resolve(false);
                }
                catch (error)
                {
                    const err_msg  = sprintf(i18nStr('settings_get_error'), error.message);
                    fpbxToast(err_msg, '', 'error');
                    // console.error(err_msg);
                    return reject(new Error(err_msg));
                }
            })
            .fail(function (xhr, status, error)
            {
                fpbxToast(i18nStr('ajax_failed'), '', 'error');
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
     * Event handler for various button clicks in the dpviz module.
     * - Handles settings reset and submit actions.
     * - Generates a visualization based on the clicked node.
     * - Toggles focus mode for the visualization.
     * - Handles export options for the visualization.
     * - Checks for updates to the dpviz module.
     */
    $(document).on('click', function(e)
    {
        const target = $(e.target);

        if (target.is('#settings_reset'))
        {
            handleSettingsReset(e);
        }
        else if (target.is('#settings_submit'))
        {
            handleSettingsSubmit(e, target);
        }
        else if (target.is('#reload-dpp'))
        {
            e.preventDefault();
            generateVisualization('');
        }
        else if (target.is('#toolbar_btn_focus'))   // Use the most reliable way to prevent default for focus button
        {
            e.stopPropagation();
            // Prevent the default action
            e.preventDefault();

            // Toggle focus mode
            toggleFocusMode(e, target);

            // Return false for extra measure
            return false;
        }
        else if (target.hasClass('export-option-scale'))
        {
            handleExportClick(e, target);
        }
        else if (target.is('#check-update-btn'))
        {
            handleUpdateClick(e, target);
        }
    });

    function handleSettingsReset(e)
    {
        e.preventDefault();
        fpbxConfirm(
            i18nStr("reset_settings_confirm"),
            i18nStr("yes"), i18nStr("no"),
            function() {
                const post_data = {
                    module: 'dpviz',
                    command: 'reset_setting_default'
                };
                $.post(window.FreePBX.ajaxurl, post_data, 'json')
                .done(function(response)
                {
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
                })
                .fail(function()
                {
                    fpbxToast(i18nStr('ajax_failed'), '', 'error');
                });
            }
        );
    };

    function handleSettingsSubmit(e, target)
    {
        e.preventDefault();

        const form = target.closest('form');

        fpbxConfirm(
            i18nStr("submit_settings_confirm"),
            i18nStr("yes"), i18nStr("no"),
            function()
            {
                const formArray = form.serializeArray();
                const formData  = {};
                formArray.forEach(item => {
                    formData[item.name] = item.value;
                });

                const post_data = {
                    module: 'dpviz',
                    command: 'save_settings',
                    data: formData
                };
                $.post(window.FreePBX.ajaxurl, post_data, 'json')
                .done(function(response)
                {
                    if (response.status === "success")
                    {
                        fpbxToast(response.message, '', 'success');
                        getSettings();

                        // Onley generate if the svgContainer is not empty (something has already been selected)
                        if (window.dpviz.svgContainer)
                        {
                            // TODO: Not generating correctly if not in the Dial plane tab
                            // Reload the visualization after settings are saved
                            // setTimeout(function()
                            // {
                            //     generateVisualization('');
                            // }, 1000);
                        }
                    }
                    else
                    {
                        const err_msg = response ? response.message || i18nStr('ajax_response_status_err') : i18nStr('ajax_response_empty');
                        fpbxToast(err_msg, '', 'error');
                    }
                })
                .fail(function ()
                {
                    fpbxToast(i18nStr('ajax_failed'), '', 'error');
                });
            }
        );
    };

    function generateVisualization(jump = '')
    {
        disableToolbarButtons();

        const vizContainerHeader   = $('#vizContainerHeader');
        const vizContainerBody     = $('#vizContainerBody');
        const vizSpinner           = $('#vizSpinner');
        const vizContainerTitle    = $('#vizContainerTitle');
        const vizContainerDatetime = $('#vizContainerDatetime');
        const filenameInput        = $('#filename_input');
        const modal                = $('#recordingmodal');
	    const overlay              = $('#overlay');

        vizContainerHeader.hide();
        vizContainerBody.hide();
        vizSpinner.show();

        vizContainerBody.empty();     //clear contents
        vizContainerTitle.empty();    //clear title
        vizContainerDatetime.empty(); //clear datetime
        filenameInput.val('');        //clear filename input

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
                vizContainerTitle.html(response.title);         // Set the title
                vizContainerDatetime.html(response.datetime);   // Set the datetime
                filenameInput.val(response.basefilename);       // Set the filename input

                vizContainerHeader.show();

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
                    let dot = response.gtext.replace(/\\\\n/g, '\n').replace(/\\n/g, '\n');

                    window.dpviz.viz.renderSVGElement(dot)
                    .then(function(element) {
                        window.dpviz.isFocused = false;
                        window.dpviz.svgContainer = element;
                        vizContainerBody.append(element);
                        vizSpinner.hide();  //hide spinner

                        const $svgElement = $('#graph0');
                        const $svgRoot    = $svgElement.closest('svg');

                        if ($svgElement.length && window.dpviz.settings.panzoom === "1")
                        {
                            panzoom($svgElement[0], {
                                zoomDoubleClickSpeed: 1, // disables double click to zoom
                            });
                            // svgPanZoom($svgRoot[0], {
                            //     zoomEnabled: true,           // enables zooming
                            //     controlIconsEnabled: true,   // active control icons
                            //     fit: true,                   // fit the SVG to the viewport
                            //     center: true,                // center the SVG in the viewport
                            //     panEnabled: true,            // enables panning
                            //     mouseWheelZoomEnabled: true, // enables mouse wheel zoom
                            //     minZoom: 0.5,                // minimum zoom level
                            //     maxZoom: 10,                 // maximum zoom level
                            //     zoomScaleSensitivity: 0.2,   // controls how fast the wheel zooms (higher = faster)
                            //     dblClickZoomEnabled: false,  // disables double click to zoom
                            //     // beforeZoom: function(oldZoom, newZoom) {
                            //     //     console.log('Antes del zoom:', oldZoom, '→', newZoom);
                            //     //     return true; // si devuelves false, cancela el zoom
                            //     // },
                            //     // onZoom: function(zoom) {
                            //     //     console.log('Después del zoom:', zoom);
                            //     // },
                            //     // beforePan: function(oldPan, newPan) {
                            //     //     console.log('Antes del pan:', oldPan, '→', newPan);
                            //     //     return true;
                            //     // },
                            //     // onPan: function(pan) {
                            //     //     console.log('Después del pan:', pan);
                            //     // }
                            // });
                        }

                        // Ctrl/Command + click handler for Graphviz nodes
                        $(element).find('g.node').on('click', function (e)
                        {
                            const $node        = $(this);
                            const titleElement = $node.find('title');

                            if (!titleElement) return;

                            const titleText = titleElement.text() || titleElement.html();

                            // Check for "Play Recording:" pattern
                            if (titleText.startsWith("play-system-recording"))
                            {
                                alert("Play system recording: " + titleText);
                                e.preventDefault();
                                if (modal && overlay && !window.dpviz.isFocused)
                                {
                                    overlay.style.display = 'block';
                                    spinner.style.display = "flex";
                                    getRecording(titleText);

                                    setTimeout(() => {
                                        spinner.style.display = "none";
                                        modal.style.display   = 'block';
                                    }, 750);
                                }
                            }

                            if (e.ctrlKey || e.metaKey) {  // Ctrl on Windows/Linux, Command on Mac
                                e.preventDefault();
                                generateVisualization(titleText);
                                // return false;
                            }

                            if (window.dpviz.isFocused) {
                                window.dpviz.selectedNodeId = this.id;
                                highlightPathToNode(this.id);
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
                        });

                        $(element).find('g.edge').on('click', function (e)
                        {
                            if (window.dpviz.isFocused) {
                                toggleEdgeHighlight(this.id);
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
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
                const err_msg = response ? response.message || i18nStr('ajax_response_status_err') : i18nStr('ajax_response_empty');
                fpbxToast(err_msg, '', 'error');
                // console.warn(err_msg);
            }
        })
        .fail(function (xhr, status, error)
        {
            fpbxToast(i18nStr('ajax_failed'), '', 'error');
            console.error("AJAX network error:", error || status);
        });
    }

    function handleExportClick(e, target)
    {
        e.preventDefault();

        const scale    = parseFloat(target.data('scale') || 1);
        const filename = $('#filename_input').val() || '';

        const container     = $('#vizContainer')[0];
        const containerBody = $('#vizContainerBody');
        const controlsIcons = containerBody.find('.svg-pan-zoom-control');

        // Hide the controls
        if (controlsIcons.length)
        {
            controlsIcons.hide();
        }

        html2canvas(container, {
            scale: scale,
            useCORS: true,
            allowTaint: true
        }).then(function(canvas)
        {
            // Restore the controls
            if (controlsIcons.length)
            {
                controlsIcons.show();
            }

            const imgData = canvas.toDataURL("image/png");
            triggerDownload(imgData, filename);
        })
        .catch(function(error)
        {
            // Restore the controls
            if (controlsIcons.length)
            {
                controlsIcons.show();
            }
            console.error(i18nStr('export_error_image'), error);
        });
    };

    function triggerDownload(uri, filename)
    {
        if (!uri) return false;

        if ('download' in document.createElement('a') && filename !== '')
        {
            //Firefox requires the link to be in the body
            const $link = $('<a>').attr('href', uri).attr('download', filename).appendTo('body');
            $link[0].click();       //simulate click
            $link.remove();         //remove the link when done
        }
        else
        {
            const newWindow = window.open(uri);
            if (!newWindow)
            {
                fpbxToast(i18nStr('export_blocked_popup'), '', 'error');
                return false;
            }
        }
        return true;
    }

    function handleUpdateClick(e, target)
     {
        e.preventDefault();

        $('#update-result').html('<div style="margin-top: 10px;">Checking...</div>');

        target.removeClass("btn-primary btn-success btn-danger").addClass("btn-primary");

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
                    target.addClass("btn-success");
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
                    target.addClass("btn-danger");
                    fpbxToast(response.message, '', 'error');
                    // $('#update-result').html('Error: ' + response.message);
                }

                // Optional: Reset the button after a delay
                setTimeout(() => {
                    target.removeClass("btn-success btn-danger").addClass("btn-primary");
                }, 3000);
            },
            error: function(xhr, status, error) {
                target.addClass("btn-danger");
                // $('#update-result').html('AJAX error: ' + error);
                fpbxToast('AJAX error: ' + error, '', 'error' );

                // Optional: Reset the button after a delay
                setTimeout(() => {
                    target.removeClass("btn-danger").addClass("btn-primary");
                }, 3000);
            }
        });
    };



    // focus
    function toggleEdgeHighlight(edgeId)
    {
        if (!window.dpviz.svgContainer) return;

        const $edge = $('#' + edgeId);
        if (!$edge.length) return;

        // Check if this edge is already highlighted
        if (window.dpviz.highlightedEdges.has(edgeId))
        {
            // Remove highlight
            window.dpviz.highlightedEdges.delete(edgeId);
            resetEdgeStyle($edge);
        }
        else
        {
            // Add highlight
            window.dpviz.highlightedEdges.add(edgeId);
            applyEdgeHighlight($edge);
        }
    }

    function resetEdges()
    {
        if (!window.dpviz.svgContainer) return;

        // Clear highlighted edges set
        window.dpviz.highlightedEdges.clear();

        $(window.dpviz.svgContainer).find('g.edge').each(function() {
            resetEdgeStyle($(this));
        });
    }

    function applyEdgeHighlight($edge)
    {
        if (!$edge.length) return;
        $edge.find('path').css({ stroke: 'red', strokeWidth: '3px' });  // Highlight edge
        $edge.find('polygon').css({ fill: 'red', stroke: 'red' });      // Highlight arrowhead
        $edge.find('text').css({ fill: 'red', fontWeight: 'bold' });    // Highlight edge text
    }

    function resetEdgeStyle($edge)
    {
        if (!$edge.length) return;
        $edge.find('path').css({ stroke: '', strokeWidth: '' });    // Reset only edge paths
        $edge.find('polygon').css({ fill: '', stroke: '' });        // Reset only arrowheads in edges
        $edge.find('text').css({ fill: '', fontWeight: '' });       // Reset edge text (labels)
    }

    function toggleFocusMode(e, target)
    {
        if (!window.dpviz.svgContainer) return;

        const txt = target.contents().filter(function()
        {
            return this.nodeType === 3 && $.trim(this.nodeValue) !== '';
        });

        if (window.dpviz.isFocused)
        {
            // Exit focus mode
            resetEdges();
            restoreLinks();
            window.dpviz.isFocused = false;

            txt[0].nodeValue = sprintf(' %s', i18nStr('btn_highlight'));
            target.removeClass('active btn-primary').addClass('btn-default');
        }
        else
        {
            // Enter focus mode
            disableLinks();
            window.dpviz.isFocused = true;

            txt[0].nodeValue = sprintf(' %s', i18nStr('btn_highlight_remove'));
            target.removeClass('btn-default btn-primary').addClass('active');
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


    function appendPlaceholderOption(select, textKey)
    {
        select.append($('<option>', {
            value: '',
            text: i18nStr(textKey),
            disabled: true,
            selected: true
        }));
    }

    function createInboundOption(item, loading = true)
    {
        const description = item.description || '';
        const extension   = safeDecode(item.extension) || i18nStr("ANY");
        const cidnum      = safeDecode(item.cidnum);
        const name        = (cidnum !== "") ? sprintf("%s / %s", extension, cidnum) : extension;
        let subtext       = sprintf(_("From %s To %s"), description, item.destination);

        if (loading)
        {
            subtext = sprintf(_("From %s || %s"), description, i18nStr('inbound_routes_loading_dest'));
        }
        return $('<option>', {
            value: item.destination,
            text: name,
            'data-subtext': subtext,
            'data-cidnum': item.cidnum || '',
            'data-extension': item.extension || '',
            'data-description': description,
            'data-destination': item.destination
        });
    }

    function getPrettyNameDestination(e, value)
    {
        const description        = e.data('description');
        const defaultDestination = e.data('destination');
        let displayDestination   = sprintf(_("From %s To %s"), description, defaultDestination);

        if (!window.dpviz.rnav.destinationsReady)
        {
            if (value === '__dpviz_error__')
            {
                displayDestination = sprintf("%s || %s", displayDestination, i18nStr('destination_err_loading'));
            }
            else
            {
                displayDestination = sprintf(_("From %s || %s"), description, i18nStr('inbound_routes_loading_dest'));
            }
        }
        else
        {
            const dest = window.dpviz.rnav.destinations?.[value];
            if (dest && dest !== defaultDestination)
            {
                const prefix = dest.category || dest.name || '';
                displayDestination = sprintf(_("From %s To %s: %s"), description, prefix, dest.description);
            }
        }
        e.attr('data-subtext', displayDestination);
        e.closest('select').selectpicker('refresh');
    }

    function retryUpdateDestinationCell(e, value, attempt = 0)
    {
        // Maximum number of attempts and delay, e.g., 10 attempts with 500ms delay = 5 seconds (5/0.5 = 10)
        const MAX_ATTEMPTS = 80;  // 40 seconds timeout
        const DELAY_MS     = 500; // 500ms delay

        if (window.dpviz.rnav.destinationsReady)
        {
            getPrettyNameDestination(e, value);
        }
        else if (attempt < MAX_ATTEMPTS)
        {
            setTimeout(() => { retryUpdateDestinationCell(e, value, attempt + 1); }, DELAY_MS);
        }
        else
        {
            getPrettyNameDestination(e, '__dpviz_error__');
        }
    }

    function loadSelectOptionsGetDestinations()
    {
        if (window.dpviz.rnav.destinationsLoaded) return;

        window.dpviz.rnav.destinationsLoaded = true;
        window.dpviz.rnav.destinationsReady  = false;
        window.dpviz.rnav.destinations       = {};

        const post_data = {
            'module' : 'dpviz',
            'command': 'get_destinations'
        };
        $.post(window.FreePBX.ajaxurl, post_data, 'json')
        .done(function(response)
        {
            if (response.status === "success" && response.destinations)
            {
                window.dpviz.rnav.destinations      = response.destinations;
                window.dpviz.rnav.destinationsReady = true;
            }
            else
            {
                fpbxToast(response.message || i18nStr('destination_err_unknown'), '', 'error');
            }
        })
        .fail(function ()
        {
            fpbxToast(i18nStr('ajax_failed'), '', 'error');
        });
    }

    function loadSelectOptions(show_message = true)
    {
        loadSelectOptionsGetDestinations();

        const select = $('#list_inbound_routes');
        const reload = $('#list_inbound_routes_reload');

        reload.prop('disabled', true);
        select.empty();
        appendPlaceholderOption(select, "inbound_routes_loading");

        const post_data = {
            module: 'core',
            command: 'getJSON',
            jdata: 'allDID'
        };
        return $.post(this.ajaxurl, post_data, 'json')
        .done(response => {
            select.empty();

            if (response.length === 0)
            {
                appendPlaceholderOption(select, "inbound_routes_empty");
            }
            else
            {
                appendPlaceholderOption(select, "inbound_routes_select");
                response.forEach(item => {
                    const option = createInboundOption(item, true);
                    retryUpdateDestinationCell(option, item.destination);
                    select.append(option);
                });
            }
        })
        .fail((xhr, status, error) => {
            fpbxToast("⚠ Could not connect to the server", '', 'error');
            console.error("❌ Network error:", error || status);
        })
        .always(() => {
            select.selectpicker('refresh'); // Refresh the selectpicker to show the new options
            reload.prop('disabled', false);

            if (show_message)
            {
                fpbxToast(i18nStr('inbound_routes_refresh'), '', 'success');
            }
        });
    }

    $('#list_inbound_routes').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue)
    {
        const selOption = $(this).find('option').eq(clickedIndex); // obtiene el objeto <option> completo
        if (!selOption.length) return;

        // const selValue = $(this).val();
        // const selText = $(this).find('option:selected').text();

        const extension = selOption.data('extension');
        const cidnum    = selOption.data('cidnum');

        window.dpviz.ext = safeDecode(extension);
        window.dpviz.cid = safeDecode(cidnum);
        generateVisualization('');
    });

    $('#list_inbound_routes_reload').on('click', function() {
        loadSelectOptions(true);
    });


    loadSelectOptions(false);





















    function getRecording(titleid) {
        const parts = titleid.split(",");
        const id = parts[1];
        const other = parts[2];
        const lang = parts[3];

        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('lang', lang);

        fetch('ajax.php?module=dpviz&command=getrecording', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        })
        .then(response => {
        if (!response.ok) throw new Error("Failed to load recording info");
        return response.json();
    })
    .then(async data => {
            console.log("Display name:", data.displayname);
            console.log("Filename(s):", data.filename);







            const displayname = data.displayname;
            const audioList = document.getElementById('audioList');
            audioList.innerHTML = "";

            $('#recording-displayname').html(
                '<a href="config.php?display=recordings&action=edit&id=' + id + '" target="_blank" class="btn btn-default btn-lg">' +
                '<i class="fa fa-bullhorn"></i> Recording: ' + displayname +
                ' <i class="fa fa-external-link" aria-hidden="true"></i></a>'
            );

            if (!data.filename || data.filename.trim() === '') {
                throw new Error(`No files found for language: <strong>${lang}</strong>`);
            }

            const filenames = data.filename.split('&').filter(f => f.trim() !== '');
            if (filenames.length === 0) {
                throw new Error("Filename array is empty after parsing.");
            }

            for (const filename of filenames) {
                console.log("Fetching file:", filename);

                try {
                    const response = await fetch('ajax.php?module=dpviz&command=getfile', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `file=${encodeURIComponent(filename)}`
                    });

                    if (!response.ok) {
                        throw new Error(`Could not fetch ${filename}`);
                    }

                    const blob = await response.blob();
                    const headerFilename = response.headers.get('X-Filename');
                    const audioUrl = URL.createObjectURL(blob);

                    const container = document.createElement('div');
                    container.classList.add('card', 'mb-4', 'custom-card-bg');

                    const cardBody = document.createElement('div');
                    cardBody.classList.add('card-body');

                    const cardTitle = document.createElement('h5');
                    cardTitle.classList.add('card-title', 'text-left');
                    cardTitle.textContent = `Audio: ${headerFilename}.wav`;
                    cardBody.appendChild(cardTitle);

                    const audio = document.createElement('audio');
                    audio.controls = true;
                    audio.src = audioUrl;
                    cardBody.appendChild(audio);

                    container.appendChild(cardBody);
                    audioList.appendChild(container);
                } catch (err) {
                    const container = document.createElement('div');
                    container.classList.add('recording-container', 'error');

                    const label = document.createElement('div');
                    label.classList.add('alert', 'alert-warning');
                    label.innerHTML = `File: <strong>${filename}.wav</strong> could not be found. To generate the file, simply go to the recording, select the "convert to" wav option, and click submit.`;

                    container.appendChild(label);
                    audioList.appendChild(container);
                }
            }
        })
        .catch(err => {
            console.error("Fetch error:", err);

            const audioList = document.getElementById('audioList');

            const container = document.createElement('div');
            container.classList.add('recording-container', 'error');

            const label = document.createElement('div');
            label.classList.add('alert', 'alert-danger'); // use alert-danger for more critical errors
            label.innerHTML = `<strong>Error:</strong> ${err.message}`;

            container.appendChild(label);
            audioList.appendChild(container);
        });
    }


    document.addEventListener('play', function(e) {
        const audios = document.querySelectorAll('audio');
        audios.forEach(audio => {
            if (audio !== e.target) {
                audio.pause();
            }
        });
    }, true);

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('recordingmodal');
            if (modal && modal.style.display !== 'none') {
                closeModal();
            }
        }
    });


    function closeModal() {
        const modal = document.getElementById('recordingmodal');
        const overlay = document.getElementById('overlay');
        modal.style.display = 'none';
        overlay.style.display = 'none';

        // Stop and reset all audio elements in the document
        const allAudio = document.querySelectorAll('audio');
        allAudio.forEach(audio => {
            audio.pause();
            audio.currentTime = 0;
        });
    }


});
