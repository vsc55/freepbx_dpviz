<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationVmblast extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^vmblast\-grp,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $vmblastnum   = $matches[1];
        $vmblastother = $matches[2];
        $vmblast 	  = $route['vmblasts'][$vmblastnum];
        
        $node->attribute('label', 'VM Blast: '.$vmblastnum.' '.$this->dpp->sanitizeLabels($vmblast['description']));
        $node->attribute('URL', htmlentities('/admin/config.php?display=vmblast&view=form&extdisplay='.$vmblastnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'folder');
        $node->attribute('fillcolor', 'gainsboro');
        $node->attribute('style', 'filled');
        
        if (!empty($vmblast['members']))
        {
            foreach ($vmblast['members'] as $member)
            {
                $route['parent_edge_label']= '';
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, 'vmblast-mem,'.$member,'');
            }
        }
    }
}