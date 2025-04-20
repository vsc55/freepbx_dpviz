<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationRingGroups extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-group,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $rgnum 	 = $matches[1];
        $rgother = $matches[2];
    
        $rg = $route['ringgroups'][$rgnum];
        $node->attribute('label', 'Ring Groups: '.$rgnum.' '.$this->dpp->sanitizeLabels($rg['description']));
        $node->attribute('URL', htmlentities('/admin/config.php?display=ringgroups&view=form&extdisplay='.$rgnum));
        $node->attribute('target', '_blank');
        $node->attribute('fillcolor', self::pastels[12]);
        $node->attribute('style', 'filled');
        
        $grplist = preg_split("/-/", $rg['grplist']);
    
        foreach ($grplist as $member)
        {
            $route['parent_node'] = $node;
            $route['parent_edge_label'] = '';
            $this->dpp->followDestinations($route, "rg$member",'');
        } 
        
        # The destinations we need to follow are the no-answer destination
        # (postdest) and the members of the group.
        if ($rg['postdest'] != '')
        {
            $route['parent_edge_label'] = ' No Answer ('.$this->dpp->secondsToTimes($rg['grptime']).')';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, $rg['postdest'],'');
        }
    }
}