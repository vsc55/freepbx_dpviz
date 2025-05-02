<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true);

// Basic check
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid JSON'));
    exit;
}

$ext  = isset($input['ext']) ? $input['ext'] : '';
$cid  = isset($input['cid']) ? $input['cid'] : '';
$jump = isset($input['jump']) ? $input['jump'] : '';
$iroute=$ext.$cid;
$vizReload=$ext.','.$cid;


// load graphviz library
require_once 'graphviz/src/Alom/Graphviz/InstructionInterface.php';
require_once 'graphviz/src/Alom/Graphviz/BaseInstruction.php';
require_once 'graphviz/src/Alom/Graphviz/Node.php';
require_once 'graphviz/src/Alom/Graphviz/Edge.php';
require_once 'graphviz/src/Alom/Graphviz/DirectedEdge.php';
require_once 'graphviz/src/Alom/Graphviz/AttributeBag.php';
require_once 'graphviz/src/Alom/Graphviz/Graph.php';
require_once 'graphviz/src/Alom/Graphviz/Digraph.php';
require_once 'graphviz/src/Alom/Graphviz/AttributeSet.php';
require_once 'graphviz/src/Alom/Graphviz/Subgraph.php';

//options
$options=options_gets();
$datetime = isset($options[0]['datetime']) ? $options[0]['datetime'] : '1';
$panzoom = isset($options[0]['panzoom']) ? $options[0]['panzoom'] : '1';

function dpp_load_incoming_routes() {
  global $db;
	
  $sql = "select * from incoming order by extension";
  $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from incoming");       
  }
	
	$routes = [];
  // Store the routes in a hash indexed by the inbound number
  if (is_array($results)) {
    foreach ($results as $route) {
      $num = $route['extension'];
      $cid = $route['cidnum'];
      $routes[$num.$cid] = $route;
    }
  }
	
	return $routes;
}

function dpp_find_route($routes, $num) {

  $match = array();
  $pattern = '/[^_xX+0-9\[\]]/';   # remove all non-digits
  $num =  preg_replace($pattern, '', $num);

  // "extension" is the key for the routes hash
  foreach ($routes as $ext => $route) {
    if ($ext == $num) {
      $match = $routes[$num];
    }
  }
  return $match;
}

