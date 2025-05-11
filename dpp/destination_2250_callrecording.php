<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationCallrecording extends baseDestinations
{
    # Call Recording

    public const PRIORITY = 2250;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-callrecording,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-callrecording-<id>,<other>

        $callrecID    = $matches[1];
		$callrecOther = $matches[2];
		$callRec      = $route['callrecording'][$callrecID];
		$callMode     = ucfirst($callRec['callrecording_mode']);
		$callMode     = str_replace("Dontcare", _("Don't Care"), $callMode);

        $label = $this->sanitizeLabels(sprintf(_("Call Recording: %s\\nMode: %s"), $callRec['description'], $callMode));

		$node->attribute('label', $label);
        $node->attribute('URL', $this->genUrlConfig('callrecording', $callrecID)); //admin/config.php?display=callrecording&view=form&extdisplay='.$callrecID
		$node->attribute('target', '_blank');
		$node->attribute('fillcolor', 'burlywood');
		$node->attribute('shape', 'rect');
		$node->attribute('style', 'filled');


        $this->findNextDestination($route, $node, $callRec['dest'], _(" Continue"));
        // $route['parent_node']       = $node;
		// $route['parent_edge_label'] = _(" Continue");

        // $this->dpp->followDestinations($route, $this->applyLanguage($callRec['dest']), '');
    }
}
