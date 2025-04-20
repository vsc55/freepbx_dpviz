<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationMisc extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-miscdests,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $miscdestnum   = $matches[1];
        $miscdestother = $matches[2];

        $miscdest = $route['miscdest'][$miscdestnum];
        $node->attribute('label', 'Misc Dest: '.$this->dpp->sanitizeLabels($miscdest['description']).' ('.$miscdest['destdial'].')');
        $node->attribute('URL', htmlentities('/admin/config.php?display=miscdests&view=form&extdisplay='.$miscdestnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'rpromoter');
        $node->attribute('fillcolor', 'coral');
        $node->attribute('style', 'filled');
    }
}