<?php if (!defined('FREEPBX_IS_AUTH')) { exit(_('No direct script access allowed')); } ?>

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
									<button id="check-update-btn" class="btn btn-default"><i class="fa fa-github fa-lg"></i> <?= _("Check for Updates") ?></button>&nbsp;<a href="https://github.com/madgen78/dpviz/" target="_blank"></a>
								</div>
								<div class="col-md-9">
									<div id="update-result"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!--END check for updates-->

				<form class="fpbx-submit" name="editDpviz">
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
											<input type="radio" name="datetime" id="datetimeyes" value="1" <?= ($settings['datetime'] ? "CHECKED":"") ?>>
											<label for="datetimeyes"><?= _("Yes");?></label>
											<input type="radio" name="datetime" id="datetimeno" value="0" <?= ($settings['datetime'] ? "":"CHECKED") ?>>
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
											<input type="radio" name="scale" id="scaleyes" value="3" <?= ($settings['scale'] == 3 ? "CHECKED" : ""); ?>>
											<label for="scaleyes"><?= _("Yes");?></label>
											<input type="radio" name="scale" id="scaleno" value="1" <?= ($settings['scale'] == 1 ? "CHECKED" : ""); ?>>
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
											<input type="radio" name="horizontal" id="horizontalyes" value="1" <?= ($settings['horizontal'] ?"CHECKED":"") ?>>
											<label for="horizontalyes"><?= _("Yes");?></label>
											<input type="radio" name="horizontal" id="horizontalno" value="0" <?= ($settings['horizontal'] ?"":"CHECKED") ?>>
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
					
					<!--combineQueueRing node-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="combine_queue_ring"><?= _("Combine Queue Agents and RG Members into one node") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="combine_queue_ring"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="combine_queue_ring" id="combine_queue_ring" value="1" <?= ($settings['combine_queue_ring'] ? "CHECKED":"") ?>>
											<label for="combine_queue_ringyes"><?=  _("Yes") ?></label>
											<input type="radio" name="combine_queue_ring" id="combine_queue_ring" value="0" <?= ($settings['combine_queue_ring'] ? "":"CHECKED") ?>>
											<label for="combine_queue_ringno"><?= _("No") ?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="combine_queue_ring-help" class="help-block fpbx-help-block"><?= _("When an extension is part of both a queue and a ring group, it will be shown as a single node instead of two.") ?></span>
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
											<label class="control-label" for="panzoom"><?= _("Pan & Zoom") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="panzoom"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="panzoom" id="panzoomyes" value="1" <?= ($settings['panzoom'] ?"CHECKED":"") ?>>
											<label for="panzoomyes"><?= _("Yes");?></label>
											<input type="radio" name="panzoom" id="panzoomno" value="0" <?= ($settings['panzoom'] ?"":"CHECKED") ?>>
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
											<input type="radio" name="dynmembers" id="dynmembersyes" value="1" <?= ($settings['dynmembers'] ?"CHECKED":"") ?>>
											<label for="dynmembersyes"><?= _("Yes");?></label>
											<input type="radio" name="dynmembers" id="dynmembersno" value="0" <?= ($settings['dynmembers'] ?"":"CHECKED") ?>>
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
					<!--END dynmembers-->

					<!--extOptional-->
					<div class="element-container">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<div class="form-group">
										<div class="col-md-3">
											<label class="control-label" for="ext_optional"><?= _("Show Extension Optional Destinations") ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="ext_optional"></i>
										</div>
										<div class="col-md-9 radioset">
											<input type="radio" name="ext_optional" id="ext_optionalyes" value="1" <?= ($settings['ext_optional'] ? "CHECKED":"") ?>>
											<label for="ext_optionalyes"><?= _("Yes") ?></label>
											<input type="radio" name="ext_optional" id="ext_optionalno" value="0" <?= ($settings['ext_optional'] ? "":"CHECKED") ?>>
											<label for="ext_optionalno"><?= _("No") ?></label>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<span id="ext_optional-help" class="help-block fpbx-help-block"><?= _("Displays and follows the optional destinations (No Answer, Busy, Not Reachable) set for the extension in the Advanced tab.")?></span>
							</div>
						</div>
					</div>
					<!--END extOptional-->
					
					<!-- button submit -->
					<div class="row">
						<div class="col-md-12 text-right">
							<div class="btn-group" role="group" aria-label="Actions">
								<button type="button" class="btn btn-primary" name="submit" id="settings_submit">
									<i class="fa fa-save me-2"></i>
									<?= _('Save Changes') ?>
								</button>
								<button type="button" class="btn btn-danger" name="reset"  id="settings_reset">
									<i class="fa fa-undo me-2"></i>
									<?= _('Reset Default') ?>
								</button>
							</div>
						</div>
					</div>
					<!-- END button submit -->

				</form>
			</div>
		</div>
	</div>
</div>