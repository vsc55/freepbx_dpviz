<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationRingGroups extends baseDestinations
{
    public const PRIORITY = 9500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-group,(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $rgnum            = $matches[1];
        $rg               = $route['ringgroups'][$rgnum];
        $combineQueueRing = $this->getSetting('combine_queue_ring');

        $label            = sprintf(_("Ring Group: %s %s"), $rgnum, $rg['description']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('ringgroups', $rgnum), //'/admin/config.php?display=ringgroups&view=form&extdisplay='.$rgnum
            'target'    => '_blank',
            'fillcolor' => self::pastels[12],
            'style'     => 'filled',
        ]);

        $grplist = str_replace('#', '', $rg['grplist']);
        $grplist = preg_split("/-/", $grplist);

        foreach ($grplist as $member)
        {
            switch ($combineQueueRing)
            {
                case "1":
                    $go = sprintf("qmember%s", $member);
                    break;

                case "2":
                    $go = sprintf("from-did-direct,%s,1", $member);
                    break;

                default:
                    $go = sprintf("rg%s", $member);
            }
            $this->findNextDestination($route, $node, $go, '');
        }

        # The destinations we need to follow are the no-answer destination
        # (postdest) and the members of the group.
        if ($rg['postdest'] != '')
        {
            $this->findNextDestination($route, $node, $rg['postdest'],
                sprintf(_(" No Answer (%s)"), $this->dpp->secondsToTimes($rg['grptime']))
            );
        }
    }
}
