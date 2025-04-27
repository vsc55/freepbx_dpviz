$(document).ready(function() {

    // create namespaces
    window.dpviz      = window.dpviz || {};


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

    document.querySelectorAll('g.node').forEach(node => {
        node.addEventListener('click', function(e) {
            if (e.ctrlKey || e.metaKey) {  // Support Ctrl on Windows/Linux, Command on Mac
                e.preventDefault();

                let titleElement = node.querySelector('title');
                if (titleElement) {
                    let titleText = titleElement.textContent || titleElement.innerText;

                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.location.href;

                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'clickedNodeTitle';
                    input.value = titleText;
                    form.appendChild(input);

                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    });



    /**
     * Download dialplan diagram
     */

    $(document).on('click', '#toolbar_btn_download', function (e) {
        e.preventDefault();

        const $vizContainer = $('#vizContainer');
        if (!$vizContainer.length) return;

        const $btn	   = $(this);
        const scale    = parseFloat($btn.data('scale')) || 1;
        const filename = $btn.data('filename') || 'diagram.png';

        html2canvas($vizContainer[0], {
            scale: scale,
            useCORS: true,
            allowTaint: true
        }).then(function(canvas) {
            const imgData = canvas.toDataURL("image/png");
            triggerDownload(imgData, filename);
        });
    });

    function triggerDownload(uri, filename) {
        const link = document.createElement('a');
        if ('download' in link)
        {
            link.href = uri;
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
                    } else {
                        fpbxToast(response.message || _("⚠ Something went wrong"), '', 'error');
                    }
                }, 'json').fail(function () {
                    fpbxToast(_("⚠ Could not connect to the server"), '', 'error');
                });

            }
        );
    });





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
    window.bootnavvizFormatter = function (value, row) {
        const extension = decodeURIComponent(row['extension'] || "").trim() || "ANY";
        const cidnum = decodeURIComponent(row['cidnum'] || "").trim();
        return cidnum ? `${extension} / ${cidnum}` : extension;
    };

    // Click over row → redirect
    $(document).on('click-row.bs.table', '#dpviz-side', function (e, row) {
        const extension = row['extension'];
        const cid = row['cidnum'];
        window.location = `?display=dpviz&extdisplay=${extension}&cid=${cid}`;
    });

    $('#dpviz-side').on('post-body.bs.table', function () {
        dpvizRNavLoadDestinations();
    });
});
