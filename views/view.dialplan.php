<?php
	if (!defined('FREEPBX_IS_AUTH')) { exit(_('No direct script access allowed')); }
?>
<div class="display no-border">
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">

				<div class="panel panel-info panel-help" style="position: relative;">
					<button type="button" class="close" aria-label="Close" style="position: absolute; top: 2px; right: 5px; z-index: 10;" onclick="this.closest('.panel').remove();">&times;</button>
					<div class="panel-heading collapsed" data-toggle="collapse" href="#panelId67f91e1a7403f" role="button" aria-expanded="false" aria-controls="panelId67f91e1a7403f">
						<h3 class="panel-title">
							<span class="pull-left"><i class="fa fa-info-circle fa-lg fa-fw"></i></span><?= _("Navigation and usage") ?><span class="pull-right"><i class="chevron fa fa-fw"></i></span>
						</h3>
					</div>
					<div id="panelId67f91e1a7403f" class="panel-collapse collapse">
						<div class="panel-body">
							<ul class="list-unstyled">
								<li><strong><?= _("Redraw from a Node:") ?></strong> <?= _("Press <strong>Ctrl</strong> (<strong>Cmd</strong> on macOS) and left-click a node to make it the new starting point in the diagram. To revert, <strong>Ctrl/Cmd + left-click</strong> the parent node.") ?></li>
								<li><strong><?= _("Highlight Paths:") ?></strong> <?= _("Click <strong>Highlight Paths</strong>, then select a node or edge (links are inactive). Click <strong>Remove Highlights</strong> to clear.") ?></li>
								<li><strong><?= _("Hover:") ?></strong> <?= _("Hover over a path to highlight between destinations.") ?></li>
								<li><strong><?= _("Open Destinations:") ?></strong> <?= _("Click a destination to open it in a new tab.") ?></li>
								<li><strong><?= _("Open Time Groups:") ?></strong> <?= _("Click on a \"<strong>Match: (timegroup)</strong>\" or \"<strong>NoMatch</strong>\" to open in a new tab.") ?></li>
								<li><strong><?= _("Pan:") ?></strong> <?= _("Hold the left mouse button and drag to move the view.") ?></li>
								<li><strong><?= _("Zoom:") ?></strong> <?= _("Use the mouse wheel to zoom in and out.") ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div class="btn-toolbar bg-light border rounded p-3 shadow-sm mb-4" role="toolbar">
					<div class="btn-toolbar" role="toolbar">
						<div class="btn-group mr-2" role="group">
							<button type="button" class="btn btn-primary" onclick="location.reload();"><?= _("Reload Page") ?></button>
							<button type="button" class="btn btn-primary" id="toolbar_btn_download" data-scale="<?= $scale ?>" data-filename="<?= $filename ?>"><?= sprintf(_("Export as %s"), $filename) ?></button>
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
							<?php if ($datetime == 1): ?>
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

		if (highlightedEdges.has(edgeId)) {
			highlightedEdges.delete(edgeId);

			$edge.find('path').css({ stroke: '', strokeWidth: '' });
			$edge.find('polygon').css({ fill: '', stroke: '' });
			$edge.find('text').css({ fill: '', fontWeight: '' });
		} else {
			highlightedEdges.add(edgeId);

			$edge.find('path').css({ stroke: 'red', strokeWidth: '3px' });
			$edge.find('polygon').css({ fill: 'red', stroke: 'red' });
			$edge.find('text').css({ fill: 'red', fontWeight: 'bold' });
		}
	}

	function resetEdges() {
		if (!svgContainer) return;

		highlightedEdges.clear();

		$(svgContainer).find('g.edge path').css({ stroke: '', strokeWidth: '' });
		$(svgContainer).find('g.edge polygon').css({ fill: '', stroke: '' });
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
<?php if ($panzoom==1) : ?>
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