<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationMisc extends baseDestinations
{
    public const PRIORITY = 7000;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ext-miscdests,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $miscdestnum   = $matches[1];
        $miscdestother = $matches[2];
        $miscdest      = $route['miscdest'][$miscdestnum];

        $label = sprintf(_("Misc Dest: %s (%s)"), $this->dpp->sanitizeLabels($miscdest['description']), $miscdest['destdial']);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'URL'       => $this->genUrlConfig('miscdests', $miscdestnum), //'/admin/config.php?display=miscdests&view=form&extdisplay='.$miscdestnum
            'target'    => '_blank',
            'shape'     => 'rpromoter',
            'fillcolor' => 'coral',
            'style'     => 'filled',
        ]);
    }
}