$inroutes=dpp_load_incoming_routes();
$dproute= dpp_find_route($inroutes, $iroute);

	if (empty($dproute)) {
		//$header = "<div><h2>Error: Could not find inbound route for ".formatPhoneNumbers($ext)." / ".formatPhoneNumbers($cid)."</h2></div>";
		$header = "<div><h2>Error: Could not find inbound route for ".$ext." / ".$cid."</h2></div>";
		//$buttons = $gtext = '';
		//$gtext=json_encode($gtext);
	}else{
		$filename = ($ext == '') ? 'ANY.png' : $ext.'.png';
		dpp_load_tables($dproute);   # adds data for time conditions, IVRs, etc.
		if (!empty($jump)){
			dpp_follow_destinations($dproute, '', $jump ,$options); #starts with destination
		}else{
			dpp_follow_destinations($dproute, '', '',$options); #starts with empty destination
		}
		$gtext = $dproute['dpgraph']->render();
		//$gtext = str_replace(["\n","+"], ["\\n","\\+"], $gtext);
		//$gtext = str_replace(["\\", "\r\n", "\n", "\l"], ["\\\\", "\\n", "\\n", "\\l"], $gtext);
		$gtext = str_replace(["\n"], ["\\n"], $gtext);
		$gtext=json_encode($gtext);
		
		if (is_numeric($ext) && in_array(strlen($ext), [10, 11, 12])) {
			$number=formatPhoneNumbers($ext);
		}else{
			$number=$ext;
		}		
		
		$header='<h2>Dial Plan For Inbound Route: '.$number;
			
		if (!empty($cid)){$header.=' / '.formatPhoneNumbers($cid);} 
		$header.=': '.$dproute['description'].'</h2>';
		if ($datetime==1){$header.= "<h6>".date('Y-m-d H:i:s')."</h6>";}
		
		$buttons='
				<div class="btn-toolbar" style="margin: 10px 0; padding: 10px 0;">
						<div class="btn-group">
								
								<button type="button" class="btn btn-default" id="reloadButton" onclick="generateVisualization(\''.$ext.'\',\''.$cid.'\',\''.$jump.'\',\''.$panzoom.'\')">Reload</button>
								<button type="button" class="btn btn-default" style="pointer-events: none; cursor: default;">Export as '.htmlspecialchars($filename).'</button>
								<button type="button" class="btn btn-default" onclick="exportImage(4, \''.htmlspecialchars($filename).'\')">High</button>
								<button type="button" class="btn btn-default" onclick="exportImage(2, \''. htmlspecialchars($filename).'\')">Standard</button>
								<button type="button" id="focus" class="btn btn-default">Highlight Paths</button>
						</div>
				</div>
				<input type="hidden" id="processed" value="yes">
				<input type="hidden" id="ext" value="'.$ext.'">
				<input type="hidden" id="cid" value="'.$cid.'">
				<input type="hidden" id="jump" value="'.$jump.'">
				<input type="hidden" id="panzoom" value="'.$panzoom.'">
				';
			$header.=' 
				<script>
				$(document).ready(function() {
					document.querySelectorAll(\'g.node\').forEach(node => {
						node.addEventListener(\'click\', function(e) {
							if (e.ctrlKey || e.metaKey) {  // Support Ctrl on Windows/Linux, Command on Mac
								e.preventDefault();

								let titleElement = node.querySelector(\'title\');
								
								if (titleElement) {
									let titleText = titleElement.textContent || titleElement.innerText;
									generateVisualization(\''.$ext.'\',\''.$cid.'\',titleText,\''.$panzoom.'\');
								}
							}
						});
					});
				});
				</script>
				<script src="modules/dpviz/assets/js/focus.js"></script>
				<script>
					function exportImage(scale, filename) {
						html2canvas(document.querySelector(\'#vizContainer\'), {
								scale: scale,
								useCORS: true,
								allowTaint: true
						}).then(function(canvas) {
								let imgData = canvas.toDataURL("image/png");
								saveAs(imgData, filename);
						});
					}

					function saveAs(uri, filename) {
							var link = document.createElement(\'a\');
							if (typeof link.download === \'string\') {
									link.href = uri;
									link.download = filename;
									document.body.appendChild(link);
									link.click();
									document.body.removeChild(link);
							} else {
									window.open(uri);
							}
					}
				</script>';

	}




#
# This is a recursive function.  It digs through various nodes
# (ring groups, ivrs, time conditions, extensions, etc.) to find
# the path a call takes.  It creates a graph of the path through
# the dial plan, stored in the $route object.
#
#
function dpp_follow_destinations (&$route, $destination, $optional, $options) {

$horizontal = isset($options[0]['horizontal']) ? $options[0]['horizontal'] : '0';
$direction=($horizontal== 1) ? 'LR' : 'TB';
$dynmembers= isset($options[0]['dynmembers']) ? $options[0]['dynmembers'] : '0';
$combineQueueRing= isset($options[0]['combineQueueRing']) ? $options[0]['combineQueueRing'] : '0';
$extOptional= isset($options[0]['extOptional']) ? $options[0]['extOptional'] : '0';
	

  $pastels = [
    "#7979FF", "#86BCFF", "#8ADCFF", "#3DE4FC", "#5FFEF7", "#33FDC0",
    "#ed9581", "#81a6a2", "#bae1e7", "#eb94e2", "#f8d580", "#979291",
    "#92b8ef", "#ad8086", "#F7A8A8", "#C5A3FF", "#FFC3A0", "#FFD6E0",
    "#FFB3DE", "#D4A5A5", "#A5D4D4", "#F5C6EC", "#B5EAD7", "#C7CEEA",
    "#E0BBE4", "#FFDFD3", "#FEC8D8", "#D1E8E2", "#E8D1E1", "#EAD5DC",
    "#F9E79F", "#D6EAF8"
];

$neons = [
    "#fe0000", "#fdfe02", "#0bff01", "#011efe", "#fe00f6",
    "#ff5f1f", "#ff007f", "#39ff14", "#ff073a", "#ffae00",
    "#08f7fe", "#ff44cc", "#ff6ec7", "#dfff00", "#32cd32",
    "#ccff00", "#ff1493", "#00ffff", "#ff00ff", "#ff4500",
    "#ff00aa", "#ff4c4c", "#7df9ff", "#adff2f", "#ff6347",
    "#ff66ff", "#f2003c", "#ffcc00", "#ff69b4", "#0aff02"
];
	
	$optional = preg_match('/^[_xX+\d\[\]]+$/', $optional) ? '' : $optional;
  if (! isset ($route['dpgraph'])) {
    $route['dpgraph'] = new Alom\Graphviz\Digraph('"'.$route['extension'].'"');
		$route['dpgraph']->attr('graph',array('rankdir'=>$direction));
  }
  $dpgraph = $route['dpgraph'];
  dpplog(9, "destination='$destination' route[extension]: " . print_r($route['extension'], true));

  # This only happens on the first call.  Every recursive call includes
  # a destination to look at.  For the first one, we get the destination from
  # the route object.
  if ($destination == '') {
		if (empty($route['extension'])){
			$didLabel='ANY';
		}elseif (is_numeric($route['extension']) && (strlen($route['extension'])==10 || strlen($route['extension'])==11)){
			$didLabel=formatPhoneNumbers($route['extension']);
		}else{
			$didLabel=$route['extension'];
		}
		$didLink=$route['extension'].'/';
		if (!empty($route['cidnum'])){
			$didLabel.=' / '.formatPhoneNumbers($route['cidnum']);
			$didLink.=$route['cidnum'];
		}
		//$didLabel.="\\n".$route['description'];
		$didData=$route['incoming'][$route['extension']];
		$didTooltip=$didData['extension']."\n";
		$didTooltip.= !empty($didData['cidnum']) ? "Caller ID Number= " . $didData['cidnum']."\n" : "";
		$didTooltip.= !empty($didData['description']) ? "Description= " . $didData['description']."\n" : "";
		$didTooltip.= !empty($didData['alertinfo']) ? "Alert Info= " . $didData['alertinfo']."\n" : "";
		$didTooltip.= !empty($didData['grppre']) ? "CID Prefix= " . $didData['grppre']."\n" : "";
		$didTooltip.= !empty($didData['mohclass']) ? "MOH Class= " . $didData['mohclass']."\n" : "";

		$dpgraph->node($route['extension'],
			array(
				'label' => sanitizeLabels($didLabel)."\n".$route['description'],
				'tooltip' => sanitizeLabels($didTooltip),
				'width' => 2,
				'margin' => '.13',
				'shape' => 'cds',
				'style' => 'filled',
				'URL'   => htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)),
				'target'=>'_blank',
				'fillcolor' => 'darkseagreen')
			);
    // $graph->node() returns the graph, not the node, so we always
    // have to get() the node after adding to the graph if we want
    // to save it for something.
    // UPDATE: beginNode() creates a node and returns it instead of
    // returning the graph.  Similarly for edge() and beginEdge().
    $route['parent_node'] = $dpgraph->get($route['extension']);
    
		


    # One of thse should work to set the root node, but neither does.
    # See: https://rt.cpan.org/Public/Bug/Display.html?id=101437
    #$route->{parent_node}->set_attribute('root', 'true');
    #$dpgraph->set_attribute('root' => $route->{extension});
		
    // If an inbound route has no destination, we want to bail, otherwise recurse.
    if ($optional != '') {
			$route['parent_edge_label'] = ' ';
      dpp_follow_destinations($route, $optional,'',$options);
    }elseif ($route['destination'] != '') {
			$route['parent_edge_label'] = ' Always';
      dpp_follow_destinations($route, $route['destination'],'',$options);
    }
    return;
  }
	
  dpplog(9, "Inspecting destination $destination");

  // We use get() to see if the node exists before creating it.  get() throws
  // an exception if the node does not exist so we have to catch it.
  try {
    $node = $dpgraph->get($destination);
  } catch (Exception $e) {
    dpplog(7, "Adding node: $destination");
    $node = $dpgraph->beginNode($destination);
		$node->attribute('margin', '.25,.055');
  }
 
  // Add an edge from our parent to this node, if there is not already one.
  // We do this even if the node already existed because this node might
  // have several paths to reach it.
  $ptxt = $route['parent_node']->getAttribute('label', '');
  $ntxt = $node->getAttribute('label', '');
  dpplog(9, "Found it: ntxt = $ntxt");
  if ($ntxt == '' ) { $ntxt = "(new node: $destination)"; }
  if ($dpgraph->hasEdge(array($route['parent_node'], $node))) {
		
    dpplog(9, "NOT making an edge from $ptxt -> $ntxt");
		$edge= $dpgraph->beginEdge(array($route['parent_node'], $node));
		$edge->attribute('label', sanitizeLabels($route['parent_edge_label']));
		$edge->attribute('labeltooltip',sanitizeLabels($ptxt));
		$edge->attribute('edgetooltip',sanitizeLabels($ptxt));
		
  } else {
    dpplog(9, "Making an edge from $ptxt -> $ntxt");
    $edge= $dpgraph->beginEdge(array($route['parent_node'], $node));
    $edge->attribute('label', sanitizeLabels($route['parent_edge_label']));
		$edge->attribute('labeltooltip',sanitizeLabels($ptxt));
		$edge->attribute('edgetooltip',sanitizeLabels($ptxt));
		
		if (preg_match("/^( Match| No Match)/", $route['parent_edge_label'])) {
			$edge->attribute('URL', $route['parent_edge_url']);
			$edge->attribute('target', $route['parent_edge_target']);
			$edge->attribute('labeltooltip',sanitizeLabels($route['parent_edge_labeltooltip']));
			$edge->attribute('edgetooltip',sanitizeLabels($route['parent_edge_labeltooltip']));
		}
		if (preg_match("/^( IVR)./", $route['parent_edge_label'])){
			$edge->attribute('style', 'dashed');
		}
		
		//start from node
		if (preg_match("/^ +$/", $route['parent_edge_label'])){
			$edge->attribute('style', 'dotted');
		}
		
		
  }

  dpplog(9, "The Graph: " . print_r($dpgraph, true));

  // Now bail if we have already recursed on this destination before.
  if ($node->getAttribute('label', 'NONE') != 'NONE') {
    return;
  }

	# Now look at the destination and figure out where to dig deeper.

		#
		# Announcements
		#
  if (preg_match("/^app-announcement-(\d+),s,(\d+)/", $destination, $matches)) {
		$annum = $matches[1];
		$another = $matches[2];

		$an = $route['announcements'][$annum];
		$recID=$an['recording_id'];
		
		$announcement = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
		#feature code exist?
		if ( isset($route['featurecodes']['*29'.$recID]) ){
			#custom feature code?
			if ($route['featurecodes']['*29'.$an['recording_id']]['customcode']!=''){$featurenum=$route['featurecodes']['*29'.$an['recording_id']]['customcode'];}else{$featurenum=$route['featurecodes']['*29'.$an['recording_id']]['defaultcode'];}
			#is it enabled?
			if ( ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ){$rec="Record(yes): ".$featurenum;}else{$rec="Record(no): ".$featurenum;}
		}else{
			$rec="Record(no): disabled";
		}
		
		$node->attribute('label', "Announcements: ".sanitizeLabels($an['description'])."\nRecording: ".sanitizeLabels($announcement)."\n".$rec);
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=announcement&view=form&extdisplay='.$annum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'note');
		$node->attribute('fillcolor', 'oldlace');
		$node->attribute('style', 'filled');

		# The destinations we need to follow are the no-answer destination
		# (postdest) and the members of the group.

		if ($an['post_dest'] != '') {
			$route['parent_edge_label'] = ' Continue';
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $an['post_dest'],'',$options);
		}
		# end of announcements

		#
		# Blackhole
		#
  } elseif (preg_match("/^app-blackhole,(hangup|congestion|busy|zapateller|musiconhold|ring|no-service),(\d+)/", $destination, $matches)) {
		$blackholetype = str_replace('musiconhold','Music On Hold',$matches[1]);
		$blackholeother = $matches[2];
		$previousURL=$route['parent_node']->getAttribute('URL', '');

		$node->attribute('label', 'Terminate Call: '.ucwords($blackholetype,'-'));
		$node->attribute('tooltip', 'Terminate Call: '.ucwords($blackholetype,'-'));
		$node->attribute('URL', $previousURL);
    $node->attribute('target', '_blank');
		$node->attribute('shape', 'invhouse');
		$node->attribute('fillcolor', 'orangered');
		$node->attribute('style', 'filled');
		
		#end of Blackhole

		#
		# Call Flow Control (daynight)
		#
  } elseif (preg_match("/^app-daynight,(\d+),(\d+)/", $destination, $matches)) {
    $daynightnum = $matches[1];
    $daynightother = $matches[2];
    $daynight = $route['daynight'][$daynightnum];
    
    #feature code exist?
    if ( isset($route['featurecodes']['*28'.$daynightnum]) ){
      #custom feature code?
      if ($route['featurecodes']['*28'.$daynightnum]['customcode']!=''){$featurenum=$route['featurecodes']['*28'.$daynightnum]['customcode'];}else{$featurenum=$route['featurecodes']['*28'.$daynightnum]['defaultcode'];}
      #is it enabled?
      if ($route['featurecodes']['*28'.$daynightnum]['enabled']=='1'){$code="\nToggle (enabled): ".$featurenum;}else{$code="\nToggle (disabled): ".$featurenum;}
    }else{
      $code='';
    }
	  
    #check current status and set path to active
    $C ='/usr/sbin/asterisk -rx "database show DAYNIGHT/C'.$daynightnum.'" | cut -d \':\' -f2 | tr -d \' \' | head -1';
    exec($C, $current_daynight);
    $dactive = $nactive = "";
    if ($current_daynight[0]=='DAY'){$dactive="(Active)";}else{$nactive="(Active)";}

    foreach ($daynight as $d){
      if ($d['dmode']=='day'){
				 $route['parent_edge_label'] = ' Day Mode '.$dactive;
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $d['dest'],'',$options);
      }elseif ($d['dmode']=='night'){
          $route['parent_edge_label'] = ' Night Mode '.$nactive;
          $route['parent_node'] = $node;
          dpp_follow_destinations($route, $d['dest'],'',$options);
      }elseif ($d['dmode']=="fc_description"){
           $node->attribute('label', "Call Flow: ".sanitizeLabels($d['dest']) .$code);
      }
    }
    $daynight = $route['daynight'][$daynightnum];
    $node->attribute('URL', htmlentities('/admin/config.php?display=daynight&view=form&itemid='.$daynightnum.'&extdisplay='.$daynightnum));
    $node->attribute('target', '_blank');
    $node->attribute('fillcolor', $pastels[14]);
    $node->attribute('style', 'filled');
		#end of Call Flow Control (daynight)

		#
		# Conferences (meetme)
		#
  } elseif (preg_match("/^ext-meetme,(\d+),(\d+)/", $destination, $matches)) {
		$meetmenum = $matches[1];
		$meetmeother = $matches[2];
		$meetme = $route['meetme'][$meetmenum];

		$node->attribute('label', 'Conferences: '.$meetme['exten'].' '.sanitizeLabels($meetme['description']));
		$node->attribute('URL', htmlentities('/admin/config.php?display=conferences&view=form&extdisplay='.$meetmenum));
		$node->attribute('target', '_blank');
		$node->attribute('fillcolor', 'burlywood');
		$node->attribute('style', 'filled');
		#end of Conferences (meetme)

		#
		# Directory
		#
  } elseif (preg_match("/^directory,(\d+),(\d+)/", $destination, $matches)) {
		$directorynum = $matches[1];
		$directoryother = $matches[2];
		$directory = $route['directory'][$directorynum];

		$node->attribute('label', 'Directory: '.sanitizeLabels($directory['dirname']));
		$node->attribute('URL', htmlentities('/admin/config.php?display=directory&view=form&id='.$directorynum));
		$node->attribute('target', '_blank');
		$node->attribute('fillcolor', $pastels[9]);
		$node->attribute('shape', 'folder');
		$node->attribute('style', 'filled');
		
		if ($directory['invalid_destination']!=''){
			 $route['parent_edge_label']= ' Invalid Input';
			 $route['parent_node'] = $node;
			 dpp_follow_destinations($route, $directory['invalid_destination'],'',$options);
		}
		#end of Directory

		#
		# DISA
		#
  } elseif (preg_match("/^disa,(\d+),(\d+)/", $destination, $matches)) {
		$disanum = $matches[1];
		$disaother = $matches[2];
		$disa = $route['disa'][$disanum];

		$node->attribute('label', 'DISA: '.sanitizeLabels($disa['displayname']));
		$node->attribute('URL', htmlentities('/admin/config.php?display=disa&view=form&itemid='.$disanum));
		$node->attribute('target', '_blank');
		$node->attribute('fillcolor', $pastels[10]);
		$node->attribute('style', 'filled');
		#end of DISA

		#
		# Dynamic Routes
		#
  } elseif (preg_match("/^dynroute-(\d+)/", $destination, $matches)) {
		$dynnum = $matches[1];
		$dynrt = $route['dynroute'][$dynnum];
		
		$recID=$dynrt['announcement_id'];
		
		$announcement = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
		$node->attribute('label', "DYN: ".sanitizeLabels($dynrt['name'])."\nAnnouncement: ".sanitizeLabels($announcement));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=dynroute&action=edit&id='.$dynnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'component');
		$node->attribute('fillcolor', $pastels[12]);
		$node->attribute('style', 'filled');

		if (!empty($dynrt['routes'])){
			ksort($dynrt['routes']);
			foreach ($dynrt['routes'] as $selid => $ent) {
				
				$route['parent_edge_label']= "  Match: ".sanitizeLabels($ent['selection'])."\n".sanitizeLabels($ent['description']);
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $ent['dest'],'',$options);
			}
		}
		
		//are the invalid and timeout destinations the same?
		if ($dynrt['invalid_dest']==$dynrt['default_dest']){
			 $route['parent_edge_label']= ' Invalid Input, Default ('.$dynrt['timeout'].' secs)';
			 $route['parent_node'] = $node;
			 dpp_follow_destinations($route, $dynrt['invalid_dest'],'',$options);
		}else{
			if ($dynrt['invalid_dest'] != '') {
				$route['parent_edge_label']= ' Invalid Input';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $dynrt['invalid_dest'],'',$options);
			}
			if ($dynrt['default_dest'] != '') {
				$route['parent_edge_label']= ' Default ('.$dynrt['timeout'].' secs)';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $dynrt['default_dest'],'',$options);
			}
		}
		#end of Dynamic Routes

		#
		# Extension (from-did-direct)
		#
  } elseif (preg_match("/^from-did-direct,(\d+),(\d+)/", $destination, $matches)) {
		
		$extnum = $matches[1];
		$extother = $matches[2];
		if (isset($route['extensions'][$extnum])){
			$extension = $route['extensions'][$extnum];
			$extname= $extension['name'];
			$extemail= $extension['email'];
			$extemail= str_replace("|",",\n",$extemail);
			$node->attribute('label', 'Extension: '.$extnum.' '.sanitizeLabels($extname)."\n".sanitizeLabels($extemail));
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$extnum));
			$node->attribute('target', '_blank');
		}else{
			$node->attribute('label', $extnum);
		}
		
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', $pastels[15]);
		$node->attribute('style', 'filled');
		
		//Optional Destinations
		if ($extOptional && (!empty($extension['noanswer_dest']) || !empty($extension['busy_dest']) || !empty($extension['chanunavail_dest'])) ) {
			if (
					$extension['noanswer_dest'] === $extension['busy_dest'] &&
					$extension['noanswer_dest'] === $extension['chanunavail_dest']
			) {
					// All three are equal
					$route['parent_edge_label'] = ' No Answer, Busy, Not Reachable';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'],'',$options);
			} elseif (
					$extension['noanswer_dest'] === $extension['busy_dest']
					&& $extension['chanunavail_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Busy are the same, but Not Reachable is different
					$route['parent_edge_label'] = ' No Answer & Busy';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'],'',$options);
				}
					//Not Reachable
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = ' Not Reachable';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'],'',$options);
					}
			} elseif (
					$extension['noanswer_dest'] === $extension['chanunavail_dest']
					&& $extension['busy_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Not Reachable are the same
					$route['parent_edge_label'] = ' No Answer & Not Reachable';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'],'',$options);
				}
					//Busy
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = ' Busy';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'],'',$options);
					}
			} elseif (
					$extension['busy_dest'] === $extension['chanunavail_dest']
					&& $extension['noanswer_dest'] !== $extension['busy_dest']
			) {
				if (!empty($extension['busy_dest'])) {
					// Busy and Not Reachable are the same
					$route['parent_edge_label'] = ' Busy & Not Reachable';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['busy_dest'],'',$options);
				}
					//No Answer
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = ' No Answer';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'],'',$options);
					}
			} else {
					// All are different
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = ' No Answer';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'],'',$options);
					}
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = ' Busy';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'],'',$options);
					}
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = ' Not Reachable';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'],'',$options);
					}
			}
		}
		#end of Extension (from-did-direct)

		#
		# Feature Codes
		#
  } elseif (preg_match("/^ext-featurecodes,(\*?\d+),(\d+)/", $destination, $matches)) {
		$featurenum = $matches[1];
		$featureother = $matches[2];
		$feature = $route['featurecodes'][$featurenum];
		
		if ($feature['customcode']!=''){$featurenum=$feature['customcode'];}
		$node->attribute('label', "Feature Code: ".sanitizeLabels($feature['description'])." <".$featurenum.">");
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=featurecodeadmin'));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'folder');
		$node->attribute('fillcolor', 'gainsboro');
		$node->attribute('style', 'filled');
		#end of Feature Codes

		#
		# Inbound Routes
		#
  } elseif (preg_match("/^from-trunk,([^,]*),(\d+)/", $destination, $matches)) {
		
		$num = $matches[1];
		$numother = $matches[2];

		$incoming = $route['incoming'][$num];
		
		$didLabel = ($num == "") ? "ANY" : formatPhoneNumbers($num);
		$didLabel.= "\n".$incoming['description'];
		$didLink=$num.'/';
		
		$node->attribute('label', sanitizeLabels($didLabel));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'cds');
		$node->attribute('fillcolor', 'darkseagreen');
		$node->attribute('style', 'filled');
		
		$route['parent_edge_label']= ' Continue';
		$route['parent_node'] = $node;
		dpp_follow_destinations($route, $incoming['destination'],'',$options);

		#end of Inbound Routes

		#
		# IVRs
		#
  } elseif (preg_match("/^ivr-(\d+),([a-z]+),(\d+)/", $destination, $matches)) {
    $inum = $matches[1];
    $iflag = $matches[2];
    $iother = $matches[3];

    $ivr = $route['ivrs'][$inum];
		$recID= $ivr['announcement'];
		$ivrRecName = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
		
    #feature code exist?
    if ( isset($route['featurecodes']['*29'.$ivr['announcement']]) ){
      #custom feature code?
      if ($route['featurecodes']['*29'.$ivr['announcement']]['customcode']!=''){$featurenum=$route['featurecodes']['*29'.$ivr['announcement']]['customcode'];}else{$featurenum=$route['featurecodes']['*29'.$ivr['announcement']]['defaultcode'];}
      #is it enabled?
      if ( ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ){$rec='(yes): '.$featurenum;}else{$rec='(no): '.$featurenum;}
    }else{
      $rec='(no): disabled';
    }

    $node->attribute('label', "IVR: ".sanitizeLabels($ivr['name'])."\nAnnouncement: ".sanitizeLabels($ivrRecName)."\nRecord ".$rec."\n");
		$node->attribute('tooltip', $node->getAttribute('label'));
    $node->attribute('URL', htmlentities('/admin/config.php?display=ivr&action=edit&id='.$inum));
    $node->attribute('target', '_blank');
    $node->attribute('shape', 'component');
    $node->attribute('fillcolor', 'gold');
    $node->attribute('style', 'filled');

    # The destinations we need to follow are the invalid_destination,
    # timeout_destination, and the selection targets
	
		
		#now go through the selections
		if (!empty($ivr['entries'])){
			ksort($ivr['entries']);
			foreach ($ivr['entries'] as $selid => $ent) {
				
				$route['parent_edge_label']= ' Selection '.sanitizeLabels($ent['selection']);
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $ent['dest'],'',$options);
			}
		}
		
		#are the invalid and timeout destinations the same?
		if ($ivr['invalid_destination']==$ivr['timeout_destination']){
			if (!empty($ivr['invalid_destination'])){
			 $route['parent_edge_label']= ' Invalid Input, Timeout ('.$ivr['timeout_time'].' secs)';
			 $route['parent_node'] = $node;
			 dpp_follow_destinations($route, $ivr['invalid_destination'],'',$options);
			}
		}else{
				if ($ivr['invalid_destination'] != '') {
					$route['parent_edge_label']= ' Invalid Input';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ivr['invalid_destination'],'',$options);
				}
				if ($ivr['timeout_destination'] != '') {
					$route['parent_edge_label']= ' Timeout ('.$ivr['timeout_time'].' secs)';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ivr['timeout_destination'],'',$options);
				}
		}
		
		
		# end of IVRs

		#
		# Languages
		#
  } elseif (preg_match("/^app-languages,(\d+),(\d+)/", $destination, $matches)) {
		$langnum = $matches[1];
		$langother = $matches[2];

		$lang = $route['languages'][$langnum];
		$node->attribute('label', 'Languages: '.sanitizeLabels($lang['description']));
		$node->attribute('URL', htmlentities('/admin/config.php?display=languages&view=form&extdisplay='.$langnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'note');
		$node->attribute('fillcolor', $pastels[6]);
		$node->attribute('style', 'filled');

		if ($lang['dest'] != '') {
			$route['parent_edge_label'] = ' Continue';
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $lang['dest'],'',$options);
		}
		#end of Languages

		#
		# MISC Destinations
		#
  } elseif (preg_match("/^ext-miscdests,(\d+),(\d+)/", $destination, $matches)) {
		$miscdestnum = $matches[1];
		$miscdestother = $matches[2];

		$miscdest = $route['miscdest'][$miscdestnum];
		$node->attribute('label', 'Misc Dest: '.sanitizeLabels($miscdest['description']).' ('.$miscdest['destdial'].')');
		$node->attribute('URL', htmlentities('/admin/config.php?display=miscdests&view=form&extdisplay='.$miscdestnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'rpromoter');
		$node->attribute('fillcolor', 'coral');
		$node->attribute('style', 'filled');
		#end of MISC Destinations

		#
		# Play Recording
		#
  } elseif (preg_match("/^play-system-recording,(\d+),(\d+)/", $destination, $matches)) {
		$recID = $matches[1];
		$recIDOther = $matches[2];
		$playName = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
		$node->attribute('label', 'Play Recording: '.sanitizeLabels($playName));
		$node->attribute('URL', htmlentities('/admin/config.php?display=recordings&action=edit&id='.$recID));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', $pastels[16]);
		$node->attribute('style', 'filled');
		#end of Play Recording

		# Queue Priorities
		#
  }elseif (preg_match("/^app-queueprio,(\d+),(\d+)/", $destination, $matches)) {
		$queueprioID = $matches[1];
		$queueprioIDOther = $matches[2];
		$queueprio = $route['queueprio'][$queueprioID];
		$queueprioLabel=$queueprio['description']."\nPriority: ".$queueprio['queue_priority'];
		
		$node->attribute('label', 'Queue Priorities: '.sanitizeLabels($queueprioLabel));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=queueprio&view=form&extdisplay='.$queueprioID));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', $pastels[16]);
		$node->attribute('style', 'filled');
		if ($queueprio['dest'] != '') {
			$route['parent_edge_label'] = ' Continue';
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $queueprio['dest'], '',$options);
		}
		#end of Queue Priorities
		
		#
		# Queues
		#
  } elseif (preg_match("/^ext-queues,(\d+),(\d+)/", $destination, $matches)) {
    $qnum = $matches[1];
    $qother = $matches[2];

    $q = $route['queues'][$qnum];
    if ($q['maxwait'] == 0 || $q['maxwait'] == '' || !is_numeric($q['maxwait'])) {
			$maxwait = 'Unlimited';
    } else {
  	$maxwait = secondsToTimes($q['maxwait']);
    }
    $node->attribute('label', 'Queue '.$qnum.': '.sanitizeLabels($q['descr']));
    $node->attribute('URL', htmlentities('/admin/config.php?display=queues&view=form&extdisplay='.$qnum));
    $node->attribute('target', '_blank');
    $node->attribute('shape', 'hexagon');
    $node->attribute('fillcolor', 'mediumaquamarine');
    $node->attribute('style', 'filled');
		
		if (!empty($q['members'])){
			foreach ($q['members'] as $types=>$type) {
				foreach ($type as $member){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = ($types == 'static') ? ' Static' : ' Dynamic';
					switch ($combineQueueRing) {
						case "2":
								$go="from-did-direct,$member,1";
								break;
						default:
								$go="qmember$member";
					}
					dpp_follow_destinations($route, $go,'',$options);
					//dpp_follow_destinations($route, 'qmember'.$members,'',$options);
				}
			}
		}
		
		# The destinations we need to follow are the queue members (extensions)
    # and the no-answer destination.
    if ($q['dest'] != '') {
      $route['parent_edge_label'] = ' No Answer ('.$maxwait.')';
      $route['parent_node'] = $node;
      dpp_follow_destinations($route, $q['dest'],'',$options);
    }
		
		if (is_numeric($q['ivr_id'])){
      $route['parent_edge_label'] = ' IVR Break Out (every '.secondsToTimes($q['data']['min-announce-frequency']).')';
      $route['parent_node'] = $node;
      dpp_follow_destinations($route, 'ivr-'.$q['ivr_id'].',s,1','',$options);
    }
		#end of Queues
		
		#
		# Queue members (static and dynamic)
		#
	} elseif (preg_match("/^qmember(\d+)/", $destination, $matches)) {
		$qextension=$matches[1];
		$qlabel = isset($route['extensions'][$qextension]['name']) ? "Ext ".$qextension."\n".$route['extensions'][$qextension]['name'] : $qextension;
		
		$node->attribute('label', sanitizeLabels($qlabel));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('style', 'filled');
		if (!is_numeric($qlabel)){
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$qextension));
			$node->attribute('target', '_blank');
		}
		
		if ($route['parent_edge_label'] == ' Static') {
			$node->attribute('fillcolor', $pastels[20]);
		}else{
			$node->attribute('fillcolor', $pastels[8]);
		}
		#end of Queue members (static and dynamic)

		#
		# Ring Groups
		#
  } elseif (preg_match("/^ext-group,(\d+)/", $destination, $matches)) {
    $rgnum = $matches[1];

    $rg = $route['ringgroups'][$rgnum];
    $node->attribute('label', 'Ring Groups: '.$rgnum.' '.sanitizeLabels($rg['description']));
    $node->attribute('URL', htmlentities('/admin/config.php?display=ringgroups&view=form&extdisplay='.$rgnum));
    $node->attribute('target', '_blank');
    $node->attribute('fillcolor', $pastels[12]);
    $node->attribute('style', 'filled');
		$grplist=str_replace('#', '', $rg['grplist']);
		$grplist = preg_split("/-/", $grplist);
    
    foreach ($grplist as $member) {
      $route['parent_node'] = $node;
			$route['parent_edge_label'] = '';
			switch ($combineQueueRing) {
					case "1":
							$go="qmember$member";
							break;
					case "2":
							$go="from-did-direct,$member,1";
							break;
					default:
							$go="rg$member";
			}
			dpp_follow_destinations($route,$go, '',$options);
			//dpp_follow_destinations($route, $combineQueueRing ? "qmember$member" : "rg$member", '',$options);
    } 
		
		# The destinations we need to follow are the no-answer destination
    # (postdest) and the members of the group.
    if ($rg['postdest'] != '') {
      $route['parent_edge_label'] = ' No Answer ('.secondsToTimes($rg['grptime']).')';
      $route['parent_node'] = $node;
      dpp_follow_destinations($route, $rg['postdest'],'',$options);
    }
    # End of Ring Groups
  
		#
		# Ring Group Members
		#
  } elseif (preg_match("/^rg(\d+)/", $destination, $matches)) {
		$rgext = $matches[1];
		$rglabel = isset($route['extensions'][$rgext]) ? "Ext ".$rgext."\n".$route['extensions'][$rgext]['name'] : $rgext;

		$node->attribute('label', sanitizeLabels($rglabel));
		$node->attribute('tooltip', $node->getAttribute('label'));
		if (!is_numeric($rglabel)){
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$rgext));
			$node->attribute('target', '_blank');
		}
		$node->attribute('fillcolor', $pastels[2]);
		$node->attribute('style', 'filled');
		# end of ring group members

		#
		# Set CID
		#
  } elseif (preg_match("/^app-setcid,(\d+),(\d+)/", $destination, $matches)) {
		$cidnum = $matches[1];
		$cidother = $matches[2];
		$cid = $route['setcid'][$cidnum];
		$cidLabel= "Set CID\nName= ".preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name'])."\nNumber= ".preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']);

		$node->attribute('label', sanitizeLabels($cidLabel));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=setcid&view=form&id='.$cidnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'note');
		$node->attribute('fillcolor', $pastels[6]);
		$node->attribute('style', 'filled');

		if ($cid['dest'] != '') {
			$route['parent_edge_label'] = ' Continue';
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $cid['dest'],'',$options);
		}
		#end of Set CID
		
		#
		# TTS
		#
  } elseif (preg_match("/^ext-tts,(\d+),(\d+)/", $destination, $matches)) {
		$ttsnum = $matches[1];
		$ttsother = $matches[2];
		$tts = $route['tts'][$ttsnum];
		$ttsLabel= "TTS: ".$tts['name'];
		$ttsTooltip = "Engine: ".$tts['engine']."\nDesc: ".$tts['text'];
		
		$node->attribute('label', sanitizeLabels($ttsLabel));
		$node->attribute('tooltip', sanitizeLabels($ttsTooltip));
		$node->attribute('URL', htmlentities('/admin/config.php?display=tts&view=form&id='.$ttsnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'note');
		$node->attribute('fillcolor', $pastels[6]);
		$node->attribute('style', 'filled');

		if ($tts['goto'] != '') {
			$route['parent_edge_label'] = ' Continue';
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $tts['goto'],'',$options);
		}
		#end of TTS
		
		#
		# Time Conditions
		#
  } elseif (preg_match("/^timeconditions,(\d+),(\d+)/", $destination, $matches)) {
    $tcnum = $matches[1];
    $tcother = $matches[2];

    $tc = $route['timeconditions'][$tcnum];
		$tcTooltip=$tc['displayname']."\nMode= ".$tc['mode']."\n";
		if (!empty($tc['timezone'])){
			$tcTooltip.= ($tc['timezone'] !== 'default') ? "Timezone= " . $tc['timezone'] : '';
		}
    $node->attribute('label', "TC: ".sanitizeLabels($tc['displayname']));
    $node->attribute('tooltip', sanitizeLabels($tcTooltip));
		$node->attribute('URL', htmlentities('/admin/config.php?display=timeconditions&view=form&itemid='.$tcnum));
    $node->attribute('target', '_blank');
    $node->attribute('shape', 'invhouse');
    $node->attribute('fillcolor', 'dodgerblue');
    $node->attribute('style', 'filled');

    //TC modes
		if ($tc['mode'] === 'time-group') {
			$tg=$route['timegroups'][$tc['time']];
			$tgnum = $tg['id'];
			$tgname = $tg['description'];
			$tgtime = !empty($tg['time']) ? $tg['time'] : "No times defined";
			$tgLabel= $tgname."\n".$tgtime;
			$tgLink = '/admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum;
			$tgTooltip= $tgLabel;
		} elseif ($tc['mode'] === 'calendar-group') {
			if (!empty($route['calendar'][$tc['calendar_id']])){
				$cal= $route['calendar'][$tc['calendar_id']];
				$tgLabel=$cal['name'];
				$tgLink = '/admin/config.php?display=calendar&action=view&type=calendar&id='.$tc['calendar_id'];
				if (!empty($cal['timezone'])){
					$tz=$cal['timezone'];
				}else{
					$tz='';
				}
				$tgTooltip="Name= ".$cal['name']."\nDescription= ".$cal['description']."\nType= ".$cal['type']."\nTimezone= ".$tz;
				
			}elseif (!empty($route['calendar'][$tc['calendar_group_id']])){
				$cal= $route['calendar'][$tc['calendar_group_id']];
				$tgLabel=$cal['name'];
				$tgLink = '/admin/config.php?display=calendargroups&action=edit&id='.$tc['calendar_group_id'];
				$calNames='Calendars= ';
				if (!empty($cal['calendars'])){
					foreach ($cal['calendars'] as $c){
						$calNames.=$route['calendar'][$c]['name']."\n";
					}
				}
				
				$cats = !empty($cal['categories']) ? count($cal['categories']) : 'All';
				$categories='Categories= '.$cats;
				$eves = !empty($cal['events']) ? count($cal['events']) : 'All';
				$events='Events= '.$eves;
				$expand = $cal['expand'] ? 'true' : 'false';
				$tgTooltip="Name= ".$cal['name']."\n".$calNames."\n".$categories."\n".$events."\nExpand= ".$expand;
			}
		}
		
		# Now set the current node to be the parent and recurse on both the true and false branches
    //$route['parent_edge_label'] = " Match:\nline 555\lLine2";
    $route['parent_edge_label'] = " Match:\n".sanitizeLabels($tgLabel);
    $route['parent_edge_url'] = htmlentities($tgLink);
		$route['parent_edge_target'] = '_blank';
		$route['parent_edge_labeltooltip']=" Match\n".sanitizeLabels($tgTooltip);

    $route['parent_node'] = $node;
	  dpp_follow_destinations($route, $tc['truegoto'],'',$options);

		$route['parent_edge_label'] = " No Match";
    $route['parent_edge_url'] = htmlentities($tgLink);
    $route['parent_edge_target'] = '_blank';
		$route['parent_edge_labeltooltip']=" No Match\n".sanitizeLabels($tgTooltip);
    $route['parent_node'] = $node;
		dpp_follow_destinations($route, $tc['falsegoto'],'',$options);
		#end of Time Conditions
 
		#
		# Voicemail
		#
  } elseif (preg_match("/^ext-local,vm([b,i,s,u])(\d+),(\d+)/", $destination, $matches)) {
		$vmtype= $matches[1];
		$vmnum = $matches[2];
		$vmother = $matches[3];
		
		$vm_array=array('b'=>'(Busy Message)','i'=>'(Instructions Only)','s'=>'(No Message)','u'=>'(Unavailable Message)' );
		$vmname= $route['extensions'][$vmnum]['name'];
		$vmemail= $route['extensions'][$vmnum]['email'];
		$vmemail= str_replace("|",",\n",$vmemail);
	 
		$node->attribute('label', "Voicemail: ".$vmnum." ".sanitizeLabels($vmname)." ".$vm_array[$vmtype]."\n".sanitizeLabels($vmemail));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$vmnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'folder');
		$node->attribute('fillcolor', $pastels[11]);
		$node->attribute('style', 'filled');
		#end of Voicemail
	
		#
		# VM Blast
		#
  } elseif (preg_match("/^vmblast\-grp,(\d+),(\d+)/", $destination, $matches)) {
		$vmblastnum = $matches[1];
		$vmblastother = $matches[2];
		$vmblast = $route['vmblasts'][$vmblastnum];
		
		$node->attribute('label', 'VM Blast: '.$vmblastnum.' '.sanitizeLabels($vmblast['description']));
		$node->attribute('URL', htmlentities('/admin/config.php?display=vmblast&view=form&extdisplay='.$vmblastnum));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'folder');
		$node->attribute('fillcolor', 'gainsboro');
		$node->attribute('style', 'filled');
		
		if (!empty($vmblast['members'])){
			foreach ($vmblast['members'] as $member) {
				$route['parent_edge_label']= '';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, 'vmblast-mem,'.$member,'',$options);
			}
		}
		#end of VM Blast
		
		#VM Blast members
	} elseif (preg_match("/^vmblast\-mem,(\d+)/", $destination, $matches)) {
		$member=$matches[1];
		$vmblastname=$route['extensions'][$member]['name'];
		$vmblastemail=$route['extensions'][$member]['email'];
		$vmblastemail= str_replace("|",",\n",$vmblastemail);
		$node->attribute('label', "Ext ".$member." ".sanitizeLabels($vmblastname)."\n".sanitizeLabels($vmblastemail));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$member));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', $pastels[16]);
		$node->attribute('style', 'filled');
	
		#preg_match not found
	}else {
		if (!empty($route['customapps'])){
			#custom destinations
			foreach ($route['customapps'] as $entry) {
				if ($entry['target'] === $destination) {
					$custDest=$entry;
					break;
				}
			}
			#end of Custom Destinations
		}
		
		if (!empty($custDest)){
			$custId=$entry['destid'];
			$custLabel="Cust Dest: ".$entry['description']."\nTarget: ".$entry['target'];
			$custNotes=$entry['notes'];
			
			$node->attribute('label', sanitizeLabels($custLabel));
			if (empty($custNotes)){
				$node->attribute('tooltip', $node->getAttribute('label'));
			}else{
				$node->attribute('tooltip', sanitizeLabels($entry['notes']));
			}
			$node->attribute('URL', htmlentities('/admin/config.php?display=customdests&view=form&destid='.$custId));
			$node->attribute('target', '_blank');
			$node->attribute('shape', 'component');
			$node->attribute('fillcolor', $pastels[27]);
			$node->attribute('style', 'filled');
		}else{
			dpplog(1, "Unknown destination type: $destination");
			$node->attribute('fillcolor', $pastels[12]);
			$node->attribute('label', sanitizeLabels($destination));
			$node->attribute('style', 'filled');
    }
  }
}

