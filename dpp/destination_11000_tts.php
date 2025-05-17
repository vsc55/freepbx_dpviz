<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationTts extends BaseDestinations
{
    public const PRIORITY = 11000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-tts,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-tts,<number>,<number>

        $ttsnum     = $matches[1];
        $ttsother   = $matches[2];
        $tts        = $route['tts'][$ttsnum];

        $ttsLabel   = sprintf(_("TTS: %s"), $tts['name']);
        $ttsTooltip = sprintf(_("Engine: %s\\nDesc: %s"), $tts['engine'], $tts['text']);

        $this->updateNodeAttribute($node, [
            'label'     => $ttsLabel,
            'tooltip'   => $ttsTooltip,
            'URL'       => htmlentities('/admin/config.php?display=tts&view=form&id=' . $ttsnum),
            'target'    => '_blank',
            'shape'     => 'note',
            'fillcolor' => self::PASTELS[6],
            'style'     => 'filled',
        ]);

        if ($tts['goto'] != '') {
            $this->findNextDestination($route, $node, $tts['goto'], _(" Continue"));
        }
    }
}
