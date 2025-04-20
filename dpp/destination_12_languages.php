<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationLanguages extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-languages,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $langnum   = $matches[1];
        $langother = $matches[2];

        $lang = $route['languages'][$langnum];
        $node->attribute('label', 'Languages: '.$this->dpp->sanitizeLabels($lang['description']));
        $node->attribute('URL', htmlentities('/admin/config.php?display=languages&view=form&extdisplay='.$langnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'note');
        $node->attribute('fillcolor', self::pastels[6]);
        $node->attribute('style', 'filled');

        if ($lang['dest'] != '')
        {
            $route['parent_edge_label'] = ' Continue';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, $lang['dest'],'');
        }
    }
}