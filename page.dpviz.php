<?php

if (!defined('FREEPBX_IS_AUTH')) {
    die(_('No direct script access allowed'));
}

echo \FreePBX::Dpviz()->showPage("main");
