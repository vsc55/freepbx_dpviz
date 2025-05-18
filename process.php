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
$jump = isset($input['jump']) ? $input['jump'] : '';
if ($ext==$jump){$jump='';}

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
$options=\FreePBX::Dpviz()->getOptions();
try{
	$soundlang = FreePBX::create()->Soundlang;
	$options['lang'] = $soundlang->getLanguage();
}catch(\Exception $e){
	freepbx_log(FPBX_LOG_ERROR,"Soundlang is missing, please install it."); 
	$options['lang'] = "en";
}

$datetime = isset($options['datetime']) ? $options['datetime'] : '1';
$panzoom = isset($options['panzoom']) ? $options['panzoom'] : '1';
function dpp_load_incoming_routes() {
  global $db;
	
  $sql = "select * from incoming order by extension";
  $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from incoming");       
  }
	
	$routes = array();
  // Store the routes in a hash indexed by the inbound number
  if (is_array($results)) {
    foreach ($results as $route) {
      $num = $route['extension'];
      $cid = $route['cidnum'];
			if (empty($num) && empty($cid)){$exten='ANY';}else{$exten=$num.$cid;}
      $routes[$exten] = $route;
    }
  }
	
	return $routes;
}


function dpp_find_route($routes, $num) {

  $match = array();
  $pattern = '/[^ANY_xX+0-9\[\]]/';   # remove all non-digits
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
$dproute['extension']= $ext;

	if (empty($dproute)) {
		$header = "<div><h2>Error: Could not find inbound route for ".$ext."</h2></div>";
	}else{
		dpp_load_tables($dproute,$options);   # adds data for time conditions, IVRs, etc.
		
		if (!empty($jump)){
			dpp_follow_destinations($dproute, '', $jump ,$options); #starts with destination
		}else{
			dpp_follow_destinations($dproute, $ext, '',$options); #starts with empty destination
		}
		
		/*  puts a box next to the first node.
		$dproute['dpgraph']->node('In Use By', array(
			'label' => "In Use By:\nline 2\lLine3\lLine3\lLine3\l",
			'shape' => 'box',
			'style' => 'filled',
			'fillcolor' => 'darkseagreen',
			'rank'=>'same'
			//'comment' => $langOption
		));
		*/
		
		$gtext = $dproute['dpgraph']->render();
		$gtext=json_encode($gtext);


		$header='<h2>Dial Plan For <span id="headerSelected"></span></h2>';
		if ($datetime==1){$header.= "<h6>".date('Y-m-d H:i:s')."</h6>";}

		$header.='
				<input type="hidden" id="processed" value="yes">
				<input type="hidden" id="ext" value="'.$ext.'">
				<input type="hidden" id="jump" value="'.$jump.'">
				<input type="hidden" id="panzoom" value="'.$panzoom.'">
				
				<script>
					function updateHeaderSelected() {
						let name = sessionStorage.getItem("selectedName");
						const headerSelected = document.getElementById("headerSelected");
						if (headerSelected) {
							headerSelected.textContent = name || "No name selected";
						}
					}
					updateHeaderSelected();
					
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
					
					function handleSVGExport() {
						const svgElement = document.querySelector(\'#vizContainer svg\');
						if (!svgElement) {
							alert(\'SVG not found!\');
							return;
						}

						const input = document.getElementById(\'filenameInput\');
						const filename = (input?.value.trim() || \'graph\') + \'.svg\';
						exportCleanedSVG(svgElement, filename);
					}
					
					function handleExport(scale) {
							var input = document.getElementById(\'filenameInput\');
							var filename = input.value.trim() || \'export\';
							exportImage(scale, filename + \'.png\');
					}
					
					
					function exportCleanedSVG(svgElement, filename) {
						// Clone the SVG to avoid changing the original
						const clonedSVG = svgElement.cloneNode(true);

						// Remove all <a> (link) elements from the SVG
						clonedSVG.querySelectorAll(\'a\').forEach(link => {
							const parent = link.parentNode;
							while (link.firstChild) {
								parent.insertBefore(link.firstChild, link);
							}
							parent.removeChild(link);
						});

						// Serialize the cleaned SVG and prepare the Blob
						const svgData = new XMLSerializer().serializeToString(clonedSVG);
						const blob = new Blob([svgData], { type: \'image/svg+xml;charset=utf-8\' });
						const url = URL.createObjectURL(blob);

						// Trigger download
						const a = document.createElement(\'a\');
						a.href = url;
						a.download = filename.endsWith(\'.svg\') ? filename : `${filename}.svg`;
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						URL.revokeObjectURL(url);
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
$horizontal = isset($options['horizontal']) ? $options['horizontal'] : '0';
$direction=($horizontal== 1) ? 'LR' : 'TB';
$dynmembers= isset($options['dynmembers']) ? $options['dynmembers'] : '0';
$combineQueueRing= isset($options['combineQueueRing']) ? $options['combineQueueRing'] : '0';
$extOptional= isset($options['extOptional']) ? $options['extOptional'] : '0';
$fmfmOption= isset($options['fmfm']) ? $options['fmfm'] : '0';
$langOption= isset($options['lang']) ? $options['lang'] : 'en';


  $pastels = array(
			"#7979FF", "#86BCFF", "#8ADCFF", "#3DE4FC", "#5FFEF7", "#33FDC0",
			"#ed9581", "#81a6a2", "#bae1e7", "#eb94e2", "#f8d580", "#979291",
			"#92b8ef", "#ad8086", "#F7A8A8", "#C5A3FF", "#FFC3A0", "#FFD6E0",
			"#FFB3DE", "#D4A5A5", "#A5D4D4", "#F5C6EC", "#B5EAD7", "#C7CEEA",
			"#E0BBE4", "#FFDFD3", "#FEC8D8", "#D1E8E2", "#E8D1E1", "#EAD5DC",
			"#F9E79F", "#D6EAF8"
	);

	$neons = array(
			"#fe0000", "#fdfe02", "#0bff01", "#011efe", "#fe00f6",
			"#ff5f1f", "#ff007f", "#39ff14", "#ff073a", "#ffae00",
			"#08f7fe", "#ff44cc", "#ff6ec7", "#dfff00", "#32cd32",
			"#ccff00", "#ff1493", "#00ffff", "#ff00ff", "#ff4500",
			"#ff00aa", "#ff4c4c", "#7df9ff", "#adff2f", "#ff6347",
			"#ff66ff", "#f2003c", "#ffcc00", "#ff69b4", "#0aff02"
	);
	
	$optional = preg_match('/^[ANY_xX+\d\[\]]+$/', $optional) ? '' : $optional;
  if (! isset ($route['dpgraph'])) {
		
    $route['dpgraph'] = new Alom\Graphviz\Digraph('"'.$route['extension'].'"');
		$route['dpgraph']->attr('graph',array('rankdir'=>$direction,'tooltip'=>' '));
		//$route['dpgraph']->attr('subgraph',array('shape'=>'box','fontsize'=>'16','label'=>'Hello'));
		//subgraph cluster_L { "File: [stackcollapse]" [shape=box fontsize=16 label="File: [stackcollapse]\l\lShowing nodes accounting for 380, 90.48% of 420 total\lDropped 120 nodes (cum <= 2)\lShowing top 20 nodes out of 110\l\lSee https://git.io/JfYMW for how to read the graph\l" tooltip="[stackcollapse]"] }
		
  }
	
  $dpgraph = $route['dpgraph'];
	
	
  dpplog(9, "destination='$destination' route[extension]: " . print_r($route['extension'], true));

  # This only happens on the first call.  Every recursive call includes
  # a destination to look at.  For the first one, we get the destination from
  # the route object.
	
  if ($destination == '') {
		
		$dpgraph->node($route['extension'], array(
			'label' => 'Back',
			'shape' => 'cds',
			'style' => 'filled',
			'fillcolor' => 'darkseagreen',
			//'comment' => $langOption
		));
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
      dpp_follow_destinations($route, $route['destination'].','.$langOption,'',$options);
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
	
	if (isset($route['parent_node'])){
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
  if (preg_match("/^app-announcement-(\d+),s,(\d+),(.+)/", $destination, $matches)) {
		$module='Announcement';
		$annum = $matches[1];
		$another = $matches[2];
		$anlang = $matches[3];
		
		if (isset($route['announcements'][$annum])){
			$an = $route['announcements'][$annum];
			$recID=$an['recording_id'];
		
			if (isset($route['recordings'][$recID])){
				$recording= $route['recordings'][$recID];
				$announcement= $recording['displayname'];
				$recordingId=$recording['id'];
			}else{
				$announcement="None";
			}
		
			#feature code exist?
			if ( isset($route['featurecodes']['*29'.$recID]) ){
				#custom feature code?
				if ($route['featurecodes']['*29'.$an['recording_id']]['customcode']!=''){$featurenum=$route['featurecodes']['*29'.$an['recording_id']]['customcode'];}else{$featurenum=$route['featurecodes']['*29'.$an['recording_id']]['defaultcode'];}
				#is it enabled?
				if ( ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ){$rec="Record(yes): ".$featurenum;}else{$rec="Record(no): ".$featurenum;}
			}else{
				$rec="Record(no): disabled";
			}
			
			$label=sanitizeLabels($an['description'])."\nRecording: ".sanitizeLabels($announcement)."\n".$rec;
			$tooltip='';
			makeNode($module,$annum,$label,$tooltip,$node);
			
			if ($an['post_dest'] != '') {
				$route['parent_edge_label'] = ' Continue';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $an['post_dest'].','.$anlang,'',$options);
			}
			
			if (isset($route['recordings'][$recID])){
				$route['parent_edge_label']= ' Recording';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, 'play-system-recording,'.$recordingId.',1,'.$anlang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		# end of announcements

		#
		# Blackhole
		#
  } elseif (preg_match("/^app-blackhole,(hangup|congestion|busy|zapateller|musiconhold|ring|no-service),(\d+)/", $destination, $matches)) {
		$blackholetype = $matches[1];
		$blackholetype = str_replace('musiconhold','Music On Hold',$blackholetype);
		$blackholetype = str_replace('ring','Play Ringtones',$blackholetype);
		$blackholetype = str_replace('no-service','Play No Service Message',$blackholetype);
		$blackholetype = ucwords(str_replace('-', ' ', $blackholetype));
		$blackholeother = $matches[2];
		$previousURL=$route['parent_node']->getAttribute('URL', '');

		$node->attribute('label', 'Terminate Call: '.$blackholetype);
		$node->attribute('tooltip', 'Terminate Call: '.$blackholetype);
		$node->attribute('URL', $previousURL);
    $node->attribute('target', '_blank');
		$node->attribute('shape', 'invhouse');
		$node->attribute('fillcolor', 'orangered');
		$node->attribute('style', 'filled');
		
		#end of Blackhole

		#
		# Call Flow Control (daynight)
		#
  } elseif (preg_match("/^app-daynight,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Call Flow";
    $daynightnum = $matches[1];
    $daynightother = $matches[2];
		$daynightLang=$matches[3];
		
		if (isset($route['daynight'][$daynightnum])){
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
					 dpp_follow_destinations($route, $d['dest'].','.$daynightLang,'',$options);
				}elseif ($d['dmode']=='night'){
						$route['parent_edge_label'] = ' Night Mode '.$nactive;
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $d['dest'].','.$daynightLang,'',$options);
				}elseif ($d['dmode']=="fc_description"){
						$label=sanitizeLabels($d['dest']) .$code;
				}
			}
			$tooltip='';
			makeNode($module,$daynightnum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Flow Control (daynight)

		#
		# Call Recording
		#
  } elseif (preg_match("/^ext-callrecording,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Call Recording";
		$callrecID = $matches[1];
		$callrecOther = $matches[2];
		$callLang= $matches[3];
		if (isset($route['callrecording'][$callrecID])){
			$callRec = $route['callrecording'][$callrecID];
			$callMode= ucfirst($callRec['callrecording_mode']);
			$callMode = str_replace("Dontcare", "Don't Care", $callMode);
			$label=sanitizeLabels($callRec['description'])."\nMode: ".$callMode;
			$tooltip='';
			
			makeNode($module,$callrecID,$label,$tooltip,$node);
			
			$route['parent_edge_label']= " Continue";
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $callRec['dest'].','.$callLang,'',$options);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Recording
		#
		
		# Conferences (meetme)
		#
  } elseif (preg_match("/^ext-meetme,(\d+),(\d+)/", $destination, $matches)) {
		$module="Conferences";
		$meetmenum = $matches[1];
		$meetmeother = $matches[2];
		if (isset($route['meetme'][$meetmenum])){
			$meetme = $route['meetme'][$meetmenum];
			$label = $meetme['exten'].' '.sanitizeLabels($meetme['description']);
			$tooltip='';
			makeNode($module,$meetmenum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Conferences (meetme)

		#
		# Directory
		#
  } elseif (preg_match("/^directory,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Directory";
		$directorynum = $matches[1];
		$directoryother = $matches[2];
		$directoryLang = $matches[3];
		if (isset($route['directory'][$directorynum])){
			$directory = $route['directory'][$directorynum];
			$label=sanitizeLabels($directory['dirname']);
			$tooltip='';
			makeNode($module,$directorynum,$label,$tooltip,$node);
			
			if ($directory['invalid_destination']!=''){
				 $route['parent_edge_label']= ' Invalid Input';
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $directory['invalid_destination'].','.$directoryLang,'',$options);
			}
		
		}else{
			notFound($module,$destination,$node);
		}
		#end of Directory

		#
		# DISA
		#
  } elseif (preg_match("/^disa,(\d+),(\d+)/", $destination, $matches)) {
		$module="DISA";
		$disanum = $matches[1];
		$disaother = $matches[2];
		if (isset($route['disa'][$disanum])){
			$disa = $route['disa'][$disanum];
			$label=sanitizeLabels($disa['displayname']);
			$tooltip='';
			makeNode($module,$disanum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of DISA

		#
		# Dynamic Routes
		#
  } elseif (preg_match("/^dynroute-(\d+),([a-z]),(\d+),(.+)/", $destination, $matches)) {
		$module="Dyn Route";
		$dynnum = $matches[1];
		$dynLang = $matches[4];
		if (isset($route['dynroute'][$dynnum])){
			$dynrt = $route['dynroute'][$dynnum];
			
			$recID=$dynrt['announcement_id'];
			if (isset($route['recordings'][$recID])){
				$recording= $route['recordings'][$recID];
				$announcement= $recording['displayname'];
				$recordingId=$recording['id'];
			}else{
				$announcement="None";
			}
			
			$label=sanitizeLabels($dynrt['name'])."\nAnnouncement: ".sanitizeLabels($announcement);
			$tooltip='';
			makeNode($module,$dynnum,$label,$tooltip,$node);
			
			if (!empty($dynrt['routes'])){
				ksort($dynrt['routes']);
				foreach ($dynrt['routes'] as $selid => $ent) {
					$desc = isset($ent['description']) ? $ent['description'] : '';
					
					$route['parent_edge_label']= "  Match: ".sanitizeLabels($ent['selection'])."\n".$desc;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ent['dest'].','.$dynLang,'',$options);
				}
			}
			
			if (isset($route['recordings'][$recID])){
				$route['parent_node'] = $node;
				$route['parent_edge_label']= ' Recording';
				dpp_follow_destinations($route, 'play-system-recording,'.$recordingId.',1,'.$dynLang,'',$options);
				
			}
			
			//are the invalid and default destinations the same?
			if ($dynrt['invalid_dest'] != '' && $dynrt['invalid_dest']==$dynrt['default_dest']){
				 $route['parent_edge_label']= ' Invalid Input, Default ('.$dynrt['timeout'].' secs)';
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $dynrt['invalid_dest'].','.$dynLang,'',$options);
			}else{
				if ($dynrt['invalid_dest'] != '') {
					$route['parent_node'] = $node;
					$route['parent_edge_label']= ' Invalid Input';
					dpp_follow_destinations($route, $dynrt['invalid_dest'].','.$dynLang,'',$options);
				}
				if ($dynrt['default_dest'] != '') {
					$route['parent_node'] = $node;
					$route['parent_edge_label']= ' Default ('.$dynrt['timeout'].' secs)';
					dpp_follow_destinations($route, $dynrt['default_dest'].','.$dynLang,'',$options);
				}
			}
		
		}else{
			notFound($module,$destination,$node);
		}
		#end of Dynamic Routes

		#
		# Extension (from-did-direct)
		#
  } elseif (preg_match("/^from-did-direct,(\d+),(\d+),(.+)/", $destination, $matches)) {
	
		$extnum = $matches[1];
		$extLang= $matches[3];
		
		if (isset($route['extensions'][$extnum])){
			$extension = $route['extensions'][$extnum];
			$extname= $extension['name'];
			$extemail= $extension['email'];
			$extemail= str_replace("|",",\n",$extemail);
			
			if (isset($extension['fmfm'])){
				if ($extension['fmfm']['ddial']=='DIRECT'){
					$fmfmLabel="\n\nFMFM Enabled\nInitial Ring Time: ".secondsToTimes($extension['fmfm']['prering'])."\nRing Time: ".secondsToTimes($extension['fmfm']['grptime'])."\nFollow-Me List: ".$extension['fmfm']['grplist']."\nConfirm Calls: ".$extension['fmfm']['grpconf'];
				}else{
					$fmfmLabel="\n\nFMFM Disabled";
				}
			}else{
				$fmfmLabel='';
			}
			
			
			$node->attribute('label', 'Extension: '.$extnum.' '.sanitizeLabels($extname)."\n".sanitizeLabels($extemail));
			$node->attribute('tooltip', $node->getAttribute('label').$fmfmLabel);
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$extnum));
			$node->attribute('target', '_blank');
			
			if (isset($extension['fmfm']) && $fmfmOption){
				if ($extension['fmfm']['ddial']=='DIRECT'){
						$grplist = preg_split("/-/", $extension['fmfm']['grplist']);
						foreach ($grplist as $g){
							$g=trim($g);
							$g=str_replace('#', '', $g);
							$follow='from-did-direct,'.$g.',1,'.$extLang;
							
							$route['parent_edge_label'] = ' FMFM ('.secondsToTimes($extension['fmfm']['prering']).')';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $follow,'',$options);
						}
						
						if (isset($extension['fmfm']['postdest']) && $extension['fmfm']['postdest']!='ext-local,'.$extnum.',dest'){
							$route['parent_edge_label'] = ' FMFM No Answer';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route,$extension['fmfm']['postdest'].','.$extLang,'',$options);
						}
				}
				
			}
			
		}else{
			//phone numbers or remote extensions
			$node->attribute('label', $extnum);
			$node->attribute('tooltip', $node->getAttribute('label'));
		}
		
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
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
			} elseif (
					$extension['noanswer_dest'] === $extension['busy_dest']
					&& $extension['chanunavail_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Busy are the same, but Not Reachable is different
					$route['parent_edge_label'] = ' No Answer & Busy';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
				}
					//Not Reachable
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = ' Not Reachable';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'].','.$extLang,'',$options);
					}
			} elseif (
					$extension['noanswer_dest'] === $extension['chanunavail_dest']
					&& $extension['busy_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Not Reachable are the same
					$route['parent_edge_label'] = ' No Answer & Not Reachable';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
				}
					//Busy
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = ' Busy';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
					}
			} elseif (
					$extension['busy_dest'] === $extension['chanunavail_dest']
					&& $extension['noanswer_dest'] !== $extension['busy_dest']
			) {
				if (!empty($extension['busy_dest'])) {
					// Busy and Not Reachable are the same
					$route['parent_edge_label'] = ' Busy & Not Reachable';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
				}
					//No Answer
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = ' No Answer';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
					}
			} else {
					// All are different
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = ' No Answer';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
					}
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = ' Busy';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
					}
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = ' Not Reachable';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'].','.$extLang,'',$options);
					}
			}
		}
		#end of Extension (from-did-direct)

		#
		# Feature Codes
		#
  } elseif (preg_match("/^ext-featurecodes,(\*?\d+),(\d+)/", $destination, $matches)) {
		$module="Feature Code";
		$featurenum = $matches[1];
		$featureother = $matches[2];
		if (isset($route['featurecodes'][$featurenum])){
			$feature = $route['featurecodes'][$featurenum];
			if ($feature['customcode']!=''){$featurenum=$feature['customcode'];}
			$label=sanitizeLabels($feature['description'])." <".$featurenum.">";
			$tooltip='';
			makeNode($module,'',$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Feature Codes

		#
		# Inbound Routes
		#
  } elseif (preg_match("/^from-trunk,([^,]*)\-([^,]*),(\d+),(.+)/", $destination, $matches)) {
		
		$num = $matches[1];
		$numcid = $matches[2];
		$numLang= $matches[4];
		if (empty($num)){$num='ANY';}
		if ($numcid==''){$numcidd=' / ANY';}else{$numcidd=" / ".$numcid;}
		
		$incoming = $route['incoming'][$num];
		
		$didLabel = ($num == "") ? "ANY" : formatPhoneNumbers($num);
		$didLabel.= $numcidd."\n".$incoming['description'];
		$didLink=$num.'/';
		
		
		$didTooltip=$num."\n";
		$didTooltip.= !empty($incoming['cidnum']) ? "Caller ID Number= " . $incoming['cidnum']."\n" : "";
		$didTooltip.= !empty($incoming['description']) ? "Description= " . $incoming['description']."\n" : "";
		$didTooltip.= !empty($incoming['alertinfo']) ? "Alert Info= " . $incoming['alertinfo']."\n" : "";
		$didTooltip.= !empty($incoming['grppre']) ? "CID Prefix= " . $incoming['grppre']."\n" : "";
		$didTooltip.= !empty($incoming['mohclass']) ? "MOH Class= " . $incoming['mohclass']."\n" : "";
		
		$node->attribute('label', sanitizeLabels($didLabel));
		$node->attribute('tooltip',sanitizeLabels($didTooltip));
		$node->attribute('width', 2);
    $node->attribute('margin','.13');
		$node->attribute('URL', htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'cds');
		$node->attribute('fillcolor', 'darkseagreen');
		$node->attribute('style', 'filled');
		
		$route['parent_edge_label']= " Always";
		$route['parent_node'] = $node;
		dpp_follow_destinations($route, $incoming['destination'].','.$numLang,'',$options);

		#end of Inbound Routes

		#
		# IVRs
		#
  } elseif (preg_match("/^ivr-(\d+),([a-z]+),(\d+),(.+)/", $destination, $matches)) {
		$module="IVR";
    $inum = $matches[1];
    $iflag = $matches[2];
    $iother = $matches[3];
		$ilang= $matches[4];

		if (isset($route['ivrs'][$inum])){
			$ivr = $route['ivrs'][$inum];
			$recID= $ivr['announcement'];
			
			if (isset($route['recordings'][$recID])){
				$recording= $route['recordings'][$recID];
				$ivrRecName= $recording['displayname'];
				$recordingId=$recording['id'];
				
			}else{
				$ivrRecName="None";
			}
			
		
			#feature code exist?
			if ( isset($route['featurecodes']['*29'.$ivr['announcement']]) ){
				#custom feature code?
				if ($route['featurecodes']['*29'.$ivr['announcement']]['customcode']!=''){$featurenum=$route['featurecodes']['*29'.$ivr['announcement']]['customcode'];}else{$featurenum=$route['featurecodes']['*29'.$ivr['announcement']]['defaultcode'];}
				#is it enabled?
				if ( ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ){$rec='(yes): '.$featurenum;}else{$rec='(no): '.$featurenum;}
			}else{
				$rec='(no): disabled';
			}
			
			$label=sanitizeLabels($ivr['name'])."\nAnnouncement: ".sanitizeLabels($ivrRecName)."\nRecord ".$rec."\n";
			if ($ivr['directdial']=='ext-local'){
				$ddial="Enabled";
			}elseif (is_numeric($ivr['directdial'])){
				$ddial=$route['directory'][$ivr['directdial']]['dirname'];
			}else{
				$ddial=$ivr['directdial'];
			}
			if ($ivr['retvm']==''){
				$retvm="No";
			}else{
				$retvm="Yes";
			}
			$tooltip="IVR DTMF Options\nEnable Direct Dial: ".$ddial."\nTimeout: ".secondsToTimes($ivr['timeout_time'])."\nInvalid Retries: ".$ivr['invalid_loops']."\nInvalid Retry Recording: ".findRecording($route,$ivr['invalid_retry_recording'])."\nInvalid Recording: ".findRecording($route,$ivr['invalid_recording'])."\nTimeout Retries: ".$ivr['timeout_loops']."\nTimeout Retry Recording: ".findRecording($route,$ivr['timeout_retry_recording'])."\nTimeout Recording: ".findRecording($route,$ivr['timeout_recording'])."\nReturn to IVR after VM: ".$retvm."\n";
			
			makeNode($module,$inum,$label,$tooltip,$node);
			
			# The destinations we need to follow are the invalid_destination,
			# timeout_destination, and the selection targets
			if (isset($route['recordings'][$recID])){
				$route['parent_node'] = $node;
				$route['parent_edge_label']= ' Recording';
				dpp_follow_destinations($route, 'play-system-recording,'.$recordingId.',1,'.$ilang,'',$options);
				
			}
			
			#now go through the selections
			if (!empty($ivr['entries'])){
				ksort($ivr['entries']);
				foreach ($ivr['entries'] as $selid => $ent) {
					
					$route['parent_edge_label']= ' Selection '.$ent['selection'];
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ent['dest'].','.$ilang,'',$options);
				}
			}
			
			#are the invalid and timeout destinations the same?
			if ($ivr['invalid_destination']==$ivr['timeout_destination']){
				if (!empty($ivr['invalid_destination'])){
				 $route['parent_edge_label']= ' Invalid Input, Timeout ('.$ivr['timeout_time'].' secs)';
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $ivr['invalid_destination'].','.$ilang,'',$options);
				}
			}else{
					if ($ivr['invalid_destination'] != '') {
						$route['parent_edge_label']= ' Invalid Input';
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $ivr['invalid_destination'].','.$ilang,'',$options);
					}
					if ($ivr['timeout_destination'] != '') {
						$route['parent_edge_label']= ' Timeout ('.$ivr['timeout_time'].' secs)';
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $ivr['timeout_destination'].','.$ilang,'',$options);
					}
			}
		}else{
			notFound($module,$destination,$node);
		}		
		# end of IVRs

		#
		# Languages
		#
  } elseif (preg_match("/^app-languages,(\d+),(\d+)/", $destination, $matches)) {
		$module="Languages";
		$langnum = $matches[1];
		$langother = $matches[2];
		if (isset($route['languages'][$langnum])){
			$langArray = $route['languages'][$langnum];
			$label=sanitizeLabels($langArray['description']);
			$tooltip='';
			makeNode($module,$langnum,$label,$tooltip,$node);
			
			if ($langArray['dest'] != '') {
				$route['parent_edge_label'] = ' Continue';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $langArray['dest'].','.$langArray['lang_code'],'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Languages

		#
		# MISC Applications
		#
  } elseif (preg_match("/^miscapps,(\d+),([a-z]+),(\d+),(.+)/", $destination, $matches)) {
		$module="Misc Apps";
		$miscappsnum = $matches[1];
		$miscappsLang = $matches[4];
		
		if (isset($route['miscapps'][$miscappsnum])){
			$miscapps = $route['miscapps'][$miscappsnum];
			$miscappsDialplan= shell_exec('/usr/sbin/asterisk -rx "dialplan show app-miscapps" | grep "'.$miscapps['ext'].'"');
			$enabled = empty($miscappsDialplan) ? '(disabled)' : '';
			
			$label=sanitizeLabels($miscapps['description']).' ('.$miscapps['ext'].') '.$enabled;
			$tooltip='';
			makeNode($module,$miscappsnum,$label,$tooltip,$node);
			
			if ($miscapps['dest'] != '') {
				$route['parent_edge_label'] = ' Continue';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $miscapps['dest'].','.$miscappsLang, '',$options);
			}
		
		}else{
			notFound($module,$destination,$node);
		}
		#end of MISC Applications

		#
		# MISC Destinations
		#
  } elseif (preg_match("/^ext-miscdests,(\d+),(\d+)/", $destination, $matches)) {
		$module="Misc Dests";
		$miscdestnum = $matches[1];
		$miscdestother = $matches[2];

		if (isset($route['miscdest'][$miscdestnum])){
			$miscdest = $route['miscdest'][$miscdestnum];
			$label=sanitizeLabels($miscdest['description']).' ('.$miscdest['destdial'].')';
			$tooltip='';
			makeNode($module,$miscdestnum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of MISC Destinations

		#
		# Play Recording
		#
  } elseif (preg_match("/^play-system-recording,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$recID = $matches[1];
		$recOther = $matches[2];
		$recLang = $matches[3];
		
		if (isset($route['recordings'][$recID])){
			$rec=$route['recordings'][$recID];
			$playName=$rec['displayname'];
		}else{
			$playName='None';
		}
		
		$node->attribute('label', 'Recording ('.$recLang.'): '.sanitizeLabels($playName));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', '#');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', $pastels[16]);
		$node->attribute('style', 'filled');
		#end of Play Recording
		
		#
		# Queue Priorities
		#
  }elseif (preg_match("/^app-queueprio,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Queue Priorities";
		$queueprioID = $matches[1];
		$queueprioIDOther = $matches[2];
		$queuepriorLang= $matches[3];
		
		if (isset($route['queueprio'][$queueprioID])){
			$queueprio = $route['queueprio'][$queueprioID];
			$label=sanitizeLabels($queueprio['description']."\nPriority: ".$queueprio['queue_priority']);
			$tooltip='';
			makeNode($module,$queueprioID,$label,$tooltip,$node);
			
			if ($queueprio['dest'] != '') {
				$route['parent_edge_label'] = ' Continue';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $queueprio['dest'].','.$queuepriorLang, '',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Queue Priorities
		
		#
		# Queues
		#
  } elseif (preg_match("/^ext-queues,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Queues";
    $qnum = $matches[1];
    $qother = $matches[2];
		$qlang= $matches[3];

		if (isset($route['queues'][$qnum])){
			$q = $route['queues'][$qnum];
			
			if ($q['maxwait'] == 0 || $q['maxwait'] == '' || !is_numeric($q['maxwait'])) {
				$maxwait = 'Unlimited';
			} else {
			$maxwait = secondsToTimes($q['maxwait']);
			}
			
			$label=$qnum.' '.sanitizeLabels($q['descr']);
			$restrict=array('Call as Dialed','No Follow-Me or Call Forward','Extensions Only');
			$skipbusy=array('No','Yes','Yes + (ringinuse=no)','Queue calls only (ringinuse=no)');
			if (isset($q['data']['music'])){$music=$q['data']['music'];}else{$music='inherit';}
			$mohclass=array('MoH Only','Ring Only','Agent Ringing');
			$noyes=array('No','Yes');
			$maxcallers = ($q['data']['maxlen'] == 0) ? 'Unlimited' : $q['data']['maxlen'];
			if ($q['ivr_id']!='none'){
				$breakoutname = isset($route['ivrs'][$q['ivr_id']]['name']) ? $route['ivrs'][$q['ivr_id']]['name'] : "none";
				$periodic="Periodic Announcements\nIVR Break Out Menu: ".$breakoutname."\nRepeat Frequency: ".secondsToTimes($q['data']['periodic-announce-frequency']);
			}else{
				$periodic="Periodic Announcements\nDisabled";
			}
			$tooltip="General Settings\nCID Prefix: ".$q['grppre']."\nStrategy: ".$q['data']['strategy']."\nAgent Restrictions: ".$restrict[$q['use_queue_context']]."\nAutofill: ".ucfirst($q['data']['autofill'])."\nSkip Busy Agents: ".$skipbusy[$q['cwignore']]."\nMusic On Hold Class: ".$music." (".$mohclass[$q['ringing']].")\nCall Recording: ".$q['data']['recording']."\nMark calls answered elsewhere: ".$noyes[$q['data']['answered_elsewhere']].
			"\n\nTiming & Agent Options\nMax Wait Time: ".$maxwait."\nAgent Timeout: ".secondsToTimes($q['data']['timeout'])."\nAgent Retry: ".secondsToTimes($q['data']['retry'])."\nWrap-Up-Time: ".secondsToTimes($q['data']['wrapuptime']).
			"\n\nCapacity Options\nMax Callers: ".$maxcallers."\nJoin Empty: ".ucfirst($q['data']['joinempty'])."\nLeave Empty: ".ucfirst($q['data']['leavewhenempty']).
			"\n\nCaller Position\nFrequency: ".secondsToTimes($q['data']['announce-frequency'])."\nMinimum Announcement Interval: ".secondsToTimes($q['data']['min-announce-frequency'])."\nAnnounce Position: ".ucfirst($q['data']['announce-position'])."\nAnnounce Hold Time: ".ucfirst($q['data']['announce-holdtime'])."\n\n".$periodic;
			makeNode($module,$qnum,$label,$tooltip,$node);
			
			if (!empty($q['members'])){
				foreach ($q['members'] as $types=>$type) {
					foreach ($type as $member){
						$route['parent_node'] = $node;
						$route['parent_edge_label'] = ($types == 'static') ? ' Static' : ' Dynamic';
						switch ($combineQueueRing) {
							case "2":
									$go="from-did-direct,$member,1,$qlang";
									break;
							default:
									$go="qmember$member";
						}
						dpp_follow_destinations($route, $go,'',$options);
					}
				}
			}
			
			# The destinations we need to follow are the queue members (extensions)
			# and the no-answer destination.
			if ($q['dest'] != '') {
				$route['parent_edge_label'] = ' No Answer ('.$maxwait.')';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $q['dest'].','.$qlang,'',$options);
			}
			
			if (is_numeric($q['ivr_id'])){
				$route['parent_edge_label'] = ' IVR Break Out (every '.secondsToTimes($q['data']['min-announce-frequency']).')';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, 'ivr-'.$q['ivr_id'].',s,1,'.$qlang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
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
  } elseif (preg_match("/^ext-group,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Ring Groups";
    $rgnum = $matches[1];
		$rglang = $matches[3];
		if (isset($route['ringgroups'][$rgnum])){
			$rg = $route['ringgroups'][$rgnum];
			
			$label=$rgnum.' '.sanitizeLabels($rg['description']);
			if ($rg['needsconf']!=''){$conf='Yes';}else{$conf="No";}
			$tooltip="Strategy: ".$rg['strategy']."\nRing Time: ".secondsToTimes($rg['grptime'])."\nMusic On Hold: ".$rg['ringing']."\nCID Prefix: ".$rg['grppre']."\nConfirm Calls: ".$conf."\nCall Recording: ".$rg['recording']."\n";
			makeNode($module,$rgnum,$label,$tooltip,$node);
			
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
								$go="from-did-direct,$member,1,$rglang";
								break;
						default:
								$go="rg$member";
				}
				dpp_follow_destinations($route,$go,'',$options);
			} 
			
			# The destinations we need to follow are the no-answer destination
			# (postdest) and the members of the group.
			if ($rg['postdest'] != '') {
				$route['parent_edge_label'] = ' No Answer ('.secondsToTimes($rg['grptime']).')';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $rg['postdest'].','.$rglang,'',$options);
			}
			if ($rg['annmsg_id']!=0){
				$route['parent_node'] = $node;
				$route['parent_edge_label'] = 'Announcement';
				dpp_follow_destinations($route,'play-system-recording,'.$rg['annmsg_id'].',1,'.$rglang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
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
  } elseif (preg_match("/^app-setcid,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Set CID";
		$cidnum = $matches[1];
		$cidother = $matches[2];
		$cidLang = $matches[3];
		
		if (isset($route['setcid'][$cidnum])){
			$cid = $route['setcid'][$cidnum];
			$label= sanitizeLabels("\nName= ".preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name'])."\nNumber= ".preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']));
			$tooltip='';
			makeNode($module,$cidnum,$label,$tooltip,$node);

			if ($cid['dest'] != '') {
				$route['parent_edge_label'] = ' Continue';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $cid['dest'].','.$cidLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Set CID
		
		#
		# TTS
		#
  } elseif (preg_match("/^ext-tts,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="TTS";
		$ttsnum = $matches[1];
		$ttsother = $matches[2];
		$ttsLang= $matches[3];
		if (isset($route['tts'][$ttsnum])){
			$tts = $route['tts'][$ttsnum];
			$label= sanitizeLabels($tts['name']);
			$tooltip = "Engine: ".$tts['engine']."\nDesc: ".$tts['text'];
			makeNode($module,$ttsnum,$label,$tooltip,$node);
			
			if ($tts['goto'] != '') {
				$route['parent_edge_label'] = ' Continue';
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $tts['goto'].','.$ttsLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of TTS
		
		#
		# Time Conditions
		#
  } elseif (preg_match("/^timeconditions,(\d+),(\d+),(.+)/", $destination, $matches)) {
    $tcnum = $matches[1];
    $tcother = $matches[2];
		$tcLang= $matches[3];
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
    //echo $tgLabel;
		
    $route['parent_edge_label'] = " Match:\n".$tgLabel;
    $route['parent_edge_url'] = htmlentities($tgLink);
		$route['parent_edge_target'] = '_blank';
		$route['parent_edge_labeltooltip']=" Match\n".$tgTooltip;

    $route['parent_node'] = $node;
	  dpp_follow_destinations($route, $tc['truegoto'].','.$tcLang,'',$options);

		$route['parent_edge_label'] = " No Match";
    $route['parent_edge_url'] = htmlentities($tgLink);
    $route['parent_edge_target'] = '_blank';
		$route['parent_edge_labeltooltip']=" No Match\n".$tgTooltip;
    $route['parent_node'] = $node;
		dpp_follow_destinations($route, $tc['falsegoto'].','.$tcLang,'',$options);
		#end of Time Conditions
 
		#
		# Voicemail
		#
  } elseif (preg_match("/^ext-local,vm([b,i,s,u])(\d+),(\d+)/", $destination, $matches)) {
		$module='Voicemail';
		$vmtype= $matches[1];
		$vmnum = $matches[2];
		$vmother = $matches[3];
		
		$vm_array=array('b'=>'(Busy Message)','i'=>'(Instructions Only)','s'=>'(No Message)','u'=>'(Unavailable Message)' );
		if (isset($route['extensions'][$vmnum]['name'])){
			$vmname= $route['extensions'][$vmnum]['name'];
			$vmemail= $route['extensions'][$vmnum]['email'];
			$vmemail= str_replace("|",",\n",$vmemail);
			$label=$vmnum." ".sanitizeLabels($vmname)." ".$vm_array[$vmtype]."\n".sanitizeLabels($vmemail);
			$tooltip='';
			makeNode($module,$vmnum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Voicemail
	
		#
		# VM Blast
		#
  } elseif (preg_match("/^vmblast\-grp,(\d+),(\d+)/", $destination, $matches)) {
		$module="VM Blast";
		$vmblastnum = $matches[1];
		$vmblastother = $matches[2];
		
		if (isset($route['vmblasts'][$vmblastnum])){
			$vmblast = $route['vmblasts'][$vmblastnum];
			$label=$vmblastnum.' '.sanitizeLabels($vmblast['description']);
			$tooltip='';
			makeNode($module,$vmblastnum,$label,$tooltip,$node);
			
			if (!empty($vmblast['members'])){
				foreach ($vmblast['members'] as $member) {
					$route['parent_edge_label']= '';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, 'vmblast-mem,'.$member,'',$options);
				}
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of VM Blast
		
		#
		#VM Blast members
		#
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
		
		
		#Custom Destinations (with return)
		#
	} elseif (preg_match("/^customdests,dest-(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Custom Dests";
		$custId=$matches[1];
		$custLang=$matches[3];
		
		if (isset($route['customapps'][$custId])){
			$custDest=$route['customapps'][$custId];
			$custReturn = ($custDest['destret'] == 1) ? "Yes" : "No";
			$label=$custDest['description']."\lTarget: ".$custDest['target']."\lReturn: ".$custReturn."\l";
			$tooltip="";
			makeNode($module,$custId,$label,$tooltip,$node);
			
			if ($custDest['destret']){
				$route['parent_edge_label']= ' Return';
				$route['parent_node'] = $node;
				
				dpp_follow_destinations($route, $custDest['dest'].','.$custLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
	
		#preg_match not found
		
	}else {
	
		if (!empty($route['customapps'])){
			#custom destinations
			foreach ($route['customapps'] as $entry) {
				if (preg_match('/(,[^,]+)$/', $destination, $matches)) {
					$destLang = $matches[1]; // This will be ",en"
				}
				$destNoLang= preg_replace('/,[^,]+$/', '', $destination);

				if ($entry['target']=== $destNoLang) {
					$custDest=$entry;
					$custDest['lang']=$destLang;
					break;
				}
			}
			#end of Custom Destinations (with return)
		}
		
		if (!empty($custDest)){
			
			if (isset($custDest['destid'])){
				$module="Custom Dests";
				$custId=$custDest['destid'];
				$custReturn = ($custDest['destret'] == 1) ? "Yes" : "No";
				
				$label=sanitizeLabels($custDest['description'])."\lTarget: ".$custDest['target']."\lReturn: ".$custReturn."\l";
				$tooltip="";
				makeNode($module,$custId,$label,$tooltip,$node);
				
				if ($custDest['destret']){
					$route['parent_edge_label']= ' Return';
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $custDest['dest'].$custDest['lang'],'',$options);
				}
			}else{
				notFound($module,$destination,$node);
			}
		}else{
			dpplog(1, "Unknown destination type: $destination");
			$node->attribute('fillcolor', $pastels[12]);
			$node->attribute('label', sanitizeLabels($destination));
			$node->attribute('style', 'filled');
    }
  }
}

# load gobs of data.  Save it in hashrefs indexed by ints
function dpp_load_tables(&$dproute,$options) {
	global $db;
	$dynmembers= isset($options['dynmembers']) ? $options['dynmembers'] : '0';
	$fmfmOption= isset($options['fmfm']) ? $options['fmfm'] : '0';
	
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
	
	
	//fmfm
	$D='/usr/sbin/asterisk -rx "database show AMPUSER" | grep \'followme\' | cut -d \'/\' -f3,5';
	exec($D, $fmfm);
	foreach ($fmfm as $line){
				// Split into key and value
		list($left, $value) = explode(':', $line, 2);
		$left = trim($left);
		$value = trim($value);

		// Split the left part into extension and subkey
		list($ext, $subkey) = explode('/', $left, 2);
		$dproute['extensions'][$ext]['fmfm'][$subkey]=$value;
	}


	# Inbound Routes
  $query = "select * from incoming order by extension";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from incoming");
  }
  foreach($results as $incoming) {
		$id = empty($incoming['extension']) ? 'ANY' : $incoming['extension'];
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
	$tables = array('announcement','callrecording','daynight','directory_details','disa','dynroute','dynroute_dests','featurecodes','kvstore_FreePBX_modules_Calendar',
							'kvstore_FreePBX_modules_Customappsreg','languages','meetme','miscapps','miscdests','queueprio','queues_config','queues_details',
							'ringgroups','setcid','timeconditions','timegroups_groups','timegroups_details','tts','vmblast','vmblast_groups');
	
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
		}elseif ($table == 'callrecording') {
				foreach($results as $callrecording) {
					$id = $callrecording['callrecording_id'];
					$dproute['callrecording'][$id] = $callrecording;
					dpplog(9, "callrecording=$id");
				}
		}elseif ($table == 'daynight') {
				foreach($results as $daynight) {
					$id = $daynight['ext'];
					if (!isset($dproute['daynight'][$id])) {
							$dproute['daynight'][$id] = array();
					}
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
    }elseif ($table == 'miscapps') {
        foreach($results as $miscapps) {
					$id = $miscapps['miscapps_id'];
					$dproute['miscapps'][$id] = $miscapps;
					dpplog(9, "miscdest dest: $id");
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
						if ($exploded[1]!== "*"){$dow_parts = explode("-", $exploded[1]);foreach ($dow_parts as &$part) {$part = ucfirst($part);}$dow = implode("-", $dow_parts) . ", ";} else {$dow = "";}
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
					$dproute['vmblasts'][$id]['members'][]=$vmblastsGrp['ext'];
				}
		}
	}
	
}
# END load gobs of data.

function sanitizeLabels($text) {
    if ($text === null) {
        $text = '';
    }
		
		//$text=addcslashes($text, '"');
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8');

		//$text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);
		//$text = str_replace('\"', '"', $text);
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
    $remainingSeconds = $seconds % 60;

    if ($hours > 0) {
        return $remainingSeconds === 0 ? "$hours hrs, $minutes mins" : "$hours hrs, $minutes mins, $remainingSeconds secs";
    } elseif ($minutes > 0) {
        return $remainingSeconds === 0 ? "$minutes mins" : "$minutes mins, $remainingSeconds secs";
    } else {
        return "$remainingSeconds secs";
    }
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

function sanitize_filename($string, $replace_with = '_') {
    // Replace spaces and other separators with underscore (or your preferred character)
    $string = preg_replace('/[^\w\-\.]+/', $replace_with, $string);

    // Remove multiple consecutive replace characters
    $string = preg_replace('/' . preg_quote($replace_with, '/') . '+/', $replace_with, $string);

    // Trim leading/trailing replace character
    $string = trim($string, $replace_with);

    // Prevent reserved names (optional for Windows safety)
    $reserved = ['CON','PRN','AUX','NUL','COM1','COM2','COM3','COM4','COM5','COM6','COM7','COM8','COM9',
                 'LPT1','LPT2','LPT3','LPT4','LPT5','LPT6','LPT7','LPT8','LPT9'];
    if (in_array(strtoupper($string), $reserved)) {
        $string = '_' . $string;
    }

    return $string;
}

function makeNode($module,$id,$label,$tooltip,$node){

	switch ($module) {
			case 'Announcement':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='note';
					$color='oldlace';
					break;
					
			case 'Call Flow':
					$url='daynight&view=form&itemid='.$id.'&extdisplay='.$id;
					$shape='rect';
					$color='#F7A8A8';
					break;

			case 'Call Recording':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='rect';
					$color='burlywood';
					break;
			
			case 'Conferences':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='rect';
					$color='burlywood';
					break;

			case 'Custom Dests':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&destid='.$id;
					$shape='component';
					$color='#D1E8E2';
					break;

			case 'Directory':
					$url=strtolower($module).'&view=form&id='.$id;
					$shape='folder';
					$color='#eb94e2';
					break;
					
			case 'DISA':
					$url=strtolower($module).'&view=form&itemid='.$id;
					$shape='folder';
					$color='#eb94e2';
					break;
					
			case 'Dyn Route':
					$url=str_replace(' ', '', strtolower($module)).'&action=edit&id='.$id;
					$shape='component';
					$color='#92b8ef';
					break;
					
			case 'Feature Code':
					$url=str_replace(' ', '', strtolower($module)).'admin';
					$shape='folder';
					$color='gainsboro';
					break;

			case 'IVR':
					$url=strtolower($module).'&action=edit&id='.$id;
					$shape='component';
					$color='gold';
					break;

			case 'Languages':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='note';
					$color='#ed9581';
					break;
					
			case 'Misc Apps':
					$url=str_replace(' ', '', strtolower($module)).'&action=edit&extdisplay='.$id;
					$shape='rpromoter';
					$color='#5FFEF7';
					break;
					
			case 'Misc Dests':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='rpromoter';
					$color='coral';
					break;
					
			case 'Queue Priorities':
					$url='queueprio&view=form&extdisplay='.$id;
					$shape='rect';
					$color='#FFC3A0';
					break;
			
			case 'Queues':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='hexagon';
					$color='mediumaquamarine';
					break;
					
			case 'Ring Groups':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='rect';
					$color='#92b8ef';
					break;
					
			case 'Set CID':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&id='.$id;
					$shape='note';
					$color='#ed9581';
					break;
			
			case 'TTS':
					$url=strtolower($module).'&view=form&id='.$id;
					$shape='note';
					$color='#ed9581';
					break;
					
			case 'VM Blast':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='folder';
					$color='gainsboro';
					break;
					
			case 'Voicemail':
					$url='extensions&extdisplay='.$id;
					$shape='folder';
					$color='#979291';
					break;

			default:
					// Code to execute if no case matches
	}

	$node->attribute('label', "{$module}: {$label}");
	$node->attribute('tooltip', $tooltip);
	$node->attribute('URL', htmlentities('/admin/config.php?display='.$url));
	$node->attribute('target', '_blank');
	$node->attribute('shape', $shape);
	$node->attribute('fillcolor', $color);
	$node->attribute('style', 'filled');
	
	return $node;
}


function notFound($module,$destination,$node){
	
	$pos = strrpos($destination, ',');
	if ($pos !== false) {
			$output = substr($destination, 0, $pos);
	} else {
			$output = $destination; // No comma found
	}
	
	$node->attribute('label', "Bad Dest: {$module}: {$output}");
	$node->attribute('tooltip', "Bad Dest: {$module}: {$output}");
	$node->attribute('shape', 'rect');
	$node->attribute('fillcolor', 'red');
	$node->attribute('style', 'filled');
	
	return $node;
}

function findRecording($route,$id){
	//echo "-- $id --";
	if (is_numeric($id)){
		
		if (isset($route['recordings'][$id])){
			$name=$route['recordings'][$id]['displayname'];
		}else{
			$name="not found";
		}
	}elseif ($id==''){
		$name="None";
	}else{
		$name=$id;
	}
	return $name;
}






