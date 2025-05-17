<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationCustomDestinations extends BaseDestinations
{
    #Custom Destinations (with return)

    public const PRIORITY = 13250;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^customdests,dest-(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of customdests,dest-<number>,<number>

        $custId     = $matches[1];
        $custDest   = $route['customapps'][$custId];
        $custReturn = ($custDest['destret'] == 1) ? _("Yes") : _("No");
        $label      = sprintf(_("Cust Dest: %s\\nTarget:%s\\nReturn: %s\\n"), $custDest['description'], $custDest['target'], $custReturn);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities('/admin/config.php?display=customdests&view=form&destid=' . $custId),
            'target'    => '_blank',
            'shape'     => 'component',
            'fillcolor' => self::PASTELS[27],
            'style'     => 'filled',
        ]);

        if ($custDest['destret']) {
            $this->findNextDestination($route, $node, $custDest['dest'], _(' Return'));
        }
    }
}
