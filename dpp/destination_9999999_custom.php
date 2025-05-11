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
                if (sprintf("%s,%s", $entry['target'], $this->lang) === $destination)
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
            $label      = sprintf(_("Cust Dest: %s\\nTarget: %s\\nReturn: %s\\n"), $entry['description'], $entry['target'], $custReturn);

            $this->updateNodeAttribute($node, [
                'label'     => $label,
                'tooltip'   => $label,
                'URL'       => htmlentities('/admin/config.php?display=customdests&view=form&destid='.$custId),
                'target'    => '_blank',
                'shape'     => 'component',
                'fillcolor' => self::pastels[27],
                'style'     => 'filled',
            ]);

            if ($custDest['destret'])
            {
                $this->findNextDestination($route, $node, $custDest['dest'], _(' Return'));
			}
        }
        else
        {
            $this->log(1, sprintf(_("Unknown destination type: %s"), $destination));
            $this->updateNodeAttribute($node, [
                'label'     => $destination,
                'fillcolor' => self::pastels[12],
                'style'    => 'filled',
            ]);
        }
    }
}
