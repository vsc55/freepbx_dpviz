<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationQueues extends BaseDestinations
{
    public const PRIORITY = 8500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-queues,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-queues,<number>,<number>

        $qnum              = $matches[1];
        $qother            = $matches[2];
        $combineQueueRing  = $this->getSetting('combine_queue_ring');

        $q = $route['queues'][$qnum];
        if ($q['maxwait'] == 0 || $q['maxwait'] == '' || !is_numeric($q['maxwait'])) {
            $maxwait = _("Unlimited");
        } else {
            $maxwait = $this->dpp->secondsToTimes($q['maxwait']);
        }

        $label = sprintf(_("Queue %s: %s"), $qnum, $q['descr']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('queues', $qnum), //'/admin/config.php?display=queues&view=form&extdisplay='.$qnum
            'target'    => '_blank',
            'shape'     => 'hexagon',
            'fillcolor' => 'mediumaquamarine',
            'style'     => 'filled',
        ]);

        if (!empty($q['members'])) {
            foreach ($q['members'] as $types => $type) {
                foreach ($type as $member) {
                    switch ($combineQueueRing) {
                        case "2":
                            $go = sprintf("from-did-direct,%s,1", $member);
                            break;

                        default:
                            $go = sprintf('qmember%s', $member);
                    }

                    $route['parent_edge_data_status'] = $types;
                    $this->findNextDestination($route, $node, $go, ($types == 'static') ? _(" Static") : _(" Dynamic"));
                }
            }
        }

        # The destinations we need to follow are the queue members (extensions)
        # and the no-answer destination.
        if ($q['dest'] != '') {
            $this->findNextDestination($route, $node, $q['dest'], sprintf(_(" No Answer (%s)"), $maxwait));
        }

        if (is_numeric($q['ivr_id'])) {
            $this->findNextDestination(
                $route,
                $node,
                sprintf('ivr-%s,s,1', $q['ivr_id']),
                sprintf(_(" IVR Break Out (every %s)"), $this->dpp->secondsToTimes($q['data']['min-announce-frequency']))
            );
        }
    }
}
