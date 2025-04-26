<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationQueues extends baseDestinations
{
    public const PRIORITY = 8500;

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
            $maxwait = _('Unlimited');
        }
        else
        {
            $maxwait = $this->dpp->secondsToTimes($q['maxwait']);
        }

        $label = sprintf(_('Queue %s: %s'), $qnum, $q['descr']);

        $node->attribute('label', $this->dpp->sanitizeLabels($label));
        $node->attribute('URL', $this->genUrlConfig('queues', $qnum)); //'/admin/config.php?display=queues&view=form&extdisplay='.$qnum
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'hexagon');
        $node->attribute('fillcolor', 'mediumaquamarine');
        $node->attribute('style', 'filled');
            
        if (!empty($q['members']))
        {
            foreach ($q['members'] as $types => $type)
            {
                foreach ($type as $members)
                {
                    $route['parent_node']             = $node;
                    //TODO: Check if it works since it is used in query_members!!
                    $route['parent_edge_label']       = ($types == 'static') ? _(' Static') : _(' Dynamic');
                    $route['parent_edge_data_status'] = $types;
                    $this->dpp->followDestinations($route, sprintf('qmember%s', $members),'');
                }
            }
        }
        
        # The destinations we need to follow are the queue members (extensions)
        # and the no-answer destination.
        if ($q['dest'] != '') 
        {
            $route['parent_node']       = $node;
            $route['parent_edge_label'] = sprintf(_(' No Answer (%s)'), $maxwait);
            
            $this->dpp->followDestinations($route, $q['dest'],'');
        }
        
        if (is_numeric($q['ivr_id']))
        {
            $route['parent_node']       = $node;
            $route['parent_edge_label'] = sprintf(_(' IVR Break Out (every %s)'), $this->dpp->secondsToTimes($q['data']['min-announce-frequency']));
            
            $this->dpp->followDestinations($route, sprintf('ivr-%s,s,1', $q['ivr_id']),'');
        }
    }
}