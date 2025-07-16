<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true);

if (!empty($_SESSION['lang'])){
	$locale=$_SESSION['lang'];
	$baseLocale = preg_replace('/\..*$/', '', $locale);

	putenv("LC_ALL=$locale");
	putenv("LANGUAGE=$baseLocale");
	setlocale(LC_ALL, $locale);

	// Set up gettext
	bindtextdomain("dpviz", "/var/www/html/admin/modules/dpviz/i18n");
	bind_textdomain_codeset("dpviz", "UTF-8");
	textdomain("dpviz");
}

// Basic check

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid JSON'));
    exit;
}

$ext  = isset($input['ext']) ? $input['ext'] : '';
$jump = isset($input['jump']) ? $input['jump'] : '';
$skip = isset($input['skip']) ? $input['skip'] : array();

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
require_once 'graphviz/src/Alom/Graphviz/RawText.php';

//options
$options=\FreePBX::Dpviz()->getOptions();
try{
	$soundlang = FreePBX::create()->Soundlang;
	$options['lang'] = $soundlang->getLanguage();
}catch(\Exception $e){
	freepbx_log(FPBX_LOG_ERROR,"Soundlang is missing, please install it."); 
	$options['lang'] = "en";
}

$options['hideall']=0;
$options['skip'] = isset($input['skip']) ? $input['skip'] : array();

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
		$header = "<div><h2>" . _('Error: Could not find inbound route for') ." ".$ext."</h2></div>";
	}else{
		dpp_load_tables($dproute,$options);   # adds data for time conditions, IVRs, etc.
		
		if (!empty($jump)){
			dpp_follow_destinations($dproute, '', $jump, $options); #starts with destination
		}else{
			dpp_follow_destinations($dproute, $ext, '', $options); #starts with empty destination
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
		if (!empty($skip) && empty($jump)){
			$dproute['dpgraph']->node('reset', array(
				'label' => "   "._('Reset'),
				'tooltip' => _('Reset'),
				'shape' => 'larrow',
				'URL' => '#',
				'fontcolor' => '#555555',
				'fontsize' => '18pt',
				'fillcolor' => '#F0F0F0',
				'style' => 'filled'
			));
		}
		
		$gtext = $dproute['dpgraph']->render();
		$gtext=json_encode($gtext);


		$header='<h2><span id="headerSelected"></span></h2>';
		if ($datetime==1){$header.= "<h6>".date('Y-m-d H:i:s')."</h6>";}

		$header .= '
				<input type="hidden" id="processed" value="yes">
				<input type="hidden" id="ext" value="' . htmlspecialchars($ext, ENT_QUOTES) . '">
				<input type="hidden" id="jump" value="' . htmlspecialchars($jump, ENT_QUOTES) . '">
				<input type="hidden" id="skip" value=\'' . json_encode($skip) . '\'>
				<input type="hidden" id="panzoom" value="' . htmlspecialchars($panzoom, ENT_QUOTES) . '">
				
				<script>
					function updateHeaderSelected() {
						let name = sessionStorage.getItem("selectedName");
						const headerSelected = document.getElementById("headerSelected");
						if (headerSelected) {
							headerSelected.textContent = name || "'._('No name selected').'";
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
$minimal= isset($options['minimal']) ? $options['minimal'] : '1';
$stop=false; //reset on new call

if (!isset($route['parent_edge_code'])){$route['parent_edge_code']='';}
	
if ($minimal){
	$patterns = array(
    '/^play-system-recording/i',
    '/^from-did-direct/i',
    '/^qmember/i',
		'/^rgmember/i',
		'/^ext-local/i',
	);
	
	foreach ($patterns as $pattern) {
			if (preg_match($pattern, $destination)) {
					return;
			}
	}
}
 
	 
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
	
	//$optional = preg_match('/^[ANY_xX+\d\[\]]+$/', $optional) ? '' : $optional;
  if (! isset ($route['dpgraph'])) {
		
    $route['dpgraph'] = new Alom\Graphviz\Digraph('"reset'.$route['extension'].'"');
		//$route['dpgraph']->attr('graph',array('rankdir'=>$direction,'ordering'=>'in','tooltip'=>' ','ranksep'=>'.50 equally','nodesep'=>'.30'));		
		$route['dpgraph']->attr('graph',array('rankdir'=>$direction,'ordering'=>'in','tooltip'=>' '));
  }
	
  $dpgraph = $route['dpgraph'];
	
	
  dpplog(9, "destination='$destination' route[extension]: " . print_r($route['extension'], true));

  # This only happens on the first call.  Every recursive call includes
  # a destination to look at.  For the first one, we get the destination from
  # the route object.
	
  if ($destination == '') {
		
		$dpgraph->node("reset".$route['extension'], array(
			'label' => "   "._('Reset'),
			'tooltip' => _('Reset'),
			'shape' => 'larrow',
			'URL' => '#',
			'fontcolor' => '#555555',
			'fontsize' => '18pt',
			'fillcolor' => '#F0F0F0',
			'style' => 'filled'
		));
    // $graph->node() returns the graph, not the node, so we always
    // have to get() the node after adding to the graph if we want
    // to save it for something.
    // UPDATE: beginNode() creates a node and returns it instead of
    // returning the graph.  Similarly for edge() and beginEdge().
    $route['parent_node'] = $dpgraph->get("reset".$route['extension']);
    
		


    # One of thse should work to set the root node, but neither does.
    # See: https://rt.cpan.org/Public/Bug/Display.html?id=101437
    #$route->{parent_node}->set_attribute('root', 'true');
    #$dpgraph->set_attribute('root' => $route->{extension});
		
    // If an inbound route has no destination, we want to bail, otherwise recurse.
    if ($optional != '') {
			$route['parent_edge_label'] = ' ';
      dpp_follow_destinations($route, $optional,'',$options);
    }elseif ($route['destination'] != '') {
			$route['parent_edge_label'] = " "._('Always');
      dpp_follow_destinations($route, $route['destination'].','.$langOption,'',$options);
    }
    return;
  }
	
  dpplog(9, "Inspecting destination $destination");

	if ((preg_match("/^from-did-direct,(\d+),(\d+),(.+)/", $destination) && $options['hideall']==1)){
		return;
	}
	
  // We use get() to see if the node exists before creating it.  get() throws
  // an exception if the node does not exist so we have to catch it.
  try {
    $node = $dpgraph->get($destination);
		
  } catch (Exception $e) {
    dpplog(7, "Adding node: $destination");
    $node = $dpgraph->beginNode($destination);
		//$node->attribute('margin', '.25,.055');
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
			
			if (preg_match("/^(edgelink)/", $route['parent_edge_code'])){
				$edge->attribute('URL', $route['parent_edge_url']);
				$edge->attribute('target', $route['parent_edge_target']);
				if (isset($route['parent_edge_labeltooltip'])){
					$edge->attribute('labeltooltip',sanitizeLabels($route['parent_edge_labeltooltip']));
					$edge->attribute('edgetooltip',sanitizeLabels($route['parent_edge_labeltooltip']));
				}
				
				$route['parent_edge_code']='';
			}
			
			if (preg_match("/^( IVR Break| Queue Callback)./", $route['parent_edge_label'])){
				$edge->attribute('style', 'dashed');
			}
			if (preg_match("/^( Callback | Destination after)./", $route['parent_edge_label'])){
				$edge->attribute('style', 'dotted');
			}
			
			
			if (preg_match("/^(recording)/", $route['parent_edge_code'])){
				$edge->attribute('dir', 'back');
				$route['parent_edge_code']='';
			}
			
			//start from node
			if (preg_match("/^ +$/", $route['parent_edge_label'])){
				$edge->attribute('style', 'dotted');
			}
			
			//exclude paths
			if (in_array($destination,$options['skip'])){
				$stop=true;
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
		
			$label=sanitizeLabels($an['description'])."\n". _('Recording').": ".sanitizeLabels($announcement);
			$tooltip='';
			makeNode($module,$annum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($an['post_dest'] != '') {
				$route['parent_edge_label'] = " "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $an['post_dest'].','.$anlang,'',$options);
			}
			
			if (isset($route['recordings'][$recID])){
				$route['parent_edge_label']= " "._('Recording');
				$route['parent_edge_code']= 'recording';
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
		
		$translatedMap = array(
			'musiconhold' => _('Music On Hold'),
			'ring'        => _('Play Ringtones'),
			'no-service'  => _('Play No Service Message'),
			'busy'        => _('Busy'),
			'hangup'      => _('Hang Up'),
			'congestion'  => _('Congestion'),
			'zapateller'  => 'Zapateller',
		);
		
		$blackholeother = $matches[2];
		$previousURL=$route['parent_node']->getAttribute('URL', '');

		$node->attribute('label', _('Terminate Call').': '.$translatedMap[$blackholetype]);
		$node->attribute('tooltip', _('Terminate Call').': '.$translatedMap[$blackholetype]);
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
			$daynight = array_reverse($route['daynight'][$daynightnum]);
			//array_reverse($daynight);
			
			
			#feature code exist?
			if ( isset($route['featurecodes']['*28'.$daynightnum]) ){
				#custom feature code?
				if ($route['featurecodes']['*28'.$daynightnum]['customcode']!=''){$featurenum=$route['featurecodes']['*28'.$daynightnum]['customcode'];}else{$featurenum=$route['featurecodes']['*28'.$daynightnum]['defaultcode'];}
				#is it enabled?
				if ($route['featurecodes']['*28'.$daynightnum]['enabled']=='1'){$code=$featurenum;}else{$code=$featurenum." (disabled)";}
			}else{
				$code='';
			}
			
			#check current status and set path to active
			$C ='/usr/sbin/asterisk -rx "database show DAYNIGHT/C'.$daynightnum.'" | cut -d \':\' -f2 | tr -d \' \' | head -1';
			exec($C, $current_daynight);
			$dactive = $nactive = "";
			if ($current_daynight[0]=='DAY'){$dactive="("._('Active').")";}else{$nactive="("._('Active').")";}
			
			

			foreach ($daynight as $d){
				if (isset($d['dmode']) && $d['dmode'] == 'night' && !$stop) {
					$route['parent_edge_label'] = " "._('Night Mode') . ' '.$nactive;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $d['dest'].','.$daynightLang,'',$options);
				}elseif (isset($d['dmode']) && $d['dmode'] == 'day' && !$stop) {
					$route['parent_edge_label'] = " "._('Day Mode') . ' '.$dactive;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $d['dest'].','.$daynightLang,'',$options);
				}elseif ($d['dmode']=="fc_description"){
					$label=sanitizeLabels($d['dest']) ."\n"._('Feature Code').": ".$code;
				}
			}
			$tooltip='';
			makeNode($module,$daynightnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Flow Control (daynight)

		#
		# Callback
		#
  } elseif (preg_match("/^callback,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Callback";
		$callbackId = $matches[1];
		$callrecOther = $matches[2];
		$callbackLang= $matches[3];
		if (isset($route['callback'][$callbackId])){
			$callback = $route['callback'][$callbackId];
			
			$label=sanitizeLabels($callback['description']);
			$tooltip=sanitizeLabels($callback['description'])."\n"._('Callback Number').": ".$callback['callbacknum']."\n"._('Delay Before Callback').": ".$callback['sleep']."\n"._('Caller ID').": ".$callback['callerid']."\n"._('Timeout').": ".$callback['timeout'];
			
			
			makeNode($module,$callbackId,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			$route['parent_edge_label']= _('Destination after Callback');
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $callback['destination'].','.$callbackLang,'',$options);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Recording
		#
		
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
			$callMode = str_replace("Dontcare", _('Don\'t Care'), $callMode);
			$label=sanitizeLabels($callRec['description'])."\n"._('Mode').": ".$callMode;
			$tooltip='';
			
			makeNode($module,$callrecID,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			$route['parent_edge_label']= " "._('Continue');
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
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($directory['invalid_destination']!=''){
				 $route['parent_edge_label']= " "._('Invalid Input');
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
			
			$label=sanitizeLabels($dynrt['name'])."\n"._('Announcement').": ".sanitizeLabels($announcement);
			$tooltip='';
			makeNode($module,$dynnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if (!empty($dynrt['routes'])){
				ksort($dynrt['routes']);
				foreach ($dynrt['routes'] as $selid => $ent) {
					$desc = isset($ent['description']) ? $ent['description'] : '';
					
					$route['parent_edge_label']= " "._('Match').": ".sanitizeLabels($ent['selection'])."\n".$desc;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ent['dest'].','.$dynLang,'',$options);
				}
			}
			
			if (isset($route['recordings'][$recID])){
				$route['parent_node'] = $node;
				$route['parent_edge_label']= " "._('Recording');
				$route['parent_edge_code']= 'recording';
				dpp_follow_destinations($route, 'play-system-recording,'.$recordingId.',1,'.$dynLang,'',$options);
				
			}
			
			//are the invalid and default destinations the same?
			if ($dynrt['invalid_dest'] != '' && $dynrt['invalid_dest']==$dynrt['default_dest']){
				 $route['parent_edge_label']= " ".sprintf(_('Invalid Input, Default (%s) secs'), $dynrt['timeout']);
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $dynrt['invalid_dest'].','.$dynLang,'',$options);
			}else{
				if ($dynrt['invalid_dest'] != '') {
					$route['parent_node'] = $node;
					$route['parent_edge_label']= " "._('Invalid Input');
					dpp_follow_destinations($route, $dynrt['invalid_dest'].','.$dynLang,'',$options);
				}
				if ($dynrt['default_dest'] != '') {
					$route['parent_node'] = $node;
					$route['parent_edge_label']= " ". sprintf(_('Default (%s) secs'), $dynrt['timeout']);
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
  } elseif (preg_match("/^from-did-direct,([#\d]+),(\d+),(.+)/", $destination, $matches)) {
	
		$extnum = $matches[1];
		$extLang= $matches[3];
		
		if (isset($route['extensions'][$extnum])){
			
			$extension = $route['extensions'][$extnum];
			$extname= $extension['name'];
			
			if ($extension['tech']=='pjsip' || $extension['tech']=='sip' || $extension['tech']=='iax2'){
				
				$node->attribute('penwidth', '2');
				if (!isset($route['extensions'][$extnum]['reg_status'])){
					$status=isExtensionRegistered($extnum,$extension['tech']);
					$route['extensions'][$extnum]['reg_status']=$status;
				}
				$registered=$route['extensions'][$extnum]['reg_status'];
				if ($registered){$online=_('Yes');}else{$online=_('No');}
				
				$node->attribute('color', $registered ? 'green' : 'red');
				
				$regStatus="\n\n"._('Registration').": ".$online;
				$regStatus.="\n"._('Tech').": ".strtoupper($extension['tech']);
			}else{
				$regStatus="\n\n"._('Tech').": ".ucfirst($extension['tech']);
			}
			
			if (isset($extension['mailbox'])){
				$extemail= $extension['mailbox']['email'];
				$extemail= str_replace(",",",\n",$extemail);
				
				$mailboxLabel="\n\n"._('Voicemail: Enabled');
				$mailboxLabel.="\n"._('Email').": ".$extemail;
				foreach ($extension['mailbox']['options'] as $m=>$mm){
					$mailboxLabel.="\n".ucfirst($m).": ".ucfirst($mm);
				}
				
			}else{
				$extemail='';
				$mailboxLabel="\n\n"._('Voicemail: Disabled');
			}
			
			if (isset($extension['fmfm'])){
				if ($extension['fmfm']['ddial']=='DIRECT'){
					$confirm = ($extension['fmfm']['needsconf'] == 'CHECKED') ? _('Yes') : _('No');
					$fmfmLabel="\n\nFMFM: "._('Enabled')."\n"._('Initial Ring Time').": ".secondsToTimes($extension['fmfm']['pre_ring'])."\n"._('Ring Time').": ".secondsToTimes($extension['fmfm']['grptime'])."\n"._('Follow-Me List').": ".$extension['fmfm']['grplist']."\n"._('Confirm Calls').": ".$confirm;
				}else{
					$fmfmLabel="\n\nFMFM:"._('Disabled');
				}
			}else{
				$fmfmLabel='';
			}

			$node->attribute('label', _('Extension').": ".$extnum." ".sanitizeLabels($extname)."\n".sanitizeLabels($extemail));
			$node->attribute('tooltip', _('Extension').": ".$extnum."\n"._('Name').": ".sanitizeLabels($extname).$regStatus.$mailboxLabel.$fmfmLabel);
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$extnum));
			$node->attribute('target', '_blank');
			
			if (isset($extension['fmfm']) && $fmfmOption){
				if ($extension['fmfm']['ddial']=='DIRECT'){
						$grplist = preg_split("/-/", $extension['fmfm']['grplist']);
						foreach ($grplist as $g){
							$g=trim($g);
							//$g=str_replace('#', '', $g);
							$follow='from-did-direct,'.$g.',1,'.$extLang;
							
							$route['parent_edge_label'] = ' FMFM ('.secondsToTimes($extension['fmfm']['pre_ring']).')';
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $follow,'',$options);
						}
						
						if (isset($extension['fmfm']['postdest']) && $extension['fmfm']['postdest']!='ext-local,'.$extnum.',dest'){
							$route['parent_edge_label'] = " FMFM "._('No Answer');
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
					$route['parent_edge_label'] = " "._('No Answer, Busy, Not Reachable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
			} elseif (
					$extension['noanswer_dest'] === $extension['busy_dest']
					&& $extension['chanunavail_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Busy are the same, but Not Reachable is different
					$route['parent_edge_label'] = " "._('No Answer & Busy');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
				}
					//Not Reachable
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = " "._('Not Reachable');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'].','.$extLang,'',$options);
					}
			} elseif (
					$extension['noanswer_dest'] === $extension['chanunavail_dest']
					&& $extension['busy_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Not Reachable are the same
					$route['parent_edge_label'] = " "._('No Answer & Not Reachable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
				}
					//Busy
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = " "._('Busy');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
					}
			} elseif (
					$extension['busy_dest'] === $extension['chanunavail_dest']
					&& $extension['noanswer_dest'] !== $extension['busy_dest']
			) {
				if (!empty($extension['busy_dest'])) {
					// Busy and Not Reachable are the same
					$route['parent_edge_label'] = " "._('Busy & Not Reachable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
				}
					//No Answer
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = " "._('No Answer');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
					}
			} else {
					// All are different
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = " "._('No Answer');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
					}
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = " "._('Busy');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
					}
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = " "._('Not Reachable');
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
  } elseif (preg_match("/^from-trunk,((?:[^\[,]+(?:\[[^\]]+\])?))\-([^,]*),(\d+),(.+)/", $destination, $matches)) {
		
		$num = $matches[1];
		$numcid = $matches[2];
		$numLang= $matches[4];		
	
		$allowCheck = 0;
		$checkAModule=\FreePBX::Modules()->checkStatus("allowlist");
		if ($checkAModule){
			if ($num=='ANY'){$allowNum='';}else{$allowNum=$num;}
			$allowCheck = \FreePBX::Allowlist()->didIsSet($allowNum, $numcid);
			$allowList = \FreePBX::Allowlist()->getAllowlist();
		}

		$blackCheck=\FreePBX::Modules()->checkStatus("blacklist");
		
		if (empty($num)){$num='ANY';}
		if ($numcid==''){$numcidd=" / ANY";}else{$numcidd=" / ".$numcid;}
		
		$incoming = $route['incoming'][$num.$numcid];
		if (isset($incoming['language'])){$numLang=$incoming['language'];}
		
		$didLabel = ($num == "ANY") ? "ANY" : formatPhoneNumbers($num);
		$didLabel.= $numcidd."\n".$incoming['description'];
		if ($num=='ANY'){
			$didLink='/';
		}else{
			$didLink=$num.'/'.$numcid;
		}
		
		$didTooltip=$num.$numcidd."\n";
		$didTooltip.= !empty($incoming['cidnum']) ? _('Caller ID Number').": " . $incoming['cidnum']."\n" : "";
		$didTooltip.= !empty($incoming['description']) ? _('Description').": " . $incoming['description']."\n" : "";
		$didTooltip.= !empty($incoming['alertinfo']) ? _('Alert Info').": " . $incoming['alertinfo']."\n" : "";
		$didTooltip.= !empty($incoming['grppre']) ? _('CID Prefix').": " . $incoming['grppre']."\n" : "";
		$didTooltip.= !empty($incoming['mohclass']) ? _('Music on hold class').": " . $incoming['mohclass']."\n" : "";
		$didTooltip.= !empty($incoming['language']) ? _('Language').": " . $incoming['language']."\n" : "";
		
		$node->attribute('label', sanitizeLabels($didLabel));
		$node->attribute('tooltip',sanitizeLabels($didTooltip));
		$node->attribute('width', 2);
    $node->attribute('margin','.13');
		$node->attribute('URL', htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)));
		$node->attribute('target', '_blank');
		$node->attribute('shape', 'cds');
		$node->attribute('fillcolor', 'darkseagreen');
		$node->attribute('style', 'filled');
		if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
		
		if ($blackCheck && !$minimal){
			$blackList = \FreePBX::Blacklist()->getBlacklist();
			$total=count($blackList);
			if ($total > 1){
				$blackDest = \FreePBX::Blacklist()->destinationGet();
				$blockUnknown = \FreePBX::Blacklist()->blockunknownGet();
				$block = $blockUnknown ? _('Yes') : _('No');
				$tooltip="\n"._('Block Unknown/Blocked Caller ID').": ".$block;
				$tooltip.="\n\n"._('Number: Description')."\n";
				
				$i=0;
				
				foreach ($blackList as $b){
					if ($b['number']=='dest'){continue;}
					if ($b['description']==1){$b['description']='';}
					$tooltip.=$b['number'].": ".sanitizeLabels($b['description'])."\n";
					
					if ($i >= 25 && $i < $total - 1){
						$tooltip.="...\n". ($total - 25) ." "._('additional entries');
						break;
					}
					$i++;
				}
				
				$edgeLabel=" "._('Blacklist');
				$route['parent_edge_code']='edgelink';
				$route['parent_edge_label'] = " "._('Disallowed by Blacklist');
				$route['parent_edge_url'] = htmlentities('/admin/config.php?display=blacklist');
				$route['parent_edge_target'] = '_blank';
				$route['parent_edge_labeltooltip']=" "._('Click to edit Blacklist')."\n".$tooltip;
				$route['parent_node'] = $node;
				if ($blackDest){
					dpp_follow_destinations($route, $blackDest.','.$numLang,'',$options);
				}else{
					dpp_follow_destinations($route, 'blacklistnotset','',$options);
				}
			}																															
		}
		
		if ($allowCheck && !empty($allowList)){
			$allowDest = \FreePBX::Allowlist()->destinationGet();

			$tooltip="\n"._('Number: Description')."\n";
			$i=0;
			$total=count($allowList);
			foreach ($allowList as $a){
				if ($a['description']==1){$a['description']='';}
				$tooltip.=$a['number'].": ".sanitizeLabels($a['description'])."\n";
				
				if ($i >= 25 && $i < $total - 1){
					$tooltip.="...\n ". ($total - 25) ." "._('additional entries');
					break;
				}
				$i++;
			}
				
				
			$edgeLabel=" "._('Allowlist');
			$route['parent_edge_code']='edgelink';
			$route['parent_edge_label'] =" "._('Disallowed by Allowlist');
			$route['parent_edge_url'] = htmlentities('/admin/config.php?display=allowlist');
			$route['parent_edge_target'] = '_blank';
			$route['parent_edge_labeltooltip']=" "._('Click to edit Allowlist')."\n";
			
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $allowDest.','.$numLang,'',$options);
		
		}else{
			$edgeLabel=" "._('Always');
		}
		
		if ($allowCheck && !empty($allowList)){
			$route['parent_edge_code']='edgelink';
			$route['parent_edge_url'] = htmlentities('/admin/config.php?display=allowlist');
			$route['parent_edge_target'] = '_blank';
			$route['parent_edge_labeltooltip']=" "._('Click to edit Allowlist')."\n".$tooltip;
		}
		
		$route['parent_edge_label']= $edgeLabel;
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
			
			$label=sanitizeLabels($ivr['name'])."\n"._('Announcement').": ".sanitizeLabels($ivrRecName);
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
			$tooltip="IVR "._('DTMF Options')."\n"._('Enable Direct Dial').": ".$ddial."\n"._('Timeout').": ".secondsToTimes($ivr['timeout_time'])."\n"._('Invalid Retries').": ".$ivr['invalid_loops']."\n"._('Invalid Retry Recording').": ".findRecording($route,$ivr['invalid_retry_recording'])."\n"._('Invalid Recording').": ".findRecording($route,$ivr['invalid_recording'])."\n"._('Timeout Retries').": ".$ivr['timeout_loops']."\n"._('Timeout Retry Recording').": ".findRecording($route,$ivr['timeout_retry_recording'])."\n"._('Timeout Recording').": ".findRecording($route,$ivr['timeout_recording'])."\n"._('Return to IVR after VM').": ".$retvm."\n";
			
			makeNode($module,$inum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			# The destinations we need to follow are the invalid_destination,
			# timeout_destination, and the selection targets
			if (isset($route['recordings'][$recID])){
				$route['parent_node'] = $node;
				$route['parent_edge_label']=" "._('Recording');
				$route['parent_edge_code']= 'recording';
				dpp_follow_destinations($route, 'play-system-recording,'.$recordingId.',1,'.$ilang,'',$options);
				
			}
			
			#now go through the selections
			if (!empty($ivr['entries'])){
				ksort($ivr['entries']);
				foreach ($ivr['entries'] as $selid => $ent) {
					
					$route['parent_edge_label']= " ".sprintf(_('Selection %s'), $ent['selection']);
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ent['dest'].','.$ilang,'',$options);
				}
			}
			
			#are the invalid and timeout destinations the same?
			if ($ivr['invalid_destination']==$ivr['timeout_destination']){
				if (!empty($ivr['invalid_destination'])){
					$route['parent_edge_label']= " ".sprintf(_('Invalid Input, Timeout (%s secs)'), $ivr['timeout_time']);
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ivr['invalid_destination'].','.$ilang,'',$options);
				}
			}else{
					if ($ivr['invalid_destination'] != '') {
						$route['parent_edge_label']= " "._('Invalid Input');
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $ivr['invalid_destination'].','.$ilang,'',$options);
					}
					if ($ivr['timeout_destination'] != '') {
						$route['parent_edge_label']= " ".sprintf(_('Timeout (%s secs)'), $ivr['timeout_time']);
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
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			if ($langArray['dest'] != '') {
				$route['parent_edge_label'] =" "._('Continue');
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
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($miscapps['dest'] != '') {
				$route['parent_edge_label'] =" "._('Continue');
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
		# Page Group
		#
  } elseif (preg_match("/^app-pagegroups,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Paging";
		$pagenum = $matches[1];
		$pageLang = $matches[3];
		
		if (isset($route['paging'][$pagenum])){
			$paging=$route['paging'][$pagenum];
			$audioID=$paging['announcement'];
			if (!is_numeric($audioID)){$announcement=_('Announcement').": ".$audioID."\n";}else{$announcement='';}
			$busyArray=array('Skip','Force','Whisper');
			$duplexArray=array('No','Yes');
			$label=$paging['page_group']." ".sanitizeLabels($paging['description']);
			$tooltip=_('Page Group').": ".$paging['page_group']."\n"._('Description').": ".sanitizeLabels($paging['description'])."\n".$announcement._('Busy Extensions').": ".$busyArray[$paging['force_page']]."\n"._('Duplex').": ".$duplexArray[$paging['duplex']];
			makeNode($module,$pagenum,$label,$tooltip,$node);
			
			if (!empty($paging['members']) && !$minimal){
				$line="Page Group ".$pagenum." "._('members').":\n";
				foreach ($paging['members'] as $member) {
					if (isset($route['extensions'][$member])){
						$line.="Ext ".$member." ".sanitizeLabels($route['extensions'][$member]['name'])."\l";
					}
				}
				
				$memNode= $dpgraph->beginNode('pagemem'.$pagenum,
					array(
						'label' => $line,
						'tooltip' => $node->getAttribute('label'),
						'URL' => $node->getAttribute('URL', ''),
						'target' => '_blank',
						'shape' => 'rect',
						'style' => 'filled',
						'fillcolor' => $pastels[16]
					)
				);
				$edge= $dpgraph->beginEdge(array($node, $memNode));
			}
			
			if (isset($route['recordings'][$audioID])){
				$route['parent_node'] = $node;
				$route['parent_edge_label']= " "._('Recording');
				$route['parent_edge_code']= 'recording';
				dpp_follow_destinations($route, 'play-system-recording,'.$audioID.',1,'.$pageLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Page Group
		
		#
		# Phonebook
		#
  } elseif (preg_match("/^app-pbdirectory,pbdirectory,1,(.+)/", $destination, $matches)) {
		$module="Phonebook";
		$label="Asterisk";
		$tooltip="";
		makeNode($module,'',$label,$tooltip,$node);
		#end of Phonebook
		
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
		
		#feature code exist?
		if ( isset($route['featurecodes']['*29'.$recID]) ){
			#custom feature code?
			if ($route['featurecodes']['*29'.$recID]['customcode']!=''){$featurenum=$route['featurecodes']['*29'.$recID]['customcode'];}else{$featurenum=$route['featurecodes']['*29'.$recID]['defaultcode'];}
			#is it enabled?
			if ( ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') ){$recEnabled=$featurenum;}else{$recEnabled=$featurenum.' ('._('Disabled').')';}
		}else{
			$recEnabled=_('Disabled');
		}
		
		$label = _('Recording') . ' (' . $recLang . '): ' . sanitizeLabels($playName) . "\n" .
         _('Feature Code') . ': ' . $recEnabled;

		$node->attribute('label', $label);
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
			$label=sanitizeLabels($queueprio['description']."\n"._('Priority').": ".$queueprio['queue_priority']);
			$tooltip='';
			makeNode($module,$queueprioID,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($queueprio['dest'] != '') {
				$route['parent_edge_label'] =" "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $queueprio['dest'].','.$queuepriorLang, '',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Queue Priorities
		
		#
		# Queues and Virtual Queues
		#
  } elseif (preg_match("/^(ext-v?queues),(\d+),(\d+),(.+)/", $destination, $matches)) {
		$queueType= $matches[1];
    $num = $matches[2];
    $qother = $matches[3];
		$qlang= $matches[4];
		
		$label = $tooltip = '';
		
		if ($queueType=='ext-vqueues'){
			
			$module="Virtual Queues";
			$vqnum=$num;
			if (isset($route['vqueues'][$vqnum])){
				
				$vq= $route['vqueues'][$vqnum];

				$tooltipitems='';
				$label=sanitizeLabels($vq['name']) ."\n";
				if (!empty($vq['cidpp'])){$tooltipitems.=_('CID Prefix').": ".sanitizeLabels($vq['cidpp'])."\n";}
				if (!empty($vq['alertinfo'])){$tooltipitems.=_('Alert Info').": ".sanitizeLabels($vq['alertinfo'])."\n";}
				if (!empty($vq['music'])){$tooltipitems.=_('Music on hold Class').": ".sanitizeLabels($vq['music'])."\n";}
				if (!empty($vq['language'])){$tooltipitems.=_('Language').": ".sanitizeLabels($vq['language'])."\n";$qlang=$vq['language'];}
				
				$tooltip=$label."\n".$tooltipitems."\n";
				
				if ($vq['gotodest'] != '') {
					if (preg_match("/^ext-queues,(\d+),(\d+)/", $vq['gotodest'], $matches)) {
						$qnum=$matches[1];
						$failover=$vq['dest'];
					}else{
						makeNode($module,$vqnum,$label,$tooltip,$node);
						if ($stop){
							$undoNode= stopNode($dpgraph,$destination);
							$edge= $dpgraph->beginEdge(array($node, $undoNode));
							$edge->attribute('style', 'dashed');
							$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
							
							return;
						}
						$route['parent_edge_label'] = " "._('Continue');
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $vq['gotodest'].','.$qlang,'',$options);
						return;
					}
				}
				
				if (!empty($vq['cdest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Caller Post Hangup');
					dpp_follow_destinations($route, $vq['cdest'].','.$qlang,'',$options);
				}
				if (!empty($vq['adest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Agent Post Hangup');
					dpp_follow_destinations($route, $vq['adest'].','.$qlang,'',$options);
				}
				if (!empty($vq['full_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on FULL');
					dpp_follow_destinations($route, $vq['full_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINEMPTY');
					dpp_follow_destinations($route, $vq['joinempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEEMPTY');
					dpp_follow_destinations($route, $vq['leaveempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINUNAVAIL');
					dpp_follow_destinations($route, $vq['joinunavail_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEUNAVAIL');
					dpp_follow_destinations($route, $vq['leaveunavail_dest'].','.$qlang,'',$options);
				}
			}else{
				notFound($module,$destination,$node);
				return;
			}
			
		}else{
			$module="Queues";
			$qnum=$num;
		}
		
		
		if (isset($route['queues'][$qnum])){
			$q = $route['queues'][$qnum];
			
			
			//is the parent a virtual queue?
			if ($queueType=='ext-vqueues'){
				$label.="Queues: ";
				$vq=$route['vqueues'][$vqnum];
				$cidPrefix = $vq['cidpp'] != '' ? $vq['cidpp'] : $q['grppre'];
				if ($vq['music'] !=''){$music=$vq['music'];}elseif (isset($q['data']['music'])){$music=$q['data']['music'];}else{$music='inherit';}
				if (!empty($vq['language'])){$qlang=$vq['language'];}
				if ($vq['maxwait'] !=='-1'){$maxwait=$vq['maxwait'];}else{$maxwait=$q['maxwait'];}

				if ($vq['dest'] !=''){$failover=$vq['dest'];}else{$failover=$q['dest'];}
				
			}else{
				$cidPrefix=$q['grppre'];
				$maxwait=$q['maxwait'];
				if (isset($q['data']['music'])){$music=$q['data']['music'];}else{$music='inherit';}
				
				$failover=$q['dest'];
			}
				
			if ($maxwait == 0 || $maxwait == '' || !is_numeric($maxwait)) {
				$maxwait = _('Unlimited');
			} else {
				$maxwait = secondsToTimes($maxwait);
			}
			
			$label.=$qnum . " " . sanitizeLabels($q['descr']);
			$restrict=array('Call as Dialed','No Follow-Me or Call Forward','Extensions Only');
			$skipbusy=array('No','Yes','Yes + (ringinuse=no)','Queue calls only (ringinuse=no)');
			$mohclass=array('MoH Only','Ring Only','Agent Ringing');
			$noyes=array('No','Yes');
			$maxcallers = ($q['data']['maxlen'] == 0) ? _('Unlimited') : $q['data']['maxlen'];
			
			if ($q['data']['announce-frequency']==0){
				$position="["._('Caller Position')."]\n"._('Disabled')."\n\n";
			}else{
				$position="["._('Caller Position')."]\n"._('Frequency').": ".secondsToTimes($q['data']['announce-frequency'])."\n"._('Minimum Announcement Interval').": ".secondsToTimes($q['data']['min-announce-frequency'])."\n"._('Announce Position').": ".ucfirst($q['data']['announce-position'])."\n"._('Announce Hold Time').": ".ucfirst($q['data']['announce-holdtime'])."\n\n";
			}

			if ($q['data']['periodic-announce-frequency']==0){$repeat='Disabled';$edgeRepeat='';}else{$repeat=secondsToTimes($q['data']['periodic-announce-frequency']);$edgeRepeat=" (every ".$repeat.")";}
			if ($q['ivr_id']!='none'){
				$breakoutname = isset($route['ivrs'][$q['ivr_id']]['name']) ? $route['ivrs'][$q['ivr_id']]['name'] : "none";
				$periodic="["._('Periodic Announcements')."]\nIVR Break Out Menu: ".$breakoutname."\n"._('Repeat Frequency').": ".$repeat;
			}elseif (isset($q['callback_id']) && $q['callback_id']!='none'){
				$breakoutname = isset($route['queuecallback'][$q['callback_id']]) ? $route['queuecallback'][$q['callback_id']]['name'] : "none";
				$periodic="["._('Periodic Announcements')."\n"._('Queue Callback').": ".$breakoutname."\n"._('Repeat Frequency').": ".$repeat;
			}else{
				$periodic="["._('Periodic Announcements')."]\n"._('Disabled')."\n";
			}
			
			
			$tooltip="["._('General Settings')."]\n"._('CID Prefix').": ".$cidPrefix."\n"._('Strategy').": ".$q['data']['strategy']."\n"._('Agent Restrictions').": ".$restrict[$q['use_queue_context']]."\n"._('Autofill').": ".ucfirst($q['data']['autofill'])."\n"._('Skip Busy Agents').": ".$skipbusy[$q['cwignore']]."\n"._('Music On Hold Class').": ".$music." (".$mohclass[$q['ringing']].")\n"._('Call Recording').": ".$q['data']['recording']."\n"._('Mark calls answered elsewhere').": ".$noyes[$q['data']['answered_elsewhere']].
			"\n\n["._('Timing & Agent Options')."]\n"._('Max Wait Time').": ".$maxwait."\n"._('Agent Timeout').": ".secondsToTimes($q['data']['timeout'])."\n"._('Agent Retry').": ".secondsToTimes($q['data']['retry'])."\n"._('Wrap Up Time').": ".secondsToTimes($q['data']['wrapuptime']).
			"\n\n["._('Capacity Options')."]\n"._('Max Callers').": ".$maxcallers."\n"._('Join Empty').": ".ucfirst($q['data']['joinempty'])."\n"._('Leave Empty').": ".ucfirst($q['data']['leavewhenempty']).
			"\n\n".$position.$periodic;
			makeNode($module,$num,$label,$tooltip,$node);
			
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
				$route['parent_edge_label'] = " ".sprintf(_('No Answer (%s)'), $maxwait);
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $failover.','.$qlang,'',$options);
			
			
			if (!empty($q['members'])){
				if ($options['queue_member_display']==1){ //--option "Single"
					foreach ($q['members'] as $types=>$type) {
						foreach ($type as $member){
							$route['parent_node'] = $node;
							$route['parent_edge_label'] = " "._('Static');
							if ($types=='static'){
								$route['parent_edge_label'] = " "._('Static');
								$route['parent_edge_code'] = 'static';
							}else{
								$route['parent_edge_label'] = " "._('Dynamic');
								$route['parent_edge_code'] = 'dynamic';
							}
							
							switch ($combineQueueRing) {
								case "2":
									//$splitPen= explode(',', $member);
									//$member=$splitPen[0];
									$go="from-did-direct,$member,1,$qlang";
									break;
								default:
									$go="qmember$member";
							}
							
							dpp_follow_destinations($route, $go,'',$options);
						}
					}					

				}elseif ($options['queue_member_display']==2){ //--option "Combine"
					$line=_('Queue')." ".$qnum." "._('Agents').":\n";
					foreach ($q['members'] as $types=>$type) {
						
						if ($types=='static' && !empty($q['members']['static'])){
							$line.="["._('Static')."]\n";
						}elseif($types=='dynamic' && !empty($q['members']['dynamic']) && $dynmembers){
							$line.="["._('Dynamic')."]\n";
						}
						
						/*table view with registrations?
						foreach ($type as $member) {
							$split=explode(',',$member);
							$member=$split[0];
							$pen=$split[1];
							
							if (isset($route['extensions'][$member])) {
									if (!isset($route['extensions'][$member]['reg_status'])) {
											$status = isExtensionRegistered($member, $route['extensions'][$member]['tech']);
											$route['extensions'][$member]['reg_status'] = $status;
									}

									$isRegistered = $route['extensions'][$member]['reg_status'];
									$fontcolor = $isRegistered ? 'green' : 'red';

									$reg = "Ext {$member} {$route['extensions'][$member]['name']},{$pen}";
									$line .= '<font color="' . $fontcolor . '">' . htmlspecialchars($reg) . '</font><br align="left"/>';
									
							} else {
									$line .= $member.','.$pen.'<br align="left"/>';
							}
						}
						*/
						
						
						foreach ($type as $member){
							
							if (isset($route['extensions'][$member])){
								$line.="Ext ".$member." ".sanitizeLabels($route['extensions'][$member]['name'])."\l";
							}else{
								$line.=$member."\l";
							}
							
						}
						
					}
					//$finalLabel = new Alom\Graphviz\RawText('<<' . $line . '>>');
					$memNode= $dpgraph->beginNode('queuemembers'.$qnum,
						array(
							'label' => $line,
							'tooltip' => $line,
							'URL' => $node->getAttribute('URL', ''),
							'target' => '_blank',
							'shape' => 'rect',
							'style' => 'filled',
							'fillcolor' => $pastels[20]
						)
					);
					$edge= $dpgraph->beginEdge(array($node, $memNode));
					$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
					
				}else{
					//do not display agents --option "Hide"
				}
			}
			
			#Queue Plus Options
			if (!empty($q['vqplus'])){
				//$vq=$q['vqplus'];
				if (!empty($vq['cdest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Caller Post Hangup');
					dpp_follow_destinations($route, $vq['cdest'].','.$qlang,'',$options);
				}
				if (!empty($vq['adest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Agent Post Hangup');
					dpp_follow_destinations($route, $vq['adest'].','.$qlang,'',$options);
				}
				if (!empty($vq['full_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on FULL');
					dpp_follow_destinations($route, $vq['full_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINEMPTY');
					dpp_follow_destinations($route, $vq['joinempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEEMPTY');
					dpp_follow_destinations($route, $vq['leaveempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINUNAVAIL');
					dpp_follow_destinations($route, $vq['joinunavail_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEUNAVAIL');
					dpp_follow_destinations($route, $vq['leaveunavail_dest'].','.$qlang,'',$options);
				}
			}
			
			#Breakout Menus
			if (is_numeric($q['ivr_id'])){
				if (isset($route['ivrs'][$q['ivr_id']])){
					$route['parent_edge_label'] = " IVR Break Out".$edgeRepeat;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, 'ivr-'.$q['ivr_id'].',s,1,'.$qlang,'',$options);
				}else{
					notFound('IVR Break Out',$destination,$node);
				}
			}
			if (isset($q['callback_id']) && is_numeric($q['callback_id'])){
				if (isset($route['queuecallback'][$q['callback_id']])){
					$callback=$route['queuecallback'][$q['callback_id']];
					
					$route['parent_edge_label'] = " Queue Callback ".$callback['cbstarttime']." - ".$callback['cbendtime']."\l".$edgeRepeat;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, 'queuecallback-'.$q['callback_id'].',request,1,'.$qlang,'',$options);
				}else{
					notFound('Queue Callback',$destination,$node);
				}
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
		//$qpen=$matches[2];
		$qlabel = isset($route['extensions'][$qextension]['name']) ? _('Ext')." ".$qextension."\n".$route['extensions'][$qextension]['name'] : $qextension;
		$tooltip= isset($route['extensions'][$qextension]['name']) ? _('Extension').": ".$qextension."\n"._('Name').": ".$route['extensions'][$qextension]['name'] : $qextension;
		//registration status
		if (isset($route['extensions'][$qextension]) && ($route['extensions'][$qextension]['tech']=='pjsip' || $route['extensions'][$qextension]['tech']=='sip' || $route['extensions'][$qextension]['tech']=='iax2')){
			$node->attribute('penwidth', '2');
			if (!isset($route['extensions'][$qextension]['reg_status'])){
				$status=isExtensionRegistered($qextension,$route['extensions'][$qextension]['tech']);
				$route['extensions'][$qextension]['reg_status']=$status;
			}
			$registered=$route['extensions'][$qextension]['reg_status'];
			if ($registered){$online=_('Yes');}else{$online=_('No');}
			$node->attribute('color', $registered ? 'green' : 'red');

			$tooltip.="\n\n"._('Registration').": ".$online;
			$tooltip.="\n"._('Tech').": ".strtoupper($route['extensions'][$qextension]['tech']);
		}elseif(isset($route['extensions'][$qextension])){
			$tooltip.="\n\n"._('Tech').": ".ucfirst($route['extensions'][$qextension]['tech']);
		}
		
		$node->attribute('label', sanitizeLabels($qlabel));
		$node->attribute('tooltip', sanitizeLabels($tooltip));
		$node->attribute('style', 'filled');
		if (!is_numeric($qlabel)){
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$qextension));
			$node->attribute('target', '_blank');
		}
		
		if ($route['parent_edge_code'] == 'static') {
			$node->attribute('fillcolor', $pastels[20]);
		}else{
			$node->attribute('fillcolor', $pastels[8]);
		}
		$route['parent_edge_code']='';
		#end of Queue members (static and dynamic)
		
		#
		# Queue Callback
		#
	} elseif (preg_match("/^queuecallback-(\d+),(.+),(\d+),(.+)/", $destination, $matches)) {
		$module="Queue Callback";
		$qcallbackId=$matches[1];
		$qcallbackLang=$matches[4];
 
		if (isset($route['queuecallback'][$qcallbackId])){
			$module="Queue Callback";
			$qcallback=$route['queuecallback'][$qcallbackId];
			
			if (empty($qcallback['cbqueue'])){
				$queue= $route['parent_node']->getId();
			}elseif (substr($qcallback['cbqueue'], 0, 1) === 'q') {
				$queue = "ext-queues,".substr($qcallback['cbqueue'], 1).",1,".$qcallbackLang;
			}else{
				$queue = "ext-vqueues,".substr($qcallback['cbqueue'], 1).",1,".$qcallbackLang;
			}
			
			if (isset($route['recordings'][$qcallback['announcement']])){
				$qcbrecording= $route['recordings'][$qcallback['announcement']];
				$qcbRecName= $qcbrecording['displayname'];
				$recordingId=$qcbrecording['id'];
				
			}else{
				$qcbRecName="Default";
			}
			
			$label=sanitizeLabels($qcallback['name'])."\n"._('Announcement').": ".sanitizeLabels($qcbRecName);
			$tooltip = "Caller ID: ".$qcallback['cid']."\n"._('Timeout').": ".secondsToTimes($qcallback['timeout'])."\n"._('Retries').": ".$qcallback['retries']."\n"._('Retry Delay').": ".secondsToTimes($qcallback['retrydelay']);
			
			makeNode($module,$qcallbackId,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($qcallback['announcement']!=''){
				$route['parent_node'] = $node;
				$route['parent_edge_label'] = " "._('Announcement');
				dpp_follow_destinations($route,'play-system-recording,'.$recordingId.',1,'.$qcallbackLang,'',$options);
			}
			
			$route['parent_node'] = $node;
			$route['parent_edge_label'] = " "._('Callback Queue');
			dpp_follow_destinations($route,$queue,'',$options);
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of Queue Callback

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
			$tooltip="Strategy: ".$rg['strategy']."\n"._('Ring Time').": ".secondsToTimes($rg['grptime'])."\n"._('Music On Hold').": ".$rg['ringing']."\n"._('CID Prefix').": ".$rg['grppre']."\n"._('Confirm Calls').": ".$conf."\n"._('Call Recording').": ".$rg['recording']."\n";
			makeNode($module,$rgnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}

			$grplist=$rg['grplist'];
			$grplist = preg_split("/-/", $grplist);
			
			if ($options['ring_member_display']==1){
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
									$go="rgmember$member";
					}
					dpp_follow_destinations($route,$go,'',$options);
				} 
			}elseif ($options['ring_member_display']==2){
				$line=_('Ring Group')." ".$rgnum." "._('List').":\n";
				foreach ($grplist as $member){
					if (isset($route['extensions'][$member])){
						$line.=_('Ext')." ".$member." ".$route['extensions'][$member]['name']."\l";
					}else{
						$line.=$member."\l";
					}
				}
				
				$memNode= $dpgraph->beginNode('ringmembers'.$rgnum,
					array(
						'label' => $line,
						'tooltip' => $line,
						'URL' => $node->getAttribute('URL', ''),
						'target' => '_blank',
						'shape' => 'rect',
						'style' => 'filled',
						'fillcolor' => $pastels[2]
					)
				);
				$edge= $dpgraph->beginEdge(array($node, $memNode));
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
			}else{
				//do not display ring group members
			}
				
				# The destinations we need to follow are the no-answer destination
				# (postdest) and the members of the group.
				if ($rg['postdest'] != '') {
					$route['parent_edge_label'] = " ".sprintf(_('No Answer (%s)'), secondsToTimes($rg['grptime']));
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $rg['postdest'].','.$rglang,'',$options);
				}
				if ($rg['annmsg_id']!=0){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Announcement');
					dpp_follow_destinations($route,'play-system-recording,'.$rg['annmsg_id'].',1,'.$rglang,'',$options);
				}
			
		}else{
			notFound($module,$destination,$node);
		}
    # End of Ring Groups
  
		#
		# Ring Group Members
		#
} elseif (preg_match("/^rgmember([#\d]+)/", $destination, $matches)) {
		$rgext = $matches[1];
		$rglabel = isset($route['extensions'][$rgext]) ? _('Ext')." ".$rgext."\n".$route['extensions'][$rgext]['name'] : $rgext;
		$tooltip = isset($route['extensions'][$rgext]) ? _('Extextnsion').": ".$rgext."\n"._('Name').": ".$route['extensions'][$rgext]['name'] : $rgext;

		//registration status
		if (isset($route['extensions'][$rgext]) && ($route['extensions'][$rgext]['tech']=='pjsip' || $route['extensions'][$rgext]['tech']=='sip' || $route['extensions'][$rgext]['tech']=='iax2')){
			$node->attribute('penwidth', '2');
			if (!isset($route['extensions'][$rgext]['reg_status'])){
				$status=isExtensionRegistered($rgext,$route['extensions'][$rgext]['tech']);
				$route['extensions'][$rgext]['reg_status']=$status;
			}
			$registered=$route['extensions'][$rgext]['reg_status'];
			if ($registered){$online=_('Yes');}else{$online=_('No');}
			$node->attribute('color', $registered ? 'green' : 'red');

			$tooltip.="\n\n"._('Registration').": ".$online;
			$tooltip.="\n"._('Tech').": ".strtoupper($route['extensions'][$rgext]['tech']);
		}elseif(isset($route['extensions'][$rgext])){
			$tooltip.="\n\n"._('Tech').": ".ucfirst($route['extensions'][$rgext]['tech']);
		}
		
		
		
		
		$node->attribute('label', sanitizeLabels($rglabel));
		$node->attribute('tooltip', sanitizeLabels($tooltip));
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
			$label= sanitizeLabels($cid['description'])." ".sanitizeLabels("\nName= ".preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name'])."\nNumber= ".preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']));
			$tooltip='';
			makeNode($module,$cidnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($cid['dest'] != '') {
				$route['parent_edge_label'] = " "._('Continue');
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
			$tooltip = _('Engine').": ".$tts['engine']."\n"._('Description').": ".$tts['text'];
			makeNode($module,$ttsnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($tts['goto'] != '') {
				$route['parent_edge_label'] = " "._('Continue');
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
		$module="Time Conditions";
    $tcnum = $matches[1];
    $tcother = $matches[2];
		$tcLang= $matches[3];
		
		if (isset($route['timeconditions'][$tcnum])){
			$tc = $route['timeconditions'][$tcnum];
			if (!isset($tc['mode'])){$tc['mode']='time-group';}
			
			$tcTooltip=$tc['displayname']."\n"._('Mode').": ".$tc['mode']."\n";
			if (!empty($tc['timezone'])){
				$tcTooltip.= ($tc['timezone'] !== 'default') ? _('Timezone').": " . $tc['timezone'] : '';
			}
			$label=sanitizeLabels($tc['displayname']);
			$tooltip=sanitizeLabels($tcTooltip);
			
			makeNode($module,$tcnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}

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
					$tgTooltip=_('Name').": ".$cal['name']."\n"._('Description').": ".$cal['description']."\n"._('Type').": ".$cal['type']."\n"._('Timezone').": ".$tz;
					
				}elseif (!empty($route['calendar'][$tc['calendar_group_id']])){
					$cal= $route['calendar'][$tc['calendar_group_id']];
					$tgLabel=$cal['name'];
					$tgLink = '/admin/config.php?display=calendargroups&action=edit&id='.$tc['calendar_group_id'];
					$calNames=_('Calendars').": ";
					if (!empty($cal['calendars'])){
						foreach ($cal['calendars'] as $c){
							$calNames.=$route['calendar'][$c]['name']."\n";
						}
					}
					
					$cats = !empty($cal['categories']) ? count($cal['categories']) : _('All');
					$categories='Categories= '.$cats;
					$eves = !empty($cal['events']) ? count($cal['events']) : _('All');
					$events=_('Events').": ".$eves;
					$expand = $cal['expand'] ? 'true' : 'false';
					$tgTooltip=_('Name').": ".$cal['name']."\n".$calNames."\n".$categories."\n".$events."\n"._('Expand').": ".$expand;
				}
			}
			
			# Now set the current node to be the parent and recurse on both the true and false branches
			$route['parent_edge_label'] = " "._('Match').": ".$tgLabel;
			$route['parent_edge_url'] = htmlentities($tgLink);
			$route['parent_edge_target'] = '_blank';
			$route['parent_edge_code']='match';
			$route['parent_edge_labeltooltip']=" "._('Match').": ".$tgTooltip;
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $tc['truegoto'].','.$tcLang,'',$options);
			
			
			$route['parent_edge_code']='match';
			$route['parent_edge_label'] = " "._('No Match');
			$route['parent_edge_url'] = htmlentities($tgLink);
			$route['parent_edge_target'] = '_blank';
			$route['parent_edge_labeltooltip']=" "._('No Match')."\n".$tgTooltip;
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $tc['falsegoto'].','.$tcLang,'',$options);
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of Time Conditions
 
		#
		# Trunks
		#
  } elseif (preg_match("/^ext-trunk,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module='Trunks';
		$trunkId= $matches[1];
		$trunkOther = $matches[2];
		$trunkLang = $matches[3];
		
		if (isset($route['trunks'][$trunkId])){
			$trunk= $route['trunks'][$trunkId];
			$status = ($trunk['disabled'] == 'off') ? "Enabled" : "Disabled";
			$continue = ($trunk['continue'] == 'on') ? _('Yes') : _('No');
			$busy=$trunk['continue'];
			$cidArray=array("off"=>"Allow Any CID","on"=>"Block Foreign CID","cnum"=>"Remove CNAM","all"=>"Force Trunk CID");
			$modId=$trunk['tech'].','.$trunkId;
			
			$label=sanitizeLabels($trunk['name'])." (Status: ".$status.")\lCallerID: ".sanitizeLabels($trunk['outcid']);
			$tooltip="Name: ".sanitizeLabels($trunk['name'])."\n"._('Tech').": ".$trunk['tech']."\n"._('Outbound CallerID').": ".sanitizeLabels($trunk['outcid'])."\n"._('Status').": ".$status."\n"._('CID Options').": ".$cidArray[$trunk['keepcid']]."\n"._('Max Channels').": ".$trunk['maxchans']."\n"._('Continue If Busy').": ".$continue;
			$node->attribute('width', 2);
			$node->attribute('margin','.13');
			makeNode($module,$modId,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Trunks

		#
		# Voicemail
		#
  } elseif (preg_match("/^ext-local,vm([b,i,s,u])(\d+),(\d+)/", $destination, $matches)) {
		$module='Voicemail';
		$vmtype= $matches[1];
		$vmnum = $matches[2];
		$vmother = $matches[3];
		
		$vm_array=array('b'=>'(Busy Message)','i'=>'(Instructions Only)','s'=>'(No Message)','u'=>'(Unavailable Message)' );
		if (isset($route['extensions'][$vmnum]['mailbox'])){
			$voicemail=$route['extensions'][$vmnum]['mailbox'];
			$vmname= $voicemail['name'];
			$vmemail= $voicemail['email'];
			$vmemail= str_replace(",",",\n",$vmemail);
			$tooltip="\n\n"._('Voicemail: Enabled');
			$tooltip.="\n"._('Email').": ".$vmemail;
			foreach ($voicemail['options'] as $m=>$mm){
				$tooltip.="\n".ucfirst($m).": ".ucfirst($mm);
			}
			
			$label=$vmnum." ".sanitizeLabels($vmname)." ".$vm_array[$vmtype]."\n".sanitizeLabels($vmemail);

			makeNode($module,$vmnum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Voicemail
	
		#
		# VM Blast + members
		#
  } elseif (preg_match("/^vmblast\-grp,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="VM Blast";
		$vmblastnum = $matches[1];
		$vmblastother = $matches[2];
		$vmblastLang= $matches[3];
		
		if (isset($route['vmblasts'][$vmblastnum])){
			$vmblast = $route['vmblasts'][$vmblastnum];
			$audioID = $vmblast['audio_label'];
			if ($audioID > 0){
				$audioLabel=findRecording($route,$audioID);
			}elseif ($audioID=='-1'){
				$audioLabel="Read Group Number";
			}elseif ($audioID=='-2'){
				$audioLabel="Beep Only - No Confirmation";
			}
			$label=$vmblastnum." ".sanitizeLabels($vmblast['description'])."\n"._('Audio Label').": ".$audioLabel;
			if ($vmblast['password'] !=''){$pass="\nPassword: ".$vmblast['password'];}else{$pass='';}
			$tooltip=$module.": ".$label.$pass;
			makeNode($module,$vmblastnum,$label,$tooltip,$node);
			
			if (!empty($vmblast['members']) && !$minimal){
				$line="Voicemail Blast ".$vmblastnum." "._('members').":\n";
				foreach ($vmblast['members'] as $member) {
					if (isset($route['extensions'][$member])){
						$line.=_('Ext')." ".$member." ".$route['extensions'][$member]['name'].": ";
						$vmblastemail=$route['extensions'][$member]['mailbox']['email'];
						$line.= str_replace(",",",\n",$vmblastemail)."\l";
					}
				}
				
				$memNode= $dpgraph->beginNode('vmblastmem'.$vmblastnum,
					array(
						'label' => $line,
						'tooltip' => $node->getAttribute('label'),
						'URL' => $node->getAttribute('URL', ''),
						'target' => '_blank',
						'shape' => 'rect',
						'style' => 'filled',
						'fillcolor' => $pastels[16]
					)
				);
				$edge= $dpgraph->beginEdge(array($node, $memNode));
			}
			
			if (isset($route['recordings'][$audioID])){
				$route['parent_node'] = $node;
				$route['parent_edge_label']= " "._('Recording');
				$route['parent_edge_code']= 'recording';
				dpp_follow_destinations($route, 'play-system-recording,'.$audioID.',1,'.$vmblastLang,'',$options);
				
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of VM Blast + members
		
		#
		# Custom Destinations (with return)
		#
	} elseif (preg_match("/^customdests,dest-(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Custom Dests";
		$custId=$matches[1];
		$custLang=$matches[3];
		
		if (isset($route['customapps'][$custId])){
			$custDest=$route['customapps'][$custId];
			$custReturn = ($custDest['destret'] == 1) ? _('Yes') : _('No');
			$label="\n".$custDest['description']."\n"._('Target').": ".$custDest['target']."\l"._('Return').": ".$custReturn."\l";
			$tooltip="";
			makeNode($module,$custId,$label,$tooltip,$node);
			
			if ($custDest['destret']){
				$route['parent_edge_label']=" "._('Return');
				$route['parent_node'] = $node;
				
				dpp_follow_destinations($route, $custDest['dest'].','.$custLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		
		#
		# blacklistnotset
		#
	} elseif (preg_match("/^blacklistnotset/", $destination)) {
		$node->attribute('label',_('Bad Dest: Blacklist'));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=blacklist'));
		$node->attribute('target','_blank');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', 'red');
		$node->attribute('style', 'filled');
		#end of blacklistnotset
		
		
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
				$custReturn = ($custDest['destret'] == 1) ? _('Yes') : _('No');
				
				$label=sanitizeLabels($custDest['description'])."\n"._('Target').": ".$custDest['target']."\l"._('Return').": ".$custReturn."\l";
				$tooltip=sanitizeLabels($custDest['description'])."\n"._('Target').": ".$custDest['target']."\n"._('Return').": ".$custReturn."\n";
				makeNode($module,$custId,$label,$tooltip,$node);

				if ($custDest['destret']){
					$route['parent_edge_label']= " "._('Return');
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
	$users=\FreePBX::Core()->getAllUsersByDeviceType();

  foreach($users as $user) {
		$id = $user['extension'];
		$dproute['extensions'][$id]= $user;
		$mailbox=\FreePBX::Voicemail()->getMailbox($id);
		$dproute['extensions'][$id]['mailbox']=$mailbox;	
  }
	
	
	# Inbound Routes
  $query = "select * from incoming order by extension";
  $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from incoming");
  }
  foreach($results as $incoming) {
		$id = empty($incoming['extension']) ? 'ANY' : $incoming['extension'];
    $dproute['incoming'][$id.$incoming['cidnum']] = $incoming;
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

	// Array of table names to check -not required
	$tables = array('announcement','callback','callrecording','daynight','directory_details','disa','dynroute','dynroute_dests',
									'featurecodes','findmefollow','kvstore_FreePBX_modules_Calendar','kvstore_FreePBX_modules_Customappsreg','language_incoming',
									'languages','meetme','miscapps','miscdests','paging_config','paging_groups','queueprio','queues_config','queues_details',
									'recordings','ringgroups','setcid','timeconditions','timegroups_groups','timegroups_details','trunks','tts',
									'virtual_queue_config','vmblast','vmblast_groups','vqplus_callback_config','vqplus_queue_config'
						);
	
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
		}elseif ($table == 'callback') {
				foreach($results as $callback) {
					$id = $callback['callback_id'];
					$dproute['callback'][$id] = $callback;
					dpplog(9, "callback=$id");
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
		}elseif ($table == 'findmefollow') {
        foreach($results as $findmefollow) {
					$id=$findmefollow['grpnum'];
					$dproute['extensions'][$id]['fmfm'] = $findmefollow;
					$check = \FreePBX::Findmefollow()->getDDial($id);
					
					if ($check){
						$dproute['extensions'][$id]['fmfm']['ddial']='EXTENSION';
					}else{
						$dproute['extensions'][$id]['fmfm']['ddial']='DIRECT';
					}
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
    }elseif ($table == 'language_incoming') {
        foreach($results as $language_in) {
					$dproute['incoming'][$language_in['extension'].$language_in['cidnum']]['language'] = $language_in['language'];
					dpplog(9, "languages=$id");
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
					dpplog(9, "miscapps dest: $id");
				}
		}elseif ($table == 'miscdests') {
        foreach($results as $miscdest) {
					$id = $miscdest['id'];
					$dproute['miscdest'][$id] = $miscdest;
					dpplog(9, "miscdest dest: $id");
				}
		}elseif ($table == 'paging_config') {
        foreach($results as $pageConf) {
					$id = $pageConf['page_group'];
					$dproute['paging'][$id] = $pageConf;
					dpplog(9, "paging_config dest: $id");
				}
		}elseif ($table == 'paging_groups') {
        foreach($results as $pageGrp) {
					$id = $pageGrp['page_number'];
					$ext= $pageGrp['ext'];
					$dproute['paging'][$id]['members'][]= $ext;
					dpplog(9, "paging_groups dest: $id");
				}
		}elseif ($table == 'queueprio') {
        foreach($results as $queueprio) {
					$id = $queueprio['queueprio_id'];
					$dproute['queueprio'][$id] = $queueprio;
					dpplog(9, "queueprio dest: $id");
				}
		}elseif ($table == 'queues_config') {
        foreach($results as $q) {
					$id = $q['extension'];
					$dproute['queues'][$id] = $q;
					$dproute['queues'][$id]['members']['static']=array();
					$dproute['queues'][$id]['members']['dynamic']=array();
					//$dproute['queues'][$id]['static']['members']=array();
					//$dproute['queues'][$id]['static']['penalties']=array();
					//$dproute['queues'][$id]['dynamic']['members']=array();
					//$dproute['queues'][$id]['dynamic']['penalties']=array();
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
    }elseif ($table == 'recordings') {
        foreach($results as $recordings) {
					$id=$recordings['id'];
					$dproute['recordings'][$id] = $recordings;
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
						$check=checkTimeGroupLogic($tgd['time']);
						$exploded=explode("|",$tgd['time']);
						
						$time = ($exploded[0] !== "*") ? $exploded[0] : "";
						$dow = ($exploded[1] !== "*") ? implode("-", array_map('ucfirst', explode("-", $exploded[1]))) : "";
						$day  = ($exploded[2] !== "*") ? $exploded[2]  : "";
						$month = ($exploded[3] !== "*") ? implode("-", array_map('ucfirst', explode("-", $exploded[3]))) : "";
						
						if ($month && ($dow!='' || $day!='' || $time!='')){$month.=" | ";}
						if ($day && ($dow!='' || $time!='')){$day.=" | ";}
						if ($dow && ($time!='')){$dow.=" | ";}
						$dproute['timegroups'][$id]['time'].=$month . $day . $dow . $time . $check."\l";
					}
				}
    }elseif ($table == 'trunks') {
        foreach($results as $trunk) {
					$id = $trunk['trunkid'];
					$dproute['trunks'][$id] = $trunk;
				}
		}elseif ($table == 'tts') {
        foreach($results as $tts) {
					$id = $tts['id'];
					$dproute['tts'][$id] = $tts;
				}
    }elseif ($table == 'vqplus_callback_config') {
				foreach($results as $vqcallback) {
					$id = $vqcallback['id'];
					$dproute['queuecallback'][$id] = $vqcallback;
				}
		}elseif ($table == 'virtual_queue_config') {
				foreach($results as $vqueues) {
					$id = $vqueues['id'];
					$dproute['vqueues'][$id] = $vqueues;
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
		}elseif ($table == 'vqplus_queue_config') {
				foreach($results as $vqplus) {
					$id = $vqplus['queue_num'];
					$dproute['queues'][$id]['vqplus']=$vqplus;
				}
		}
	}
	
}
# END load gobs of data.

function sanitizeLabels($text) {
    if ($text === null) {
        $text = '';
    }
		
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8');

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
	
		if (!is_numeric($seconds) || $seconds < 0) {
			return $seconds;
		}
		
    $seconds = (int) round($seconds); // Ensure whole number input

    $hours = (int) ($seconds / 3600);
    $minutes = (int) (($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    if ($hours > 0) {
        return $remainingSeconds === 0 
					? "$hours hrs, $minutes mins" 
					: "$hours hrs, $minutes mins, $remainingSeconds secs";
    } elseif ($minutes > 0) {
        return $remainingSeconds === 0 
					? "$minutes mins" 
					: "$minutes mins, $remainingSeconds secs";
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
    $reserved = array('CON','PRN','AUX','NUL','COM1','COM2','COM3','COM4','COM5','COM6','COM7','COM8','COM9',
                 'LPT1','LPT2','LPT3','LPT4','LPT5','LPT6','LPT7','LPT8','LPT9');
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

			case 'Callback':
					$url=strtolower($module).'&view=form&itemid='.$id;
					$shape='rect';
					$color='#F7A8A8';
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

			case 'Paging':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='tab';
					$color='#87CEFA';
					break;
			
			case 'Phonebook':
					$url='phonebook';
					$shape='folder';
					$color='#BDB76B';
					break;
			
			case 'Queue Callback':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&id='.$id;
					$shape='rect';
					$color='#98FB98';
					break;

			case 'Queues':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='hexagon';
					$color='mediumaquamarine';
					break;

			case 'Queue Priorities':
					$url='queueprio&view=form&extdisplay='.$id;
					$shape='rect';
					$color='#FFC3A0';
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

			case 'Time Conditions':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&itemid='.$id;
					$module="TC";
					$shape='invhouse';
					$color='dodgerblue';
					break;
			
			case 'TTS':
					$url=strtolower($module).'&view=form&id='.$id;
					$shape='note';
					$color='#ed9581';
					break;

			case 'Trunks':
					$idArray=explode(",",$id);
					$url=strtolower($module).'&tech='.$idArray[0].'&extdisplay=OUT_'.$idArray[1];
					$shape='rarrow';
					$color='#66CDAA';
					break;
					
			case 'VM Blast':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='folder';
					$color='gainsboro';
					break;

			case 'Virtual Queues':
					$url='vqueue&action=modify&id='.$id;
					$module='VQueue';
					$shape='hexagon';
					$color='#00FA9A';
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

function stopNode($dpgraph,$id){
		$undoNode = $dpgraph->beginNode('undoLast'.$id,
			array(
				'label' => '+',
				'tooltip' => _('Click to continue...'),
				'shape' => 'circle',
				'URL' => '#',
				'fontcolor' => '#FFFFFF',
				'fontsize' => '45pt',
				'fixedsize' => true,
				'fillcolor' => '#4A90E2',
				'style' => 'filled'
			)
		);				
		return $undoNode;
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

function checkTimeGroupLogic($entry) {
    list($time, $dow, $dom, $month) = explode('|', $entry);

    $errors = array();

    $monthOrder = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,
                   'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12);

    $monthStart = $monthEnd = null;
    $monthInverted = false;

    // Check for inverted month range
    if ($month !== '*') {
        if (preg_match('/(\w{3})-(\w{3})/', strtolower($month), $m)) {
            if (isset($monthOrder[$m[1]], $monthOrder[$m[2]])) {
                $monthStart = $monthOrder[$m[1]];
                $monthEnd = $monthOrder[$m[2]];

                if ($monthStart > $monthEnd) {
                    $monthInverted = true;
                    $errors[] = 'month inverted ';
                }
            }
        }
    }

    $dayStart = $dayEnd = null;
    $dayInverted = false;

    // Check for inverted day-of-month
    if ($dom !== '*' && preg_match('/(\d{1,2})-(\d{1,2})/', $dom, $d)) {
        $dayStart = (int)$d[1];
        $dayEnd = (int)$d[2];

        if ($dayStart > $dayEnd) {
            $dayInverted = true;
            $errors[] = 'day-of-month inverted ';
        }
    }

    if (!empty($errors)) {
        return " ❌ " . implode(', ', $errors);
    }

    return null;
}


function isExtensionRegistered($extension, $tech) {
    $astman = \FreePBX::create()->astman;

    // Choose command based on technology
    switch (strtoupper($tech)) {
        case 'PJSIP':
            $cmd = "pjsip show aor $extension";
            break;
        case 'SIP':
            $cmd = "sip show peer $extension";
            break;
        case 'IAX':
            $cmd = "iax2 show peer $extension";
            break;
        default:
            return false; // Unknown technology
    }

    // Run the command
    $response = $astman->send_request('Command', array('Command' => $cmd));

    // Extract data
    $data = '';
    if (isset($response['data'])) {
        $data = is_array($response['data']) ? implode("\n", $response['data']) : $response['data'];
    }

    // Parse based on tech
    if (strtoupper($tech) === 'PJSIP') {
    $aors = parsePjsipAors($data);
			foreach ($aors as $aor) {
					foreach ($aor['contacts'] as $contactLine) {
							// Match "Avail" and extract latency
							if (preg_match('/\bAvail\b\s+([\d.]+)/i', $contactLine, $m)) {
									return true; // Consider it reachable
							}
					}
			}
		}elseif (preg_match('/Status\s*:\s*(\S+)/i', $data, $m)) {
			// SIP or IAX: look for "Status: OK" or similar
			return (stripos($m[1], 'OK') !== false);
    }

    return false;
}

function parsePjsipAors($data) {
    $lines = explode("\n", $data);
    $aors = array();
    $currentAor = null;

    foreach ($lines as $line) {
        $line = trim($line);

        if (preg_match('/^Aor:\s+(\S+)/', $line, $match)) {
            $currentAor = $match[1];
            $aors[$currentAor] = array(
                'contact_found' => false,
                'contacts' => array(),
            );
        }

        if ($currentAor && strpos($line, 'Contact:') === 0) {
            $aors[$currentAor]['contact_found'] = true;
            $aors[$currentAor]['contacts'][] = $line;
        }
    }

    return $aors;
}




