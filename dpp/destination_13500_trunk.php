<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationTrunk extends BaseDestinations
{
    public const PRIORITY = 13500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-trunk,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-trunk,<number>,<number>

        $trunk_id       = $matches[1];
        $trunk_priority = $matches[2];
        $trunk_tech     = $route['trunk'][$trunk_id]['tech'];
        $trunk_name     = $route['trunk'][$trunk_id]['name'];

        $label = sprintf(_("Trunk (%s): %s"), $trunk_tech, $trunk_name);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities(sprintf('/admin/config.php?display=trunks&tech=%s&extdisplay=OUT_%s', $trunk_tech, $trunk_id)),
            'target'    => '_blank',
            'shape'     => 'note',
            'fillcolor' => 'oldlace',
            'style'     => 'filled',
        ]);
    }
}
