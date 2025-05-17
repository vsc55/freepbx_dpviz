<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationInboundRoutes extends BaseDestinations
{
    public const PRIORITY = 5500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^from-trunk,([^,]*),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of from-trunk,<number>,<number>

        $num      = $matches[1];
        $numother = $matches[2];
        $incoming = $route['incoming'][$num];

        $label    = sprintf("%s\\n%s", ($num == '') ? _("ANY") : $this->dpp->formatPhoneNumbers($num), $incoming['description']);
        $didLink  = sprintf('%s/', $num);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => $this->genUrlConfig('did', urlencode($didLink)), //'/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)
            'target'    => '_blank',
            'shape'     => 'cds',
            'fillcolor' => 'darkseagreen',
            'style'     => 'filled',
        ]);

        $this->findNextDestination($route, $node, $incoming['destination'], _(" Continue"));
    }
}
