<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationCallrecording extends BaseDestinations
{
    # Call Recording

    public const PRIORITY = 2250;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-callrecording,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of ext-callrecording,<number>,<number>

        $callrecID    = $matches[1];
        $callrecOther = $matches[2];
        $callRec      = $route['callrecording'][$callrecID];
        $callMode     = ucfirst($callRec['callrecording_mode']);
        $callMode     = str_replace("Dontcare", _("Don't Care"), $callMode);

        $label        = sprintf(_("Call Recording: %s\\nMode: %s"), $callRec['description'], $callMode);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('callrecording', $callrecID), //admin/config.php?display=callrecording&view=form&extdisplay='.$callrecID
            'target'    => '_blank',
            'fillcolor' => 'burlywood',
            'shape'     => 'rect',
            'style'     => 'filled'
        ]);

        $this->findNextDestination($route, $node, $callRec['dest'], _(" Continue"));
    }
}
