<?php /* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//  Copyright (C) 2011 Mikael Carlsson (mickecarlsson at gmail dot com)
//
?>
<script src="modules/dpviz/assets/js/viz.min.js"></script>
<script src="modules/dpviz/assets/js/full.render.js"></script>
<script src="modules/dpviz/assets/js/html2canvas.min.js"></script>
<script src="modules/dpviz/assets/js/panzoom.min.js"></script>
<script type="text/javascript">
//load graphviz
var viz = new Viz();
let isFocused = false;
let svgContainer = null;
let selectedNodeId = null;
let originalLinks = new Map();
let highlightedEdges = new Set(); // Track highlighted edges


</script>
<meta charset="UTF-8">
<div class="container-fluid">
	<div class="display full-border">
		<h1><?php echo _("Dial Plan Vizualizer"); ?></h1>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
			
				<ul class="nav nav-tabs" role="tablist">
					<li role="presentation" data-name="dpbox" class="active">
						<a href="#dpbox" aria-controls="dpbox" role="tab" data-toggle="tab">
							<?php echo _("Dial Plan") ?>
						</a>
					</li>
					<li role="presentation" data-name="navigation" class="change-tab">
						<a href="#navigation" aria-controls="navigation" role="tab" data-toggle="tab">
							<?php echo _("Navigation & Usage") ?>
						</a>
					</li>
					<li role="presentation" data-name="settings" class="change-tab">
						<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
							<?php echo _("Settings") ?>
						</a>
					</li>
				</ul>
				<div class="tab-content display">
					<div role="tabpanel" id="dpbox" class="tab-pane active">
						<div id="vizButtons"></div>
						<div id="vizContainer" class="display full-border" style="min-height: 65vh;"><p><strong>Inbound Route Not Selected</strong><br>Use the menu on the right to choose an inbound route.</p></div>
					</div>
					<div role="tabpanel" id="navigation" class="tab-pane">
						<p>
							<ul class="list-unstyled">
								<li><strong>Redraw from a Node:</strong> Press <strong>Ctrl</strong> (<strong>Cmd</strong> on macOS) and left-click a node to make it the new starting point in the diagram. To revert, <strong>Ctrl/Cmd + left-click</strong> the parent node.</li>
								<li><strong>Highlight Paths:</strong> Click <strong>Highlight Paths</strong>, then select a node or edge (links are inactive). Click <strong>Remove Highlights</strong> to clear.</li>
								<li><strong>Hover:</strong> Hover over a path to highlight between destinations.</li>
								<li><strong>Open Destinations:</strong> Click a destination to open it in a new tab.</li>
								<li><strong>Open Time Groups:</strong> Click on a "<strong>Match: (timegroup)</strong>" or "<strong>No Match</strong>" to open in a new tab.</li>
								<li><strong>Pan:</strong> Hold the left mouse button and drag to move the view.</li>
								<li><strong>Zoom:</strong> Use the mouse wheel to zoom in and out.</li>
							</ul>
						</p>
					</div>
					<div role="tabpanel" id="settings" class="tab-pane">
						<?php require('views/options.php');?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>


</script>

