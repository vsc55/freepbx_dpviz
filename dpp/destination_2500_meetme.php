<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationMeetme extends BaseDestinations
{
    public const PRIORITY = 2500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-meetme,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-meetme,<number>,<number>

        $meetmenum   = $matches[1];
        $meetmeother = $matches[2];
        $meetme      = $route['meetme'][$meetmenum];

        $label       = sprintf(_("Conferences: %s %s"), $meetme['exten'], $meetme['description']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('conferences', $meetmenum), // '/admin/config.php?display=conferences&view=form&extdisplay='.$meetmenum
            'target'    => '_blank',
            'fillcolor' => 'burlywood',
            'style'     => 'filled'
        ]);
    }
}
