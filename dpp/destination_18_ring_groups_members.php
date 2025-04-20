<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationRingGroupsMembers extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^rg(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $rgext   = $matches[1];
        $rglabel = isset($route['extensions'][$rgext]) ? 'Ext '.$rgext.'\\n'.$route['extensions'][$rgext]['name'] : $rgext;

        $node->attribute('label', $this->dpp->sanitizeLabels($rglabel));
        $node->attribute('tooltip', $node->getAttribute('label'));
        if (!is_numeric($rglabel))
        {
            $node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$rgext));
            $node->attribute('target', '_blank');
        }
        $node->attribute('fillcolor', self::pastels[2]);
        $node->attribute('style', 'filled');
    }
}