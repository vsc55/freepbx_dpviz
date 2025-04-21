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