<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationDisa extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^disa,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $disanum   = $matches[1];
        $disaother = $matches[2];
        $disa 	   = $route['disa'][$disanum];

        $node->attribute('label', 'DISA: '.$this->dpp->sanitizeLabels($disa['displayname']));
        $node->attribute('URL', htmlentities('/admin/config.php?display=disa&view=form&itemid='.$disanum));
        $node->attribute('target', '_blank');
        $node->attribute('fillcolor', self::pastels[10]);
        $node->attribute('style', 'filled');
    }
}