<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationLanguages extends baseDestinations
{
    public const PRIORITY = 6500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-languages,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $langnum   = $matches[1];
        $langother = $matches[2];

        $langArray = $route['languages'][$langnum];
        $lable     = $this->sanitizeLabels(sprintf(_("Languages: %s"), $langArray['description']));

        $node->attribute('label', $lable);
        $node->attribute('URL', $this->genUrlConfig('languages', $langnum)); //'/admin/config.php?display=languages&view=form&extdisplay='.$langnum
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'note');
        $node->attribute('fillcolor', self::pastels[6]);
        $node->attribute('style', 'filled');
		// $node->attribute('comment', $langArray['lang_code']); //update $lang
        $this->setLanguage($langArray['lang_code'], $node);

        if ($langArray['dest'] != '')
        {
            $route['parent_node']       = $node;
            $route['parent_edge_label'] = _(" Continue");

            $this->dpp->followDestinations($route, $this->applyLanguage($langArray['dest'], $langArray['lang_code']), '');
        }
    }
}
