<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationTimeconditions extends BaseDestinations
{
    public const PRIORITY = 11500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^timeconditions,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of timeconditions,<number>,<number>

        $tcnum     = $matches[1];
        $tcother   = $matches[2];
        $tc        = $route['timeconditions'][$tcnum];
        $label     = sprintf(_("TC: %s"), $tc['displayname']);
        $tcTooltip = sprintf(_("%s\\nMode= %s"), $tc['displayname'], $tc['mode']);

        if (!empty($tc['timezone'])) {
            $tcTooltip .= ($tc['timezone'] !== 'default') ? sprintf(_("\\nTimezone= %s"), $tc['timezone']) : _("Undefined");
        }

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $tcTooltip,
            'URL'       => htmlentities('/admin/config.php?display=timeconditions&view=form&itemid=' . $tcnum),
            'target'    => '_blank',
            'shape'     => 'invhouse',
            'fillcolor' => 'dodgerblue',
            'style'     => 'filled',
        ]);

        $tgLabel   = '';
        $tgLink    = '';
        $tgTooltip = '';

        //TC modes
        if ($tc['mode'] === 'time-group') {
            $tg        = $route['timegroups'][$tc['time']];
            $tgnum     = $tg['id'];
            $tgname    = $tg['description'];
            $tgtime    = !empty($tg['time']) ? $tg['time'] : _("No times defined");
            $tgLabel   = sprintf("%s\\n%s", $tgname, $tgtime);
            $tgLink    = $this->genUrlConfig('timegroups', $tgnum); // '/admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum;
            $tgTooltip = $tgLabel;
        } elseif ($tc['mode'] === 'calendar-group') {
            if (!empty($route['calendar'][$tc['calendar_id']])) {
                $cal       = $route['calendar'][$tc['calendar_id']];
                $tgLabel   = $cal['name'];
                $tgLink    = '/admin/config.php?display=calendar&action=view&type=calendar&id=' . $tc['calendar_id'];
                $tz        = empty($cal['timezone']) ? _("Undefined") : $cal['timezone'];
                $tgTooltip = sprintf(_("Name= %s\\nDescription= %s\\nType= %s\\nTimezone= %s"), $cal['name'], $cal['description'], $cal['type'], $tz);
            } elseif (!empty($route['calendar'][$tc['calendar_group_id']])) {
                $cal      = $route['calendar'][$tc['calendar_group_id']];
                $tgLabel  = $cal['name'];
                $tgLink   = '/admin/config.php?display=calendargroups&action=edit&id=' . $tc['calendar_group_id'];
                $calNames = _("Calendars= ");
                if (!empty($cal['calendars'])) {
                    foreach ($cal['calendars'] as $c) {
                        $calNames .= sprintf("%s\\n", $route['calendar'][$c]['name']);
                    }
                }
                $cats       = !empty($cal['categories']) ? count($cal['categories']) : _("All");
                $categories = sprintf(_("Categories= %s"), $cats);
                $eves       = !empty($cal['events']) ? count($cal['events']) : _("All");
                $events     = sprintf(_("Events= %s"), $eves);
                $expand     = $cal['expand'] ? 'true' : 'false';
                $tgTooltip  = sprintf(_("Name= %s\\n%s\\n%s\\n%s\\nExpand= %s"), $cal['name'], $calNames, $categories, $events, $expand);
            }
        }

        # Now set the current node to be the parent and recurse on both the true and false branches
        $route['parent_edge_url']          = htmlentities($tgLink);
        $route['parent_edge_target']       = '_blank';
        $route['parent_edge_labeltooltip'] = $this->dpp->sanitizeLabels(sprintf(_(" Match:\\n%s"), $tgTooltip));
        $this->findNextDestination(
            $route,
            $node,
            $tc['truegoto'],
            sprintf(_(" Match:\\n%s"), $tgLabel)
        );


        $route['parent_edge_url']          = htmlentities($tgLink);
        $route['parent_edge_target']       = '_blank';
        $route['parent_edge_labeltooltip'] = $this->sanitizeLabels(sprintf(_(" No Match:\\n%s"), $tgTooltip));
        $this->findNextDestination($route, $node, $tc['falsegoto'], _(" No Match"));
    }
}
