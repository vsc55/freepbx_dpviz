<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationRingGroupsMembers extends BaseDestinations
{
    public const PRIORITY = 10000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^rg(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of rg<rgnum>

        $rgext = $matches[1];
        $label = $rgext;
        if (isset($route['extensions'][$rgext])) {
            $label = sprintf(_("Ext %s\\n%s"), $rgext, $route['extensions'][$rgext]['name']);
        }

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => is_numeric($label) ? '__SKIP_NO_CHANGE__' : htmlentities('/admin/config.php?display=extensions&extdisplay=' . $rgext),
            'target'    => is_numeric($label) ? '__SKIP_NO_CHANGE__' : '_blank',
            'fillcolor' => self::PASTELS[2],
            'style'     => 'filled',
        ]);
    }
}
