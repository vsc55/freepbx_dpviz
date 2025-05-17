<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationMisc extends BaseDestinations
{
    public const PRIORITY = 7000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-miscdests,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-miscdests,<number>,<number>

        $miscdestnum   = $matches[1];
        $miscdestother = $matches[2];
        $miscdest      = $route['miscdest'][$miscdestnum];

        $label = sprintf(_("Misc Dest: %s (%s)"), $this->dpp->sanitizeLabels($miscdest['description']), $miscdest['destdial']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('miscdests', $miscdestnum), //'/admin/config.php?display=miscdests&view=form&extdisplay='.$miscdestnum
            'target'    => '_blank',
            'shape'     => 'rpromoter',
            'fillcolor' => 'coral',
            'style'     => 'filled',
        ]);
    }
}
