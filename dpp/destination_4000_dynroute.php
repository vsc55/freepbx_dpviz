<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationDynroute extends baseDestinations
{
    public const PRIORITY = 4000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^dynroute-(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $dynnum       = $matches[1];
        $dynrt        = $route['dynroute'][$dynnum];
        $recID        = $dynrt['announcement_id'];
        $announcement = _("None");

        if (isset($route['recordings'][$recID]))
        {
			$recording    = $route['recordings'][$recID];
			$announcement = $recording['displayname'];
			$recordingId  = $recording['id'];
		}

        $label = sprintf(_("DYN: %s\\nAnnouncement: %s"), $dynrt['name'], $announcement);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities('/admin/config.php?display=dynroute&action=edit&id='.$dynnum),
            'target'    => '_blank',
            'shape'     => 'component',
            'fillcolor' => self::pastels[12],
            'style'     => 'filled'
        ]);

        if (!empty($dynrt['routes']))
        {
            ksort($dynrt['routes']);
            foreach ($dynrt['routes'] as $selid => $ent)
            {
                $this->findNextDestination($route, $node, $ent['dest'],
                    sprintf(_("  Match: %s\\n%s"), $ent['selection'], isset($ent['description']) ? $ent['description'] : '')
                );
            }
        }

        if (isset($route['recordings'][$recID]))
        {
            $dest = sprintf("play-system-recording,%s,1", $recordingId);
            $this->findNextDestination($route, $node, $dest, _(" Recording"));
		}

        //are the invalid and timeout destinations the same?
        if ($dynrt['invalid_dest'] != '' && $dynrt['invalid_dest'] == $dynrt['default_dest'])
        {
            $this->findNextDestination($route, $node, $dynrt['invalid_dest'],
                sprintf(_(" Invalid Input, Default (%s secs)"), $dynrt['timeout'])
            );
        }
        else
        {
            if ($dynrt['invalid_dest'] != '')
            {
                $this->findNextDestination($route, $node, $dynrt['invalid_dest'], _(" Invalid Input"));
            }
            if ($dynrt['default_dest'] != '')
            {
                $this->findNextDestination($route, $node, $dynrt['default_dest'],
                    sprintf(_(" Default (%s secs)"), $dynrt['timeout'])
                );
            }
        }
    }
}
