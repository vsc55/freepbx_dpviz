<?php

if (!defined('FREEPBX_IS_AUTH')) {
    exit(_("No direct script access allowed"));
} ?>

<div class="display no-border">
    <div class="row">
        <div class="col-sm-12">
            <div class="fpbx-container">

                <div id="vizSpinner" class="spinner-overlay align-items-center justify-content-center text-center">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div class="loader-text">
                        <h4><?= _("Loading...") ?></h4>
                        <p><?= _("Please wait while the Dial Plan is being loaded.") ?></p>
                    </div>
                </div>

                <div class="btn-toolbar bg-light border rounded p-3 shadow-sm mb-4" role="toolbar" id="btn-toolbar">
                    <div class="btn-toolbar w-100" role="toolbar">
                        <div class="btn-group mr-2" role="group">
                            <button type="button" class="btn btn-primary" id="reload-dpp" disabled>
                                <i class="fa fa-refresh"></i> <?= _("Reload") ?>
                            </button>
                        </div>
                        <div class="btn-group mr-2" role="group">
                            <button type="button" class="btn btn-default" id="toolbar_btn_focus" disabled>
                                <i class="fa fa-magic"></i> <?= _("Highlight Paths") ?>
                            </button>
                        </div>

                        <div class="input-group mr-2 flex-grow-1" role="group">
                            <div class="input-group-prepend">
                                <span class="input-group-text px-4"><?= _("Inbound Routes") ?></span>
                            </div>
                            <select class="selectpicker form-control" id="list_inbound_routes" data-live-search="true" data-header="<?= _("Inbound Routes") ?>" data-none-selected-text="<?= _("Select an Inbound Route") ?>" data-size="5" data-show-subtext="true">
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-secondary" type="button" id="list_inbound_routes_reload">
                                    <i class="fa fa-refresh"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-group mr-2" role="group">
                            <div class="input-group-prepend">
                                <span class="input-group-text px-4">
                                    <i class="fa fa-file mr-2"></i> <?= _("Export as") ?>
                                </span>
                            </div>
                            <input type="text" class="form-control" id="filename_input" value="" placeholder="<?= _("Enter filename") ?>"  disabled>
                            <div class="input-group-append">
                                <span class="input-group-text px-3">
                                    <i class="fa fa-file-image-o mr-1"></i> .png
                                </span>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" disabled>
                                    <i class="fa fa-download"></i> <?= _("Download") ?>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item export-option-scale" href="#" data-scale="4">
                                        <i class="fa fa-star"></i> <?= _("High") ?>
                                    </a>
                                    <a class="dropdown-item export-option-scale" href="#" data-scale="2">
                                        <i class="fa fa-circle"></i> <?= _("Standard") ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="vizContainer" class="display full-border" style="min-height: 65vh;">
                    <div class="row mb-4 align-items-center" id="vizContainerHeader">
                        <div class="col-3"></div>
                        <div class="col-6 text-center">
                            <h2 class="fw-bold mb-0" id="vizContainerTitle" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.2);"></h2>
                        </div>
                        <div class="col-3 text-right">
                            <h6 class="text-muted mb-0" id="vizContainerDatetime"></h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 d-flex justify-content-center align-items-center" id="vizContainerBody">

                            <div class="card" style="max-width: 50%; margin: 0 auto;">
                                <div class="card-header"></div>
                                <div class="row no-gutters">
                                    <div class="col-md-4">
                                        <img src="modules/dpviz/assets/img/select_null.png">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= _("No Dial Plan has been selected for display!") ?></h5>
                                            <p class="card-text"><?= _("To view the call flow in the system, you must first select a dial plan from the \"Inbound Routes\" dropdown menu located in the top toolbar of the interface. This menu contains all previously configured dial plans, and selecting one is essential to access detailed information about the behavior of incoming calls. Once the desired plan is selected, the system will automatically load the corresponding call flow. This visualization allows you to accurately review each step of the routing process, identify configured destinations, detect potential errors or inconsistencies, and understand how calls are handled from entry to final destination. Additionally, having access to this information enables you to make adjustments or modifications as needed, thereby optimizing the efficiency and logic of the routing. It is important to note that selecting a dial plan is a mandatory prerequisite for inspecting, analyzing, or editing any aspect of the call flow within the system.") ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer"></div>
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
