<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationRecording extends baseDestinations
{
    public const PRIORITY = 7500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^play-system-recording,(\d+),(\d+),(.+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $recID = $matches[1];
        $recOther = $matches[2];
		$recLang  = $matches[3];

		if (isset($route['recordings'][$recID]))
        {
			$rec      = $route['recordings'][$recID];
			$playName = $rec['displayname'];
		}
        else
        {
			$playName = _('None');
		}

        $label = $this->sanitizeLabels(sprintf(_("Recording (%s): %s"), $this->lang, $playName));

		$node->attribute('label', $label);
		$node->attribute('tooltip', $label);
		$node->attribute('URL', '#');
        // $node->attribute('URL', htmlentities('/admin/config.php?display=recordings&action=edit&id='.$recID));
        // $node->attribute('target', '_blank');
        $node->attribute('shape', 'rect');
        $node->attribute('fillcolor', self::pastels[16]);
        $node->attribute('style', 'filled');
    }
}
