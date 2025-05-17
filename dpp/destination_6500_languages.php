<?php

namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/BaseDestinations.php';

class DestinationLanguages extends BaseDestinations
{
    public const PRIORITY = 6500;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^app-languages,(\d+),(\d+)/";
    }

    public function callbackFollowDestinations(&$route, &$node, $destination, $matches)
    {
        # The destination is in the form of app-languages,<number>,<number>

        $langnum   = $matches[1];
        $langother = $matches[2];

        $langArray = $route['languages'][$langnum];
        $lable     = sprintf(_("Languages: %s"), $langArray['description']);

        $this->updateNodeAttribute($node, [
            'label'     => $lable,
            'URL'       => $this->genUrlConfig('languages', $langnum), //'/admin/config.php?display=languages&view=form&extdisplay='.$langnum
            'target'    => '_blank',
            'shape'     => 'note',
            'fillcolor' => self::PASTELS[6],
            'style'     => 'filled',
            'coomment'  => $langArray['lang_code'], //update $lang
        ]);
        $this->setLanguage($langArray['lang_code']);

        if ($langArray['dest'] != '') {
            $this->findNextDestination(
                $route,
                $node,
                $this->applyLanguage($langArray['dest'], $langArray['lang_code']),
                _(" Continue"),
                false
            );
        }
    }
}
