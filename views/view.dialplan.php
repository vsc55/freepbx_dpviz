<?php if (!defined('FREEPBX_IS_AUTH')) { exit(_('No direct script access allowed')); } ?>

<div class="display no-border">
    <div class="row">
        <div class="col-sm-12">
            <div class="fpbx-container">

                <div class="btn-toolbar bg-light border rounded p-3 shadow-sm mb-4" role="toolbar">
                    <div class="btn-toolbar" role="toolbar">
                        <div class="btn-group mr-2" role="group">
                            <button type="button" class="btn btn-primary" onclick="location.reload();"><?= _("Reload Page") ?></button>
                            <button type="button" class="btn btn-primary" id="toolbar_btn_download" data-scale="<?= $settings['scale'] ?>" data-filename="<?= $filename ?>"><?= sprintf(_("Export as %s"), $basefilename) ?></button>
                            <button type="button" class="btn btn-default" id="toolbar_btn_focus"><?= _("Highlight Paths") ?></button>
                        </div>
                    </div>
                </div>

                <div id="vizContainer" class="display full-border">
                    <div class="row mb-4 align-items-center">
                        <div class="col-3"></div>
                        <div class="col-6 text-center">
                            <h2 class="fw-bold mb-0" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.2);">
                                <?= sprintf(_("Dial Plan For Inbound Route %s%s:%s"), $number, ((!empty($cid)) ? ' / '.$dpviz->dpp->formatPhoneNumbers($cid) : ''),  $dpviz->dpp->dproutes['description']) ?>
                            </h2>
                        </div>
                        <div class="col-3 text-right">
                            <?php if ($settings['datetime'] == 1): ?>
                                <h6 class="text-muted mb-0"><?= date('Y-m-d H:i:s') ?></h6>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<script src="modules/dpviz/assets/js/viz.min.js"></script>
<script src="modules/dpviz/assets/js/full.render.js"></script>
<!-- <script src="modules/dpviz/assets/js/html2canvas.min.js"></script> -->
<script type="text/javascript">
    var viz = new Viz();
    let isFocused = false;
    let svgContainer = null;
    let selectedNodeId = null;
    let originalLinks = new Map();
    let highlightedEdges = new Set(); // Track highlighted edges

    viz.renderSVGElement('<?= $gtext; ?>')
    .then(function(element) {
        svgContainer = element;
        document.getElementById("vizContainer").appendChild(element);

        // Add click event for nodes - only activates in focus mode
        element.querySelectorAll("g.node").forEach(node => {
            node.addEventListener("click", function(e) {
                // Only handle node clicks for path highlighting if in focus mode
                if (isFocused) {
                    selectedNodeId = this.id;
                    highlightPathToNode(this.id);

                    // Prevent default navigation when in focus mode
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Otherwise let default behavior happen (follow links)
            });
        });

        // Add click event for edges - only activates in focus mode
        element.querySelectorAll("g.edge").forEach(edge => {
            edge.addEventListener("click", function(e) {
                // Only handle edge clicks for path highlighting if in focus mode
                if (isFocused) {
                    // Toggle highlight for this edge
                    toggleEdgeHighlight(this.id);

                    // Prevent default behavior
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    });



    // Use the most reliable way to prevent default for focus button
    $(document).on('click', '#toolbar_btn_focus', function (e) {
        e.preventDefault();
        e.stopPropagation();

        toggleFocusMode();
    });

    function toggleEdgeHighlight(edgeId) {
        if (!svgContainer) return;

        const $edge = $('#' + edgeId);
        if (!$edge.length) return;

        // Check if this edge is already highlighted
        if (highlightedEdges.has(edgeId))
        {
            // Remove highlight
            highlightedEdges.delete(edgeId);

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
            highlightedEdges.add(edgeId);

            // Highlight edge
            $edge.find('path').css({ stroke: 'red', strokeWidth: '3px' });
            // Highlight arrowhead
            $edge.find('polygon').css({ fill: 'red', stroke: 'red' });
            // Highlight edge text
            $edge.find('text').css({ fill: 'red', fontWeight: 'bold' });
        }
    }

    function resetEdges() {
        if (!svgContainer) return;

        // Clear highlighted edges set
        highlightedEdges.clear();

        // Reset only edge paths
        $(svgContainer).find('g.edge path').css({ stroke: '', strokeWidth: '' });
        // Reset only arrowheads in edges
        $(svgContainer).find('g.edge polygon').css({ fill: '', stroke: '' });
        // Reset edge text (labels)
        $(svgContainer).find('g.edge text').css({ fill: '', fontWeight: '' });
    }

    function toggleFocusMode() {
        if (!svgContainer) return;

        const $btn = $('#toolbar_btn_focus');

        if (isFocused) {
            resetEdges();
            restoreLinks();
            isFocused = false;

            $btn.text(_('Highlight Paths'))
                .removeClass('active btn-primary')
                .addClass('btn-default');
        }
        else
        {
            disableLinks();
            isFocused = true;

            $btn.text(_('Remove Highlights'))
                .addClass('active btn-primary')
                .removeClass('btn-default');
        }
    }

    function disableLinks() {
        if (!svgContainer) return;

        // Block all node clicks to their URL destinations
        svgContainer.querySelectorAll("g.node a").forEach(link => {
            if (link.hasAttribute("xlink:href")) {
                originalLinks.set(link, link.getAttribute("xlink:href"));
                link.setAttribute("xlink:href", "javascript:void(0);");
            }
        });
    }

    function restoreLinks() {
        if (!svgContainer) return;

        // Restore original hrefs
        svgContainer.querySelectorAll("g.node a").forEach(link => {
            const originalHref = originalLinks.get(link);
            if (originalHref) {
                link.setAttribute("xlink:href", originalHref);
            }
        });

        // Clear stored links
        originalLinks.clear();
    }

    function highlightPathToNode(nodeId) {
        if (!svgContainer) return;

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
            svgContainer.querySelectorAll("g.edge").forEach(edge => {
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

</script>
<?php if ($settings['panzoom'] == 1) : ?>
    <script src="modules/dpviz/assets/js/panzoom.min.js"></script>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var element = document.querySelector('#graph0');
            if (element) {
                panzoom(element);
            }
        });
    </script>
<?php endif; ?>
