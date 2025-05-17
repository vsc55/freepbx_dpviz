<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationVmblastMembers extends BaseDestinations
{
    public const PRIORITY = 13000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^vmblast\-mem,(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of vmblast-mem,<number>

        $member       = $matches[1];
        $vmblastname  = $route['extensions'][$member]['name'];
        $vmblastemail = $route['extensions'][$member]['email'];
        $vmblastemail = str_replace("|", ",\\n", $vmblastemail);

        $label        = sprintf(_("Ext %s %s\\n%s"), $member, $vmblastname, $vmblastemail);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities('/admin/config.php?display=extensions&extdisplay=' . $member),
            'target'    => '_blank',
            'shape'     => 'rect',
            'fillcolor' => self::PASTELS[16],
            'style'     => 'filled',
        ]);
    }
}
