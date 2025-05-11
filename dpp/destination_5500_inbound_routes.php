<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationInboundRoutes extends baseDestinations
{
    public const PRIORITY = 5500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^from-trunk,([^,]*),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $num      = $matches[1];
        $numother = $matches[2];

        $incoming = $route['incoming'][$num];

        $label   = $this->sanitizeLabels(sprintf("%s\\n%s", ($num == '') ? _("ANY") : $this->dpp->formatPhoneNumbers($num), $incoming['description']));
        $didLink = sprintf('%s/', $num);

        $node->attribute('label', $label);
        $node->attribute('tooltip', $label);
        $node->attribute('URL', $this->genUrlConfig('did', urlencode($didLink))); //'/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'cds');
        $node->attribute('fillcolor', 'darkseagreen');
        $node->attribute('style', 'filled');


        $this->findNextDestination($route, $node, $incoming['destination'], _(" Continue"));

        // $route['parent_node']       = $node;
        // $route['parent_edge_label'] = _(" Continue");

        // $this->dpp->followDestinations($route, $this->applyLanguage($incoming['destination']), '');
    }
}