# load gobs of data.  Save it in hashrefs indexed by ints
function dpp_load_tables(&$dproute) {
	global $db;
	global $dynmembers;
  
	# Users
  $query = "select * from users";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from users");
  }
	
  foreach($results as $users) {
		$emailResult=array();
    $id = $users['extension'];
    $dproute['extensions'][$id]= $users;
		$email='grep -E \'^'.$id.'[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3';
		exec($email, $emailResult);
		$dproute['extensions'][$id]['email'] = !empty($emailResult[0]) ? $emailResult[0] : 'unassigned';
  }

	# Inbound Routes
  $query = "select * from incoming";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from incoming");
  }
  foreach($results as $incoming) {
    $id = $incoming['extension'];
    $dproute['incoming'][$id] = $incoming;
  }	
	
  # IVRs
  $query = "select * from ivr_details";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from ivr_details");       
  }
  foreach($results as $ivr) {
    $id = $ivr['id'];
    $dproute['ivrs'][$id] = $ivr;
  }

  # IVR entries 
  $query = "select * from ivr_entries";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from ivr_entries");       
  }
  foreach($results as $ent) {
    $id = $ent['ivr_id'];
    $selid = $ent['selection'];
    dpplog(9, "entry:  ivr=$id   selid=$selid");
    $dproute['ivrs'][$id]['entries'][$selid] = $ent;
  }

  # Recordings
  $query = "select * from recordings";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from featurecodes");
  }
  foreach($results as $recordings) {
		$id=$recordings['id'];
    $dproute['recordings'][$id] = $recordings;
		dpplog(9, "recordings=$id");
  }
	
	
	// Array of table names to check -not required
	$tables = ['announcement','daynight','directory_details','disa','dynroute','dynroute_dests','featurecodes','kvstore_FreePBX_modules_Calendar',
							'kvstore_FreePBX_modules_Customappsreg','languages','meetme','miscdests','queueprio','queues_config','queues_details',
							'ringgroups','setcid','timeconditions','timegroups_groups','timegroups_details','tts','vmblast','vmblast_groups'];
	
	foreach ($tables as $table) {
    // Check if the table exists
    $tableExists = $db->getOne("SHOW TABLES LIKE '$table'");
    
    if (!$tableExists) {
        // Skip to the next table if the current table does not exist
        continue;
    }

    $query = "SELECT * FROM $table";
    $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
    
    if (DB::IsError($results)) {
        // Log the error but continue to check the other tables
        dpplog(9, "Error selecting from $table: " . $results->getMessage());
        continue;  // Skip to the next table
    }

 		if ($table == 'announcement') {
				foreach($results as $an) {
					$id = $an['announcement_id'];
					$dproute['announcements'][$id] = $an;
					$dest = $an['post_dest'];
					dpplog(9, "announcement dest:  an=$id   dest=$dest");
					$dproute['announcements'][$id]['dest'] = $dest;
				}
		}elseif ($table == 'daynight') {
				foreach($results as $daynight) {
					$id = $daynight['ext'];
					$dproute['daynight'][$id][] = $daynight;
					dpplog(9, "daynight=$id");
				}
		}elseif ($table == 'directory_details') {
				foreach($results as $directory) {
					$id = $directory['id'];
					$dproute['directory'][$id] = $directory;
					dpplog(9, "directory=$id");
				}
		}elseif ($table == 'disa') {
				foreach($results as $disa) {
					$id = $disa['disa_id'];
					$dproute['disa'][$id] = $disa;
					dpplog(9, "disa=$id");
				}
		}elseif ($table == 'dynroute') {
        foreach ($results as $dynroute) {
            $id = $dynroute['id'];
            $dproute['dynroute'][$id] = $dynroute;
            dpplog(9, "dynroute=$id");
        }
    }elseif ($table == 'dynroute_dests') {
        foreach ($results as $dynroute_dests) {
            $id = $dynroute_dests['dynroute_id'];
            $selid = $dynroute_dests['selection'];
            dpplog(9, "dynroute_dests: dynroute=$id match=$selid");
            $dproute['dynroute'][$id]['routes'][$selid] = $dynroute_dests;
        }
    }elseif ($table == 'featurecodes') {
        foreach($results as $featurecodes) {
					$id=$featurecodes['defaultcode'];
					$dproute['featurecodes'][$id] = $featurecodes;
					dpplog(9, "featurecodes=$id");
				}
		}elseif ($table == 'kvstore_FreePBX_modules_Calendar') {
			foreach($results as $calendar) {
				if ($calendar['id']=='calendars'){
					$id=$calendar['key'];
					$val=json_decode($calendar['val'],true);
					$dproute['calendar'][$id] = $val;
					dpplog(9, "calendar=$id");
				}elseif ($calendar['id']=='groups'){
					$id=$calendar['key'];
					$val=json_decode($calendar['val'],true);
					$dproute['calendar'][$id] = $val;
					dpplog(9, "calendar=$id");
				}
			}
    }elseif ($table == 'kvstore_FreePBX_modules_Customappsreg') {
        foreach($results as $Customappsreg) {
					if (is_numeric($Customappsreg['key'])){
						$id=$Customappsreg['key'];
						$val=json_decode($Customappsreg['val'],true);
						$dproute['customapps'][$id] = $val;
						dpplog(9, "customapps=$id");
					}
				}
    }elseif ($table == 'languages') {
        foreach($results as $languages) {
					$id=$languages['language_id'];
					$dproute['languages'][$id] = $languages;
					dpplog(9, "languages=$id");
				}		
    }elseif ($table == 'meetme') {
        foreach($results as $meetme) {
					$id = $meetme['exten'];
					$dproute['meetme'][$id] = $meetme;
					dpplog(9, "meetme dest:  conf=$id");
				}
    }elseif ($table == 'miscdests') {
        foreach($results as $miscdest) {
					$id = $miscdest['id'];
					$dproute['miscdest'][$id] = $miscdest;
					dpplog(9, "miscdest dest: $id");
				}
		}elseif ($table == 'queues_config') {
        foreach($results as $q) {
					$id = $q['extension'];
					$dproute['queues'][$id] = $q;
					$dproute['queues'][$id]['members']['static']=array();
					$dproute['queues'][$id]['members']['dynamic']=array();
				}
		}elseif ($table == 'queueprio') {
        foreach($results as $queueprio) {
					$id = $queueprio['queueprio_id'];
					$dproute['queueprio'][$id] = $queueprio;
					dpplog(9, "queueprio dest: $id");
				}
		}elseif ($table == 'queues_details') {
        foreach($results as $qd) {
					$id = $qd['id'];
					if ($qd['keyword'] == 'member') {
						$member = $qd['data'];
						if (preg_match("/Local\/(\d+).*?,(\d+)/", $member, $matches)) {
							$enum = $matches[1];
							$pen= $matches[2];
							$dproute['queues'][$id]['members']['static'][]=$enum;
						}
					}else{
						$dproute['queues'][$id]['data'][$qd['keyword']]=$qd['data'];
					}
				}
				# Queue members (dynamic) //options
				if ($dynmembers && !empty($dproute['queues'])){
					foreach ($dproute['queues'] as $id=>$details){
						$dynmem=array();
						
						$D='/usr/sbin/asterisk -rx "database show QPENALTY '.$id.'" | grep \'/agents/\' | cut -d\'/\' -f5';
						exec($D, $dynmem);
						
						foreach ($dynmem as $enum){
							list($ext, $pen) = explode(':', $enum);
							$ext=trim($ext);
							$pen=trim($pen);
							$dproute['queues'][$id]['members']['dynamic'][]=$ext;
						}
					}
				}
    }elseif ($table == 'ringgroups') {
        foreach($results as $rg) {
					$id = $rg['grpnum'];
					$dproute['ringgroups'][$id] = $rg;
				}
    }elseif ($table == 'setcid') {
        foreach($results as $cid) {
					$id = $cid['cid_id'];
					$dproute['setcid'][$id] = $cid;
				}
		}elseif ($table == 'timeconditions') {
        foreach($results as $tc) {
					$id = $tc['timeconditions_id'];
					$dproute['timeconditions'][$id] = $tc;
				}
		}elseif ($table == 'timegroups_groups') {			
        foreach($results as $tg) {
					$id = $tg['id'];
					$dproute['timegroups'][$id] = $tg;
				}
		}elseif ($table == 'timegroups_details') {
        foreach($results as $tgd) {
					$id = $tgd['timegroupid'];
					if (!isset($dproute['timegroups'][$id])) {
						dpplog(1, "timegroups_details id found for unknown timegroup, id=$id");
					} else {
						if (!isset($dproute['timegroups'][$id]['time'])){$dproute['timegroups'][$id]['time']="";}
						$exploded=explode("|",$tgd['time']); 
						if ($exploded[0]!=="*"){$time=$exploded[0];}else{$time="";}
						if ($exploded[1]!=="*"){$dow=ucwords($exploded[1],"-").", ";}else{$dow="";}
						if ($exploded[2]!=="*"){$date=$exploded[2]." ";}else{$date="";}
						if ($exploded[3]!=="*"){$month=ucfirst($exploded[3])." ";}else{$month="";}

						$dproute['timegroups'][$id]['time'].=$dow . $month . $date . $time."\l";
					}
				}
    }elseif ($table == 'tts') {
        foreach($results as $tts) {
					$id = $tts['id'];
					$dproute['tts'][$id] = $tts;
				}
    }elseif ($table == 'vmblast') {
				foreach($results as $vmblasts) {
					$id = $vmblasts['grpnum'];
					dpplog(9, "vmblast:  vmblast=$id");
					$dproute['vmblasts'][$id] = $vmblasts;
				}
		}elseif ($table == 'vmblast_groups') {
					foreach($results as $vmblastsGrp) {
					$id = $vmblastsGrp['grpnum'];
					dpplog(9, "vmblast:  vmblast=$id");
					$dproute['vmblasts'][$id]['members'][] = $vmblastsGrp['ext'];
				}
		}
	}
	
}
# END load gobs of data.

