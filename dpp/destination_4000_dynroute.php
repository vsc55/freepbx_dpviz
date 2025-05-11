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

        if (isset($route['recordings'][$recID]))
        {
			$recording    = $route['recordings'][$recID];
			$announcement = $recording['displayname'];
			$recordingId  = $recording['id'];
		}
        else
        {
			$announcement = _("None");
		}

        $label = $this->sanitizeLabels(sprintf(_("DYN: %s\\nAnnouncement: %s"), $dynrt['name'], $announcement));

        $node->attribute('label', $label);
        $node->attribute('tooltip', $label);
        $node->attribute('URL', htmlentities('/admin/config.php?display=dynroute&action=edit&id='.$dynnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'component');
        $node->attribute('fillcolor', self::pastels[12]);
        $node->attribute('style', 'filled');

        if (!empty($dynrt['routes']))
        {
            ksort($dynrt['routes']);
            foreach ($dynrt['routes'] as $selid => $ent)
            {
                $desc = isset($ent['description']) ? $ent['description'] : '';

                $this->findNextDestination($route, $node, $ent['dest'], sprintf(_("  Match: %s\\n%s"), $ent['selection'], $desc));

                // $route['parent_node']       = $node;
                // $route['parent_edge_label'] = $this->dpp->sanitizeLabels(sprintf(_("  Match: %s\\n%s"), $ent['selection'], $desc));

                // $this->dpp->followDestinations($route, $ent['dest'], '');
            }
        }

        if (isset($route['recordings'][$recID]))
        {
            $dest = sprintf("play-system-recording,%s,1", $recordingId);
            $this->findNextDestination($route, $node, $dest, _(" Recording"));
			// $route['parent_node']       = $node;
			// $route['parent_edge_label'] = _(" Recording");

            // $this->dpp->followDestinations($route, $this->applyLanguage(sprintf("play-system-recording,%s,1", $recordingId)), '');
		}

        //are the invalid and timeout destinations the same?
        if ($dynrt['invalid_dest'] != '' && $dynrt['invalid_dest'] == $dynrt['default_dest'])
        {
            $this->findNextDestination($route, $node, $dynrt['invalid_dest'], sprintf(_(" Invalid Input, Default (%s secs)"), $dynrt['timeout']));
            // $route['parent_node']       = $node;
            // $route['parent_edge_label'] = sprintf(_(" Invalid Input, Default (%s secs)"), $dynrt['timeout']);

            // $this->dpp->followDestinations($route, $this->applyLanguage($dynrt['invalid_dest']), '');
        }
        else
        {
            if ($dynrt['invalid_dest'] != '')
            {
                $this->findNextDestination($route, $node, $dynrt['invalid_dest'], _(" Invalid Input"));
                // $route['parent_node']       = $node;
                // $route['parent_edge_label'] = _(" Invalid Input");

                // $this->dpp->followDestinations($route, $this->applyLanguage($dynrt['invalid_dest']), '');
            }
            if ($dynrt['default_dest'] != '')
            {
                $this->findNextDestination($route, $node, $dynrt['default_dest'], sprintf(_(" Default (%s secs)"), $dynrt['timeout']));
                // $route['parent_node']       = $node;
                // $route['parent_edge_label'] = sprintf(_(" Default (%s secs)"), $dynrt['timeout']);

                // $this->dpp->followDestinations($route, $this->applyLanguage($dynrt['default_dest']), '');
            }
        }
    }
}
