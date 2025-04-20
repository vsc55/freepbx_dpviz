<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationTimeconditions extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^timeconditions,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $tcnum 	 = $matches[1];
        $tcother = $matches[2];
        $tc      = $route['timeconditions'][$tcnum];

        $label = sprintf(_('TC: %s'), $this->dpp->sanitizeLabels($tc['displayname']));

        $node->attribute('label', $label);
        $node->attribute('URL', htmlentities('/admin/config.php?display=timeconditions&view=form&itemid='.$tcnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'invhouse');
        $node->attribute('fillcolor', 'dodgerblue');
        $node->attribute('style', 'filled');
    
        # Not going to use the time group info for right now.  Maybe put it in the edge text?
        $tgname = $route['timegroups'][$tc['time']]['description'];
        $tgtime = $route['timegroups'][$tc['time']]['time'];
        $tgnum  = $route['timegroups'][$tc['time']]['id'];
        
        # Now set the current node to be the parent and recurse on both the true and false branches
        $route['parent_node']        = $node;
        $route['parent_edge_label']  = sprintf(_(' Match:\\n%s\\n%s'), $this->dpp->sanitizeLabels($tgname), $tgtime);
        $route['parent_edge_url']    = $this->genUrlConfig('timegroups', $tgnum); // /admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum
        $route['parent_edge_target'] = '_blank';

        $this->dpp->followDestinations($route, $tc['truegoto'], '');

        
        $route['parent_node']        = $node;
        $route['parent_edge_label']  = _(' NoMatch');
        $route['parent_edge_url']    = $this->genUrlConfig('timegroups', $tgnum); // /admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum)
        $route['parent_edge_target'] = '_blank';
        
        $this->dpp->followDestinations($route, $tc['falsegoto'], '');
    }
}