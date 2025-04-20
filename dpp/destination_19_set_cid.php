<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationSetCid extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-setcid,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $cidnum   = $matches[1];
        $cidother = $matches[2];
        $cid 	  = $route['setcid'][$cidnum];
        $cidLabel = 'Set CID\\nName= '.preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name']).'\\lNumber= '.preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']).'\\l';

        $node->attribute('label', $this->dpp->sanitizeLabels($cidLabel));
        $node->attribute('tooltip', $node->getAttribute('label'));
        $node->attribute('URL', htmlentities('/admin/config.php?display=setcid&view=form&id='.$cidnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'note');
        $node->attribute('fillcolor', self::pastels[6]);
        $node->attribute('style', 'filled');

        if ($cid['dest'] != '')
        {
            $route['parent_edge_label'] = ' Continue';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, $cid['dest'],'');
        }
    }
}