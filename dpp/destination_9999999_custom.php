<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationCustom extends baseDestinations
{
    //public const PRIORITY = 9999999;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/.*/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        if (!empty($route['customapps']))
        {
            #custom destinations
            $custDest = null;
            foreach ($route['customapps'] as $entry)
            {
                if (sprintf("%s,%s", $entry['target'], $lang) === $destination)
                {
                    $custDest = $entry;
                    break;
                }
            }
        }
        #end of Custom Destinations

        if (!empty($custDest))
        {
			$custId     = $custDest['destid'];
			$custReturn = ($custDest['destret'] == 1) ? _("Yes") : _("No");
            $label      = $this->sanitizeLabels(sprintf(_("Cust Dest: %s\\nTarget: %s\\nReturn: %s\\n"), $entry['description'], $entry['target'], $custReturn));

            $node->attribute('label', $label);
            $node->attribute('tooltip', $label);

            $node->attribute('URL', htmlentities('/admin/config.php?display=customdests&view=form&destid='.$custId));
            $node->attribute('target', '_blank');
            $node->attribute('shape', 'component');
            $node->attribute('fillcolor', self::pastels[27]);
            $node->attribute('style', 'filled');

            if ($custDest['destret'])
            {
                $this->findNextDestination($route, $node, $custDest['dest'], _(' Return'));

                // $route['parent_node']       = $node;
				// $route['parent_edge_label'] = _(' Return');

                // $this->dpp->followDestinations($route, sprintf("%s,%s", $custDest['dest'], $lang), '');
			}
        }
        else
        {
            $this->log(1, sprintf(_("Unknown destination type: %s"), $destination));

            $node->attribute('fillcolor', self::pastels[12]);
            $node->attribute('label', $this->dpp->sanitizeLabels($destination));
            $node->attribute('style', 'filled');
        }
    }
}
