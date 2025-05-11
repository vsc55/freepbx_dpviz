<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationBlackhole extends baseDestinations
{
    public const PRIORITY = 1500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-blackhole,(hangup|congestion|busy|zapateller|musiconhold|ring|no-service),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of app-blackhole-<type>,<num>

        $blackholetype = $matches[1];
		$blackholetype = str_replace('musiconhold', _("Music On Hold"), $blackholetype);
		$blackholetype = str_replace('ring', _("Play Ringtones"), $blackholetype);
		$blackholetype = str_replace('no-service', _("Play No Service Message"), $blackholetype);
		$blackholetype = ucwords(str_replace('-', ' ', $blackholetype));
        $blackholeother = $matches[2];

        $labal          = $this->sanitizeLabels(sprintf(_("Terminate Call: %s"), $blackholetype));
        $previousURL    = $route['parent_node']->getAttribute('URL', '');

        $node->attribute('label', $labal);
        $node->attribute('tooltip', $labal);
        $node->attribute('URL', $previousURL);
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'invhouse');
        $node->attribute('fillcolor', 'orangered');
        $node->attribute('style', 'filled');
    }
}
