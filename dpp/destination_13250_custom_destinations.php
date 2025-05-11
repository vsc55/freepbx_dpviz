<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationCustoDestinations extends baseDestinations
{
    #Custom Destinations (with return)

    public const PRIORITY = 13250;

    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^customdests,dest-(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $custId     = $matches[1];
		$custDest   = $route['customapps'][$custId];
		$custReturn = ($custDest['destret'] == 1) ? _("Yes") : _("No");
		$label      = $this->sanitizeLabels(sprintf(_("Cust Dest: %s\\nTarget:%s\\nReturn: %s\\n"), $custDest['description'], $custDest['target'], $custReturn));

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
}
