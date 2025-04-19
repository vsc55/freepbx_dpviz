<?php if (!defined('FREEPBX_IS_AUTH')) { exit(_('No direct script access allowed')); } ?>

<div class="container-fluid">
	<div class="display full-border">
		<h1><?= _("Dial Plan Vizualizer") ?></h1>
	</div>
    <?= $dpviz->showPage('options') ?>
    <?= $dpviz->showPage('dialplan') ?>
</div>