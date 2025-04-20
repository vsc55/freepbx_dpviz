<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationDynroute extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^dynroute-(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $dynnum = $matches[1];
        $dynrt  = $route['dynroute'][$dynnum];
        
        $recID = $dynrt['announcement_id'];
        
        $announcement = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
        $node->attribute('label', 'DYN: '.$this->dpp->sanitizeLabels($dynrt['name']).'\\nAnnouncement: '.$this->dpp->sanitizeLabels($announcement));
        $node->attribute('tooltip', $node->getAttribute('label'));
        $node->attribute('URL', htmlentities('/admin/config.php?display=dynroute&action=edit&id='.$dynnum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'component');
        $node->attribute('fillcolor', self::pastels[12]);
        $node->attribute('style', 'filled');

        if (!empty($dynrt['routes']))
        {
            ksort($dynrt['routes']);
            foreach ($dynrt['routes'] as $selid => $ent)
            {
                $route['parent_edge_label']= '  Match: '.$this->dpp->sanitizeLabels($ent['selection']).'\\n'.$this->dpp->sanitizeLabels($ent['description']);
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $ent['dest'],'');
            }
        }
        
        //are the invalid and timeout destinations the same?
        if ($dynrt['invalid_dest'] == $dynrt['default_dest'])
        {
            $route['parent_edge_label']= ' Invalid Input, Default ('.$dynrt['timeout'].' secs)';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, $dynrt['invalid_dest'],'');
        }
        else
        {
            if ($dynrt['invalid_dest'] != '')
            {
                $route['parent_edge_label']= ' Invalid Input';
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $dynrt['invalid_dest'],'');
            }
            if ($dynrt['default_dest'] != '')
            {
                $route['parent_edge_label']= ' Default ('.$dynrt['timeout'].' secs)';
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $dynrt['default_dest'],'');
            }
        }
    }
}