function sanitizeLabels($text) {
    if ($text === null) {
        $text = '';
    }
    return $text;
}

function dpplog($level, $msg) {
    global $dpp_log_level;

    if (!isset($dpp_log_level) || $dpp_log_level < $level) {
        return;
    }

    $ts = date('Y-m-d H:i:s');
    $logFile = "/var/log/asterisk/dpviz.log";

    $fd = fopen($logFile, "a");
    if (!$fd) {
        error_log("Couldn't open log file: $logFile");
        return;
    }

    fwrite($fd, "[$ts] [Level $level] $msg\n");
    fclose($fd);
}

function secondsToTimes($seconds) {
    $seconds = (int) round($seconds); // Ensure whole number input

    $hours = (int) ($seconds / 3600);
    $minutes = (int) (($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return $hours > 0 ? "$hours hrs, $minutes mins" : 
           ($minutes > 0 ? "$minutes mins, $seconds secs" : "$seconds secs");
}

function formatPhoneNumbers($phoneNumber) {
    $hasPlusOne = strpos($phoneNumber, '+1') === 0;

    // Strip all non-digit characters
    $digits = preg_replace('/\D/', '', $phoneNumber);

    // If +1 was present, remove the leading '1' from digits so we format the last 10
    if ($hasPlusOne && strlen($digits) === 11 && $digits[0] === '1') {
        $digits = substr($digits, 1);
    }

    if (strlen($digits) === 10) {
        $areaCode = substr($digits, 0, 3);
        $nextThree = substr($digits, 3, 3);
        $lastFour = substr($digits, 6, 4);

        if ($hasPlusOne) {
            return '+1 (' . $areaCode . ') ' . $nextThree . '-' . $lastFour;
        } else {
            return '(' . $areaCode . ') ' . $nextThree . '-' . $lastFour;
        }
    }

    // Return original if it doesn't fit expected pattern
    return $phoneNumber;
}


function options_gets() {
	$row = \FreePBX::Dpviz()->getOptions();
	$i = 0;
	if(!empty($row) && is_array($row)) {
		foreach ($row as $item) {
			$row[$i] = $item;
			$i++;
		}
		return $row;
	} else {
		return [];
	}
}
?>


