<?php if (!defined('FREEPBX_IS_AUTH')) { exit(_('No direct script access allowed')); } ?>
<?php
$options = \FreePBX::Dpviz()->getOptions();
?>
<div class="display no-border">
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">		
				<!--check for updates-->
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="col-md-3">
									<button id="check-update-btn" class="btn btn-default">Check for Updates</button>&nbsp;&nbsp;&nbsp;<a href="https://github.com/madgen78/dpviz/" title="Github" target="_blank"><i class="fa fa-github"></i></a>&nbsp;&nbsp;&nbsp;<a href="https://buymeacoffee.com/adamvolchko" style="text-decoration:none;" title="Buy Me a Coffee" target="_blank">☕</a>
								</div>
								<div class="col-md-9">
									<div id="update-result"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<form id="dpvizForm" action="ajax.php?module=dpviz&command=save_options" method="post">
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
										<input type="radio" name="datetime" id="datetimeyes" value="1" <?php echo ($options['datetime']?"CHECKED":"") ?>>
										<label for="datetimeyes"><?php echo _("Yes");?></label>
										<input type="radio" name="datetime" id="datetimeno" value="0" <?php echo ($options['datetime']?"":"CHECKED") ?>>
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
										<input type="radio" name="panzoom" id="panzoomyes" value="1" <?php echo ($options['panzoom']?"CHECKED":"") ?>>
										<label for="panzoomyes"><?php echo _("Yes");?></label>
										<input type="radio" name="panzoom" id="panzoomno" value="0" <?php echo ($options['panzoom']?"":"CHECKED") ?>>
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
										<input type="radio" name="horizontal" id="horizontalyes" value="1" <?php echo ($options['horizontal']?"CHECKED":"") ?>>
										<label for="horizontalyes"><?php echo _("Yes");?></label>
										<input type="radio" name="horizontal" id="horizontalno" value="0" <?php echo ($options['horizontal']?"":"CHECKED") ?>>
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
										<label class="control-label" for="combineQueueRing"><?php echo _("Shared extension node handling") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="combineQueueRing"></i>
									</div>
									<div class="col-md-9 radioset">
											<input type="radio" name="combineQueueRing" id="combineQueueRingNone" value="0" <?php echo ($options['combineQueueRing'] == 0 ? "CHECKED" : ""); ?>>
											<label for="combineQueueRingNone"><?php echo _("None"); ?></label>

											<input type="radio" name="combineQueueRing" id="combineQueueRingQueueRing" value="1" <?php echo ($options['combineQueueRing'] == 1 ? "CHECKED" : ""); ?>>
											<label for="combineQueueRingQueueRing"><?php echo _("Queues and Ring Groups Only"); ?></label>

											<input type="radio" name="combineQueueRing" id="combineQueueRingAll" value="2" <?php echo ($options['combineQueueRing'] == 2 ? "CHECKED" : ""); ?>>
											<label for="combineQueueRingAll"><?php echo _("All Destinations"); ?></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="combineQueueRing-help" class="help-block fpbx-help-block"><?php echo _("\"None\" displays individual extension nodes. \"Queues and Ring Groups Only\" combines them into one node. \"All\" merges all destinations into a single extension node.")?></span>
						</div>
					</div>
				</div>
				<!--END combineQueueRing-->
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
										<input type="radio" name="dynmembers" id="dynmembersyes" value="1" <?php echo ($options['dynmembers']?"CHECKED":"") ?>>
										<label for="dynmembersyes"><?php echo _("Yes");?></label>
										<input type="radio" name="dynmembers" id="dynmembersno" value="0" <?php echo ($options['dynmembers']?"":"CHECKED") ?>>
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
				<!--fmfm-->
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="fmfm"><?php echo _("Show Find Me Follow Me for Extensions") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="fmfm"></i>
									</div>
									<div class="col-md-9 radioset">
										<input type="radio" name="fmfm" id="fmfmyes" value="1" <?php echo ($options['fmfm']?"CHECKED":"") ?>>
										<label for="fmfmyes"><?php echo _("Yes");?></label>
										<input type="radio" name="fmfm" id="fmfmno" value="0" <?php echo ($options['fmfm']?"":"CHECKED") ?>>
										<label for="fmfmno"><?php echo _("No");?></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="fmfm-help" class="help-block fpbx-help-block"><?php echo _("Displays Find Me Follow Me data for extensions.")?></span>
						</div>
					</div>
				</div>
				<!--END fmfm-->
				<!--extOptional-->
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="extOptional"><?php echo _("Show Extension Optional Destinations") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="extOptional"></i>
									</div>
									<div class="col-md-9 radioset">
										<input type="radio" name="extOptional" id="extOptionalyes" value="1" <?php echo ($options['extOptional']?"CHECKED":"") ?>>
										<label for="extOptionalyes"><?php echo _("Yes");?></label>
										<input type="radio" name="extOptional" id="extOptionalno" value="0" <?php echo ($options['extOptional']?"":"CHECKED") ?>>
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
				<!--Minimal-->
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="minimal"><?php echo _("Show Minimal View") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="minimal"></i>
									</div>
									<div class="col-md-9 radioset">
										<input type="radio" name="minimal" id="minimalyes" value="1" <?php echo ($options['minimal']?"CHECKED":"") ?>>
										<label for="minimalyes"><?php echo _("Yes");?></label>
										<input type="radio" name="minimal" id="minimalno" value="0" <?php echo ($options['minimal']?"":"CHECKED") ?>>
										<label for="minimalno"><?php echo _("No");?></label>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="minimal-help" class="help-block fpbx-help-block"><?php echo _("Shows (default) or hides the following types of nodes: Extensions, Queue Members, Ring Group Members, Recordings, Voicemail, and Voicemail Blasting Members.")?></span>
						</div>
					</div>
				</div>
				<!--END minimal-->

				<div class="row">
					<div class="col-md-12 text-right">
						<button class="btn btn-primary" name="submit" id="saveButton" type="submit">
							<i class="fa fa-save"></i> Save
						</button>
						<div id="saveResponse"></div>
					</div>
				</div>
				</form>
			</div>
		</div>
	</div>
</div>