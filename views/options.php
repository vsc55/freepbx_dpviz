<div class="panel panel-default fpbx-usageinfo">
	<div class="panel-heading">
		<a data-toggle="collapse" data-target="#collapseOne" onclick="toggleNavBar()">Options <small>(Click to Expand)</small></a>
	</div>
	<div id="collapseOne" class="panel-collapse collapse">
		<div class="panel-body">
			<div class="fpbx-container">
				<div class="display full-border">
					<div class="panel panel-info panel-help">
						<div class="panel-heading collapsed" data-toggle="collapse" href="#panelId67f91e1a7403f" role="button" aria-expanded="false" aria-controls="panelId67f91e1a7403f">
							<h3 class="panel-title"><span class="pull-left"><i class="fa fa-info-circle fa-lg fa-fw"></i></span>Navigation and usage<span class="pull-right"><i class="chevron fa fa-fw"></i></span></h3>
						</div>
						<div id="panelId67f91e1a7403f" class="panel-collapse collapse" style="">
							<div class="panel-body">
								<ul class="list-unstyled">
									<li><strong>Redraw from a Node:</strong> Press <strong>Ctrl</strong> (<strong>Cmd</strong> on macOS) and left-click a node to make it the new starting point in the diagram. To revert, <strong>Ctrl/Cmd + left-click</strong> the parent node.</li>
									<li><strong>Highlight Paths:</strong> Click <strong>Highlight Paths</strong>, then select a node or edge (links are inactive). Click <strong>Remove Highlights</strong> to clear.</li>
									<li><strong>Hover:</strong> Hover over a path to highlight between destinations.</li>
									<li><strong>Open Destinations:</strong> Click a destination to open it in a new tab.</li>
									<li><strong>Open Time Groups:</strong> Click on a "<strong>Match: (timegroup)</strong>" or "<strong>NoMatch</strong>" to open in a new tab.</li>
									<li><strong>Pan:</strong> Hold the left mouse button and drag to move the view.</li>
									<li><strong>Zoom:</strong> Use the mouse wheel to zoom in and out.</li>
								</ul>
							</div>
						</div>
					</div>
					<!--check for updates-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="col-md-3">
										<button id="check-update-btn" class="btn btn-default">Check for Updates</button>&nbsp;<a href="https://github.com/madgen78/dpviz/" target="_blank"><i class="fa fa-github"></i></a>
									</div>
									<div class="col-md-9">
										<div id="update-result"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<form class="fpbx-submit" name="editDpviz" action="?display=dpviz&action=edit" method="post">
					<!--datetime-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="datetime"><?php echo _("Date & Time Stamp") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="datetime"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="datetime" id="datetimeyes" value="1" <?php echo ($datetime?"CHECKED":"") ?>>
											<label for="datetimeyes"><?php echo _("Yes");?></label>
											<input type="radio" name="datetime" id="datetimeno" value="0" <?php echo ($datetime?"":"CHECKED") ?>>
											<label for="datetimeno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="datetime-help" class="help-block fpbx-help-block"><?php echo _("Displays the date and time on the graph.")?></span>
							</div>
						</div>
					</div>
					<!--END datetime-->	
					<!--Higher Resolution-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="scale"><?php echo _("Export as High-Resolution PNG") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="scale"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="scale" id="scaleyes" value="3" <?php echo ($scale == 3 ? "CHECKED" : ""); ?>>
											<label for="scaleyes"><?php echo _("Yes");?></label>
											<input type="radio" name="scale" id="scaleno" value="1" <?php echo ($scale == 1 ? "CHECKED" : ""); ?>>
											<label for="scaleno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="scale-help" class="help-block fpbx-help-block"><?php echo _("Increases PNG resolution during export.")?></span>
							</div>
						</div>
					</div>
					<!--END Higher Resolution-->
					<!--horizontal-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="horizontal"><?php echo _("Horizontal Layout") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="horizontal"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="horizontal" id="horizontalyes" value="1" <?php echo ($horizontal?"CHECKED":"") ?>>
											<label for="horizontalyes"><?php echo _("Yes");?></label>
											<input type="radio" name="horizontal" id="horizontalno" value="0" <?php echo ($horizontal?"":"CHECKED") ?>>
											<label for="horizontalno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="horizontal-help" class="help-block fpbx-help-block"><?php echo _("Displays the dial plan in a horizontal layout.")?></span>
							</div>
						</div>
					</div>
					<!--END horizontal-->
					<!--combineQueueRing node-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="combineQueueRing"><?php echo _("Combine Queue Agents and RG Members into one node") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="combineQueueRing"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="combineQueueRing" id="combineQueueRingyes" value="1" <?php echo ($combineQueueRing?"CHECKED":"") ?>>
											<label for="combineQueueRingyes"><?php echo _("Yes");?></label>
											<input type="radio" name="combineQueueRing" id="combineQueueRingno" value="0" <?php echo ($combineQueueRing?"":"CHECKED") ?>>
											<label for="combineQueueRingno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="combineQueueRing-help" class="help-block fpbx-help-block"><?php echo _("When an extension is part of both a queue and a ring group, it will be shown as a single node instead of two.")?></span>
							</div>
						</div>
					</div>
					<!--END combineQueueRing-->
					<!--panzoom-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="panzoom"><?php echo _("Pan & Zoom") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="panzoom"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="panzoom" id="panzoomyes" value="1" <?php echo ($panzoom?"CHECKED":"") ?>>
											<label for="panzoomyes"><?php echo _("Yes");?></label>
											<input type="radio" name="panzoom" id="panzoomno" value="0" <?php echo ($panzoom?"":"CHECKED") ?>>
											<label for="panzoomno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="panzoom-help" class="help-block fpbx-help-block"><?php echo _("Allows you to use pan and zoom functions. Click and hold to pan, and use the mouse wheel to zoom.")?></span>
							</div>
						</div>
					</div>
					<!--END panzoom-->
					<!--destination-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="destination"><?php echo _("Show Destination Column") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="destination"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="destination" id="destinationyes" value="1" <?php echo ($destinationColumn?"CHECKED":"") ?>>
											<label for="destinationyes"><?php echo _("Yes");?></label>
											<input type="radio" name="destination" id="destinationno" value="0" <?php echo ($destinationColumn?"":"CHECKED") ?>>
											<label for="destinationno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="destination-help" class="help-block fpbx-help-block"><?php echo _("Displays the destination column for each inbound route. May impact performance with a large number of inbound routes.")?></span>
							</div>
						</div>
					</div>
					<!--END destination-->
					<!--dynmembers-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="dynmembers"><?php echo _("Show Dynamic Members for Queues") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="dynmembers"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="dynmembers" id="dynmembersyes" value="1" <?php echo ($dynmembers?"CHECKED":"") ?>>
											<label for="dynmembersyes"><?php echo _("Yes");?></label>
											<input type="radio" name="dynmembers" id="dynmembersno" value="0" <?php echo ($dynmembers?"":"CHECKED") ?>>
											<label for="dynmembersno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="dynmembers-help" class="help-block fpbx-help-block"><?php echo _("Displays the list of dynamic agents currently assigned to the queues.")?></span>
							</div>
						</div>
					</div>
					<!--END dynmembers-->
					<!--extOptional-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="dynmembers"><?php echo _("Show Extension Optional Destinations") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="extOptional"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="extOptional" id="extOptionalyes" value="1" <?php echo ($extOptional?"CHECKED":"") ?>>
											<label for="extOptionalyes"><?php echo _("Yes");?></label>
											<input type="radio" name="extOptional" id="extOptionalno" value="0" <?php echo ($extOptional?"":"CHECKED") ?>>
											<label for="extOptionalno"><?php echo _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="extOptional-help" class="help-block fpbx-help-block"><?php echo _("Displays and follows the optional destinations (No Answer, Busy, Not Reachable) set for the extension in the Advanced tab.")?></span>
							</div>
						</div>
					</div>
					<!--END extOptional-->

					<div class="row">
						<div class="col-md-12 text-right">
							<input class="btn btn-primary" name="submit" type="submit" value="Submit" id="submit">
						</div>
					</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
