<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationSetCid extends BaseDestinations
{
    public const PRIORITY = 10500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-setcid,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of app-setcid,<number>,<number>

        $cidnum   = $matches[1];
        $cidother = $matches[2];
        $cid      = $route['setcid'][$cidnum];

        $cid_name = preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name']);
        $cid_num  = preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']);
        $label    = sprintf(_("Set CID\\nName= %s\\nNumber= %s"), $cid_name, $cid_num);

        $this->updateNodeAttribute($node, [
            'label'     => $label,
            'tooltip'   => $label,
            'URL'       => htmlentities('/admin/config.php?display=setcid&view=form&id=' . $cidnum),
            'target'    => '_blank',
            'shape'     => 'note',
            'fillcolor' => self::PASTELS[6],
            'style'     => 'filled',
        ]);

        if ($cid['dest'] != '') {
            $this->findNextDestination($route, $node, $cid['dest'], _(" Continue"));
        }
    }
}
