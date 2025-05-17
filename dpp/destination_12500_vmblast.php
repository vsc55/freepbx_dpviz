<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationVmblast extends BaseDestinations
{
    public const PRIORITY = 12500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^vmblast\-grp,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of vmblast-grp,<number>,<number>

        $vmblastnum   = $matches[1];
        $vmblastother = $matches[2];
        $vmblast      = $route['vmblasts'][$vmblastnum];

        $label = sprintf(_("VM Blast: %s %s"), $vmblastnum, $vmblast['description']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('vmblast', $vmblastnum), //'/admin/config.php?display=vmblast&view=form&extdisplay='.$vmblastnum
            'target'    => '_blank',
            'shape'     => 'folder',
            'fillcolor' => 'gainsboro',
            'style'     => 'filled',
        ]);

        if (!empty($vmblast['members'])) {
            foreach ($vmblast['members'] as $member) {
                $this->findNextDestination(
                    $route,
                    $node,
                    sprintf('vmblast-mem,%s', $member),
                    ''
                );
            }
        }
    }
}
