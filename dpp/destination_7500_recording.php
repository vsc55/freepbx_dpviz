<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationRecording extends BaseDestinations
{
    public const PRIORITY = 7500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^play-system-recording,(\d+),(\d+),(.+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of play-system-recording,<number>,<number>,<language>

        $recID    = $matches[1];
        $recOther = $matches[2];
        $recLang  = $matches[3];
        $playName = _('None');

        if (isset($route['recordings'][$recID])) {
            $rec      = $route['recordings'][$recID];
            $playName = $rec['displayname'];
        }

        $label = sprintf(_("Recording (%s): %s"), $this->lang, $playName);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => '#',
            //'URL'       => htmlentities('/admin/config.php?display=recordings&action=edit&id='.$recID),
            //'target'    => '_blank',
            'shape'     => 'rect',
            'fillcolor' => self::PASTELS[16],
            'style'     => 'filled',
        ]);
    }
}
