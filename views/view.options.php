<?php if (!defined('FREEPBX_IS_AUTH')) { exit(_('No direct script access allowed')); } ?>

<div class="panel panel-default fpbx-usageinfo">
	<div class="panel-heading">
		<a data-toggle="collapse" data-target="#collapseOne" onclick="toggleNavBar()"><?= _("Options") ?> <small><?= _("(Click to Expand)") ?></small></a>
	</div>
	<div id="collapseOne" class="panel-collapse collapse">
		<div class="panel-body">
			<div class="fpbx-container">
				<div class="display full-border">
					<div class="panel panel-info panel-help">
						<div class="panel-heading collapsed" data-toggle="collapse" href="#panelId67f91e1a7403f" role="button" aria-expanded="false" aria-controls="panelId67f91e1a7403f">
							<h3 class="panel-title">
								<span class="pull-left"><i class="fa fa-info-circle fa-lg fa-fw"></i></span><?= _("Navigation and usage") ?><span class="pull-right"><i class="chevron fa fa-fw"></i></span>
							</h3>
						</div>
						<div id="panelId67f91e1a7403f" class="panel-collapse collapse" style="">
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
					<!--check for updates-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="col-md-3">
										<button id="check-update-btn" class="btn btn-default"><?= _("Check for Updates") ?></button>&nbsp;<a href="https://github.com/madgen78/dpviz/" target="_blank"><i class="fa fa-github"></i></a>
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
											<label class="control-label" for="datetime"><?= _("Date & Time Stamp") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="datetime"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="datetime" id="datetimeyes" value="1" <?= ($datetime?"CHECKED":"") ?>>
											<label for="datetimeyes"><?= _("Yes");?></label>
											<input type="radio" name="datetime" id="datetimeno" value="0" <?= ($datetime?"":"CHECKED") ?>>
											<label for="datetimeno"><?= _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="datetime-help" class="help-block fpbx-help-block"><?= _("Displays the date and time on the graph.")?></span>
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
											<label class="control-label" for="scale"><?= _("Export as High-Resolution PNG") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="scale"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="scale" id="scaleyes" value="3" <?= ($scale == 3 ? "CHECKED" : ""); ?>>
											<label for="scaleyes"><?= _("Yes");?></label>
											<input type="radio" name="scale" id="scaleno" value="1" <?= ($scale == 1 ? "CHECKED" : ""); ?>>
											<label for="scaleno"><?= _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="scale-help" class="help-block fpbx-help-block"><?= _("Increases PNG resolution during export.")?></span>
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
											<label class="control-label" for="horizontal"><?= _("Horizontal Layout") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="horizontal"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="horizontal" id="horizontalyes" value="1" <?= ($horizontal?"CHECKED":"") ?>>
											<label for="horizontalyes"><?= _("Yes");?></label>
											<input type="radio" name="horizontal" id="horizontalno" value="0" <?= ($horizontal?"":"CHECKED") ?>>
											<label for="horizontalno"><?= _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="horizontal-help" class="help-block fpbx-help-block"><?= _("Displays the dial plan in a horizontal layout.")?></span>
							</div>
						</div>
					</div>
					<!--END horizontal-->
					<!--panzoom-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="panzoom"><?= _("Pan & Zoom") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="panzoom"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="panzoom" id="panzoomyes" value="1" <?= ($panzoom?"CHECKED":"") ?>>
											<label for="panzoomyes"><?= _("Yes");?></label>
											<input type="radio" name="panzoom" id="panzoomno" value="0" <?= ($panzoom?"":"CHECKED") ?>>
											<label for="panzoomno"><?= _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="panzoom-help" class="help-block fpbx-help-block"><?= _("Allows you to use pan and zoom functions. Click and hold to pan, and use the mouse wheel to zoom.")?></span>
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
											<label class="control-label" for="destination"><?= _("Show Destination Column") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="destination"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="destination" id="destinationyes" value="1" <?= ($destinationColumn?"CHECKED":"") ?>>
											<label for="destinationyes"><?= _("Yes");?></label>
											<input type="radio" name="destination" id="destinationno" value="0" <?= ($destinationColumn?"":"CHECKED") ?>>
											<label for="destinationno"><?= _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="destination-help" class="help-block fpbx-help-block"><?= _("Displays the destination column for each inbound route. May impact performance with a large number of inbound routes.")?></span>
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
											<label class="control-label" for="dynmembers"><?= _("Show Dynamic Members for Queues") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="dynmembers"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="dynmembers" id="dynmembersyes" value="1" <?= ($dynmembers?"CHECKED":"") ?>>
											<label for="dynmembersyes"><?= _("Yes");?></label>
											<input type="radio" name="dynmembers" id="dynmembersno" value="0" <?= ($dynmembers?"":"CHECKED") ?>>
											<label for="dynmembersno"><?= _("No");?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="dynmembers-help" class="help-block fpbx-help-block"><?= _("Displays the list of dynamic agents currently assigned to the queues.")?></span>
							</div>
						</div>
					</div>
					<!--END destination-->

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
