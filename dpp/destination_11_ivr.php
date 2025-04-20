<?php
namespace FreePBX\modules\Dpviz\dpp\destination;

require_once __DIR__ . '/baseDestinations.php';

class DestinationIvr extends baseDestinations
{
    public function __construct(object &$dpp)
    {
        parent::__construct($dpp);
        $this->regex = "/^ivr-(\d+),([a-z]+),(\d+)/";
    }

    public function callback_followDestinations(&$route, &$node, $destination, $matches)
    {
        $inum   = $matches[1];
        $iflag  = $matches[2];
        $iother = $matches[3];

        $ivr		= $route['ivrs'][$inum];
        $recID		= $ivr['announcement'];
        $ivrRecName = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
        
        #feature code exist?
        if ( isset($route['featurecodes']['*29'.$ivr['announcement']]) )
        {
            #custom feature code?
            if ($route['featurecodes']['*29'.$ivr['announcement']]['customcode']!='')
            {
                $featurenum = $route['featurecodes']['*29'.$ivr['announcement']]['customcode'];
            }
            else
            {
                $featurenum = $route['featurecodes']['*29'.$ivr['announcement']]['defaultcode'];
            }
            #is it enabled?
            if (($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') )
            {
                $rec = '(yes): '.$featurenum;
            }
            else
            {
                $rec = '(no): '.$featurenum;
            }
        }
        else
        {
            $rec = '(no): disabled';
        }

        $node->attribute('label', "IVR: ".$this->dpp->sanitizeLabels($ivr['name'])."\\nAnnouncement: ".$this->dpp->sanitizeLabels($ivrRecName)."\\lRecord ".$rec."\\l");
        $node->attribute('tooltip', $node->getAttribute('label'));
        $node->attribute('URL', htmlentities('/admin/config.php?display=ivr&action=edit&id='.$inum));
        $node->attribute('target', '_blank');
        $node->attribute('shape', 'component');
        $node->attribute('fillcolor', 'gold');
        $node->attribute('style', 'filled');

        # The destinations we need to follow are the invalid_destination,
        # timeout_destination, and the selection targets
        
        
        #now go through the selections
        if (!empty($ivr['entries']))
        {
            ksort($ivr['entries']);
            foreach ($ivr['entries'] as $selid => $ent)
            {
                $route['parent_edge_label']= ' Selection '.$this->dpp->sanitizeLabels($ent['selection']);
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $ent['dest'],'');
            }
        }
        
        #are the invalid and timeout destinations the same?
        if ($ivr['invalid_destination']==$ivr['timeout_destination'])
        {
            $route['parent_edge_label']= ' Invalid Input, Timeout ('.$ivr['timeout_time'].' secs)';
            $route['parent_node'] = $node;
            $this->dpp->followDestinations($route, $ivr['invalid_destination'],'');
        }
        else
        {
            if ($ivr['invalid_destination'] != '') 
            {
                $route['parent_edge_label']= ' Invalid Input';
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $ivr['invalid_destination'],'');
            }
            if ($ivr['timeout_destination'] != '') {
                $route['parent_edge_label']= ' Timeout ('.$ivr['timeout_time'].' secs)';
                $route['parent_node'] = $node;
                $this->dpp->followDestinations($route, $ivr['timeout_destination'],'');
            }
        }
    }
}