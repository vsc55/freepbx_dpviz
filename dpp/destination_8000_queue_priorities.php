<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationQueuePriorities extends BaseDestinations
{
    # Queue Priorities
    public const PRIORITY = 8000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-queueprio,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of app-queueprio,<number>,<number>

        $queueprioID      = $matches[1];
        $queueprioIDOther = $matches[2];
        $queueprio        = $route['queueprio'][$queueprioID];

        $label            = sprintf(_("Queue Priorities: %s\\nPriority: %s"), $queueprio['description'], $queueprio['queue_priority']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => $this->genUrlConfig('queueprio', $queueprioID), //'/admin/config.php?display=queueprio&view=form&extdisplay='.$queueprioID
            'target'    => '_blank',
            'shape'     => 'rect',
            'fillcolor' => self::PASTELS[16],
            'style'     => 'filled',
        ]);

        if ($queueprio['dest'] != '') {
            $this->findNextDestination($route, $node, $queueprio['dest'], _(" Continue"));
        }
    }
}
