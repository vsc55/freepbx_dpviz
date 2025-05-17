<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationDisa extends BaseDestinations
{
    public const PRIORITY = 3500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^disa,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of disa,<number>,<number>

        $disanum   = $matches[1];
        $disaother = $matches[2];
        $disa      = $route['disa'][$disanum];

        $label     = sprintf(_("DISA: %s"), $disa['displayname']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => htmlentities('/admin/config.php?display=disa&view=form&itemid=' . $disanum),
            'target'    => '_blank',
            'fillcolor' => self::PASTELS[10],
            'style'     => 'filled'
        ]);
    }
}
