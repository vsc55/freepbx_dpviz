<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationQueues extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-queues,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $qnum   = $matches[1];
        $qother = $matches[2];

        $q = $route['queues'][$qnum];
        if ($q['maxwait'] == 0 || $q['maxwait'] == '' || !is_numeric($q['maxwait']))
        {
            $maxwait = 'Unlimited';
        }
        else
        {
            $maxwait = $this->dpp->secondsToTimes($q['maxwait']);
        }

        $node->attribute('label', 'Queue '.$qnum.': '.$this->dpp->sanitizeLabels($q['descr']));
        $node->attribute('URL', htmlentities('/admin/config.php?display=queues&view=form&extdisplay='.$qnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'hexagon');
        $node->attribute('fillcolor', 'mediumaquamarine');
        $node->attribute('style', 'filled');
            
        if (!empty($q['members']))
        {
            foreach ($q['members'] as $types=>$type)
            {
                foreach ($type as $members)
                {
                    $route['parent_node'] = $node;
                    $route['parent_edge_label'] = ($types == 'static') ? ' Static' : ' Dynamic';
                    $this->dpp->followDestinations($route, 'qmember'.$members,'');
                }
            }
        }
        
        # The destinations we need to follow are the queue members (extensions)
        # and the no-answer destination.
        if ($q['dest'] != '') 
        {
            $route['parent_edge_label'] = ' No Answer ('.$maxwait.')';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, $q['dest'],'');
        }
        
        if (is_numeric($q['ivr_id']))
        {
            $route['parent_edge_label'] = ' IVR Break Out (every '.$this->dpp->secondsToTimes($q['data']['min-announce-frequency']).')';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, 'ivr-'.$q['ivr_id'].',s,1','');
        }
    }
}