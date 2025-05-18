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
<script src="modules/dpviz/assets/js/focus.js"></script>
<script src="modules/dpviz/assets/js/select2.min.js"></script>
<link href="modules/dpviz/assets/css/select2.min.css" rel="stylesheet" />
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
							<i class="fa fa-sitemap"></i> <?php echo _("Dial Plan") ?>
						</a>
					</li>
					<li role="presentation" data-name="navigation" class="change-tab">
						<a href="#navigation" aria-controls="navigation" role="tab" data-toggle="tab">
							<i class="fa fa-compass"></i> <?php echo _("Navigation & Usage") ?>
						</a>
					</li>
					<li role="presentation" data-name="settings" class="change-tab">
						<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
							<i class="fa fa-cog"></i> <?php echo _("Settings") ?>
						</a>
					</li>
				</ul>
				<div class="tab-content display">
					<div role="tabpanel" id="dpbox" class="tab-pane active">
						<div id="vizButtons" style="position: sticky; top:50px;">
							<?php require('views/toolbar.php');?>
						</div>
						<div id="vizSpinner" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; flex-direction: column; align-items: center; gap:10px;">
							<div class="loader"></div>
							<h3 class="spinner-text">Loading...</h3>
						</div>
						<div id="vizWrapper">
							
							<div id="overlay" onclick="closeModal()"></div>
							<div id="recordingmodal">
								<div id="recording-displayname"></div>
								<div id="audioList"></div>
								<button class="btn btn-default" style="float:right;" onclick="closeModal()">Close</button>
							</div>
							
							
							<div id="vizContainer" class="display full-border" style="min-height: 65vh;">
								<div id="vizHeader"><p><strong>Dial Plan Not Selected</strong><br>Use the dropdown to select a dial plan.</p></div>
								<div id="vizGraph"></div>
							</div>
						</div>
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

