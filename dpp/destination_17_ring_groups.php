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
        $rg      = $route['ringgroups'][$rgnum];

        $label   = sprintf(_('Ring Group: %s %s'), $rgnum, $this->dpp->sanitizeLabels($rg['description']));

        $node->attribute('label', $label);
        $node->attribute('URL', $this->genUrlConfig('ringgroups', $rgnum)); //'/admin/config.php?display=ringgroups&view=form&extdisplay='.$rgnum
        $node->attribute('target', '_blank');
        $node->attribute('fillcolor', self::pastels[12]);
        $node->attribute('style', 'filled');
        
        $grplist = preg_split("/-/", $rg['grplist']);
    
        foreach ($grplist as $member)
        {
            $route['parent_node']       = $node;
            $route['parent_edge_label'] = '';
            $this->dpp->followDestinations($route, sprintf("rg%s", $member), '');
        } 
        
        # The destinations we need to follow are the no-answer destination
        # (postdest) and the members of the group.
        if ($rg['postdest'] != '')
        {
            $route['parent_node'] = $node;
            $route['parent_edge_label'] = sprintf(_(' No Answer (%s)'), $this->dpp->secondsToTimes($rg['grptime']));
            
            $this->dpp->followDestinations($route, $rg['postdest'], '');
        }
    }
}