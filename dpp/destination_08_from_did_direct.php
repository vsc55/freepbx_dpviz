<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationFromDidDirect extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^from-did-direct,(\d+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $extnum    = $matches[1];
        $extother  = $matches[2];
        $extension = $route['extensions'][$extnum];
        $extname   = $extension['name'];
        $extemail  = $extension['email'];
        $extemail  = str_replace("|",",\\n",$extemail);

        $label     = sprintf(_('Extension: %s %s\\n%s'), $extnum, $this->dpp->sanitizeLabels($extname), $this->dpp->sanitizeLabels($extemail));

        $node->attribute('label', $label);
        $node->attribute('tooltip', $node->getAttribute('label'));
        $node->attribute('URL', $this->genUrlConfig('extensions', $extnum, null)); //'/admin/config.php?display=extensions&extdisplay='.$extnum
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'house');
        $node->attribute('fillcolor', self::pastels[15]);
        $node->attribute('style', 'filled');
        
        //Optional Destinations
        if (!empty($extension['noanswer_dest']) || !empty($extension['busy_dest']) || !empty($extension['chanunavail_dest']) )
        {
            if ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['noanswer_dest'] === $extension['chanunavail_dest'])
            {
                // All three are equal
                $route['parent_node']       = $node;
                $route['parent_edge_label'] = _(' No Answer, Busy, Not Reachable');
                
                $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
            }
            elseif ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['chanunavail_dest'] !== $extension['noanswer_dest'])
            {
                // No Answer and Busy are the same, but Not Reachable is different
                $route['parent_node']       = $node;
                $route['parent_edge_label'] = _(' No Answer & Busy');
                
                $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');

                if (!empty($extension['chanunavail_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Not Reachable');
                    
                    $this->dpp->followDestinations($route, $extension['chanunavail_dest'], '');
                }
            }
            elseif ($extension['noanswer_dest'] === $extension['chanunavail_dest'] && $extension['busy_dest'] !== $extension['noanswer_dest'])
            {
                // No Answer and Not Reachable are the same
                $route['parent_node']       = $node;
                $route['parent_edge_label'] = _(' No Answer & Not Reachable');
                
                $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');

                if (!empty($extension['busy_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Busy');
                    
                    $this->dpp->followDestinations($route, $extension['busy_dest'], '');
                }
            }
            elseif ($extension['busy_dest'] === $extension['chanunavail_dest'] && $extension['noanswer_dest'] !== $extension['busy_dest'])
            {
                // Busy and Not Reachable are the same
                $route['parent_node']       = $node;
                $route['parent_edge_label'] = _(' Busy & Not Reachable');
                
                $this->dpp->followDestinations($route, $extension['busy_dest'], '');

                if (!empty($extension['noanswer_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' No Answer');
                    
                    $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
                }
            }
            else
            {
                // All are different
                if (!empty($extension['noanswer_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' No Answer');
                    
                    $this->dpp->followDestinations($route, $extension['noanswer_dest'], '');
                }
                if (!empty($extension['busy_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Busy');
                    
                    $this->dpp->followDestinations($route, $extension['busy_dest'], '');
                }
                if (!empty($extension['chanunavail_dest']))
                {
                    $route['parent_node']       = $node;
                    $route['parent_edge_label'] = _(' Not Reachable');
                    
                    $this->dpp->followDestinations($route, $extension['chanunavail_dest'],'');
                }
            }
        }
    }
}