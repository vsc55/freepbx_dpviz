<?php
// License for all code of this FreePBX module can be found in the license file inside the module directory
// Copyright 2015 Sangoma Technologies.
// vim: set ai ts=4 sw=4 ft=php:

namespace FreePBX\modules;

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/dpp.php';

class Dpviz extends \FreePBX_Helpers implements \BMO {
    
    private $freepbx;
	private $db;

	public $dpp = null;

	Const TABLE_NAME = 'dpviz';

	// // Log Level: 0 = total quiet, 9 = much verbose
	// Const DPP_LOG_LEVEL = 3;

	// // Set some colors
	// Const pastels = [
    // 	"#7979FF", "#86BCFF", "#8ADCFF", "#3DE4FC", "#5FFEF7", "#33FDC0",
    // 	"#ed9581", "#81a6a2", "#bae1e7", "#eb94e2", "#f8d580", "#979291",
    // 	"#92b8ef", "#ad8086", "#F7A8A8", "#C5A3FF", "#FFC3A0", "#FFD6E0",
    // 	"#FFB3DE", "#D4A5A5", "#A5D4D4", "#F5C6EC", "#B5EAD7", "#C7CEEA",
    // 	"#E0BBE4", "#FFDFD3", "#FEC8D8", "#D1E8E2", "#E8D1E1", "#EAD5DC",
    // 	"#F9E79F", "#D6EAF8"
	// ];

	// Const neons = [
    // 	"#fe0000", "#fdfe02", "#0bff01", "#011efe", "#fe00f6",
    // 	"#ff5f1f", "#ff007f", "#39ff14", "#ff073a", "#ffae00",
    // 	"#08f7fe", "#ff44cc", "#ff6ec7", "#dfff00", "#32cd32",
    // 	"#ccff00", "#ff1493", "#00ffff", "#ff00ff", "#ff4500",
    // 	"#ff00aa", "#ff4c4c", "#7df9ff", "#adff2f", "#ff6347",
    // 	"#ff66ff", "#f2003c", "#ffcc00", "#ff69b4", "#0aff02"
	// ];

	Const default_config = [
		'panzoom'	  => 1,
		'horizontal'  => 0,
		'datetime'	  => 1,
		'destination' => 1,
		'scale'		  => 1,
		'dynmembers'  => 0
	];

    public function __construct($freepbx = null)
	{
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}

        // parent::__construct($freepbx);
        $this->freepbx = $freepbx;
        $this->db 	   = $freepbx->Database;

		$this->dpp = new \FreePBX\modules\Dpviz\dpp($this->freepbx, $this->db);
    }

    public function install()
	{
		$sql = sprintf("INSERT INTO %s (`panzoom`,`horizontal`,`datetime`,`destination`,`scale`,`dynmembers`) VALUES (?,?,?,?,?,?)", self::TABLE_NAME);
		$sth = $this->db->prepare($sql);
		return $sth->execute([
			self::default_config['panzoom'],
			self::default_config['horizontal'],
			self::default_config['datetime'],
			self::default_config['destination'],
			self::default_config['scale'],
			self::default_config['dynmembers']
		]);
    }

    public function uninstall() {
        // Required by BMO, but can remain empty
    }

    public function getOptions() {
        $sql = sprintf("SELECT * FROM %s", self::TABLE_NAME);
        $sth = $this->db->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function editDpviz($panzoom, $horizontal, $datetime, $destination, $scale, $dynmembers)
	{
		// Valideate and sanitize inputs.
		// We are using the default values if the inputs are not set and only allow 1 or 0
		$panzoom = ($panzoom ?? self::default_config['panzoom']) == 1 ? 1 : 0;
		$horizontal = ($horizontal ?? self::default_config['horizontal']) == 1 ? 1 : 0;
		$datetime = ($datetime ?? self::default_config['datetime']) == 1 ? 1 : 0;
		$destination = ($destination ?? self::default_config['destination']) == 1 ? 1 : 0;
		$scale = ($scale ?? self::default_config['scale']) == 1 ? 1 : 0;
		$dynmembers = ($dynmembers ?? self::default_config['dynmembers']) == 1 ? 1 : 0;

        $sql  = sprintf("UPDATE %s SET `panzoom` = :panzoom, `horizontal` = :horizontal, `datetime` = :datetime, `destination` = :destination, `scale` = :scale, `dynmembers` = :dynmembers WHERE `id` = 1", self::TABLE_NAME);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':panzoom'	   => $panzoom,
            ':horizontal'  => $horizontal,
			':datetime'    => $datetime,
			':destination' => $destination,
			':scale'       => $scale,
			':dynmembers'  => $dynmembers
        ]);
    }

	function options_gets() {
		$data_return = [];
		$rows = $this->getOptions();
		
		if(!empty($rows) && is_array($rows))
		{
			foreach ($rows as $row)
			{
				$data_return[] = $row;
			}
		}
		return $data_return;
	}

    public function doConfigPageInit($page) {
        $request = freepbxGetSanitizedRequest();
		// $request = $_REQUEST;
        $action	 = $request['action'] ?? '';

        switch ($action)
		{
            case 'edit':
				// If the request does not exist, it defines it as null since editDpviz() will validate and set the default value if necessary.
				$panzoom 	 = $request['panzoom'] ?? null;
				$horizontal  = $request['horizontal'] ?? null;
				$datetime 	 = $request['datetime'] ?? null;
				$destination = $request['destination'] ?? null;
				$scale 		 = $request['scale'] ?? null;
				$dynmembers  = $request['dynmembers'] ?? null;

                $this->editDpviz($panzoom, $horizontal, $datetime, $destination, $scale, $dynmembers);
                break;

            default:
                break;
        }
    }

	public function showPage($page, $params = array())
	{
		$request = freepbxGetSanitizedRequest();
		// $request = $_REQUEST;
		$options = $this->options_gets();
		$data = array(
			"dpviz"	  	 => $this,
			'request' 	 => $request,
			'page' 	  	 => $page ?? '',
			'options' 	 => $options,
			'extdisplay' => $request['extdisplay'] ?? '',
			'cid' 		 => $request['cid'] ?? '',
		);

		$data = array_merge($data, $params);
		switch ($page) 
		{
			case 'main':
				$data['action'] = $request['action'] ?? '';

				$data_return = load_view(__DIR__."/views/page.main.php", $data);
				break;

			case 'options':
				$data['datetime'] 			= $options[0]['datetime'] ?? '1';
				$data['horizontal'] 		= $options[0]['horizontal'] ?? '0';
				$data['panzoom'] 			= $options[0]['panzoom'] ?? '0';
				$data['destinationColumn'] 	= $options[0]['destination'] ?? '0';
				$data['scale'] 				= $options[0]['scale'] ?? '1';
				$data['dynmembers'] 		= $options[0]['dynmembers'] ?? '0';
				$data['direction'] 			= ($options[0]['horizontal'] ?? 0) == 1 ? 'LR' : 'TB';
				$data['clickedNodeTitle'] 	= $request['clickedNodeTitle'] ?? '';
				
				$data_return = load_view(__DIR__."/views/view.options.php", $data);
				break;

			case 'dialplan':
				$data['iroute'] 	= sprintf("%s%s", $data['extdisplay'], $data['cid']);
				$data['datetime'] 	= $options[0]['datetime'] ?? '1';
				$data['scale'] 		= $options[0]['scale'] ?? '1';
				$data['panzoom'] 	= $options[0]['panzoom'] ?? '0';
				$data['direction'] 	= ($options[0]['horizontal'] ?? 0) == 1 ? 'LR' : 'TB';

				if (!isset($_GET['extdisplay']))
				{
					//TODO: Add a message to the user or load view.
					$data_return = _("Please select a dialplan to visualize.");
				}
				else 
				{
					$this->dpp->setDirection($data['direction']);
					
					$data['clickedNodeTitle'] = $request['clickedNodeTitle'] ?? '';
					$data['filename'] 		  = ($data['iroute'] == '') ? 'ANY.png' : sprintf("%s.png", $data['iroute']);
					$data['isExistRoute'] 	  = $this->dpp->isExistRoute($data['iroute']);
					$data_return = load_view(__DIR__."/views/view.dialplan.php", $data);
				}
				break;

			default:
				$data_return = sprintf(_("Unknown page: %s"), $page);
				break;
		}
		return $data_return;
	}

	public function getRightNav($request, $params = array())
	{
		$options = $this->options_gets();
		$data = array(
		 	"dpviz"   			=> $this,
		 	"request" 			=> $request,
		 	"display" 			=> strtolower(trim($request['display'] ?? '')),
		 	'destinationColumn' => ($options[0]['destination'] ?? self::default_config['destination']) == 1 ? true : false,
		 	'destinations' 		=> $this->freepbx->Modules->getDestinations(),
			'extdisplay' 		=> $request['extdisplay'] ?? null,
		);
		$data = array_merge($data, $params);
		return load_view(__DIR__.'/views/rnav.php', $data);
	}
		
	public function ajaxRequest($req, &$setting)
	{
		// ** Allow remote consultation with Postman **
		// ********************************************
		// $setting['authenticate'] = false;
		// $setting['allowremote'] = true;
		// return true;
		// ********************************************
		switch ($req)
		{
			case 'check_update':
				return true;
				break;
		}
		return false;
	}

	public function ajaxHandler()
	{
		$command = $this->getReq("command", "");
		$data_return = false;
		switch ($command)
		{
			case 'check_update':
				// Call the function to check for updates
				$result = $this->checkForGitHubUpdate();
				if (isset($result['error']))
				{
					$data_return = ['status' => 'error', 'message' => $result['error']];
				}
				else
				{
					$data_return = [
						'status'  	 => 'success',
						'current' 	 => $result['current'],
						'latest'  	 => $result['latest'],
						'up_to_date' => $result['up_to_date'],
					];
				}
				break;

			default:
				$data_return = ['status' => 'error', 'message' => _('Unknown command')];
		}
		return $data_return;
	}
	
	public function checkForGitHubUpdate()
	{
		$ver = $this->freepbx->Modules->getInfo('dpviz')['dpviz']['version']; // current version
		$url = "https://api.github.com/repos/madgen78/dpviz/releases/latest"; // GitHub version

		$opts = [
			"http" => [
				"method" => "GET",
				"header" => "User-Agent: dpviz\r\n"
			]
		];

		$context = stream_context_create($opts);
		$json 	 = file_get_contents($url, false, $context);
		if ($json === false)
		{
			return ['error' => _('Failed to fetch release info.')];
		}

		$data = json_decode($json, true);
		if (!isset($data['tag_name']))
		{
			return ['error' => _('Invalid response from GitHub.')];
		}

		$latestVersion = ltrim($data['tag_name'], 'v');
		$upToDate = version_compare($ver, $latestVersion, '>=');
		
		return [
			'current' => $ver,
			'latest' => $latestVersion,
			'up_to_date' => $upToDate,
		];
	}





	// public function dpp_load_incoming_routes()
	// {
	// 	return;
	// 	$sql = sprintf("SELECT * FROM %s Order by extension", "incoming");
    //     $sth = $this->db->prepare($sql);
    //     $sth->execute();
    //     $results = $sth->fetchAll(\PDO::FETCH_ASSOC);

	// 	$routes = [];
	// 	if (is_array($results)) {
	// 		foreach ($results as $route)
	// 		{
	// 			$key = sprintf("%s%s", $route['extension'], $route['cidnum']);
	// 			$routes[$key] = $route;
	// 		}
	// 	}
	// 	return $routes;
	// }

	// public function dpp_find_route($routes, $num)
	// {
	// 	return;
	// 	$match = array();
	// 	$pattern = '/[^_xX+0-9\[\]]/';   # remove all non-digits
	// 	$num =  preg_replace($pattern, '', $num);
	  
	// 	// "extension" is the key for the routes hash
	// 	foreach ($routes as $ext => $route)
	// 	{
	// 		if ($ext == $num) {
	// 			$match = $routes[$num];
	// 	  	}
	// 	}
	// 	return $match;
	// }

	// # load gobs of data.  Save it in hashrefs indexed by ints
	// public function dpp_load_tables(&$dproute)
	// {
	// 	return;
	// 	global $dynmembers;

	// 	# Time Conditions
	// 	$sql = sprintf("SELECT * FROM %s", "timeconditions");
    //     $sth = $this->db->prepare($sql);
    //     $sth->execute();
    //     $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $tc)
	// 	{
	// 		$id = $tc['timeconditions_id'];
	// 		$dproute['timeconditions'][$id] = $tc;
	// 	}

	// 	# Time Groups
	// 	$sql = sprintf("SELECT * FROM %s", "timegroups_groups");
    //     $sth = $this->db->prepare($sql);
    //     $sth->execute();
    //     $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $tg)
	// 	{
	// 		$id = $tg['id'];
	// 		$dproute['timegroups'][$id] = $tg;
	// 	}

	// 	# Time Groups Details
	// 	$sql = sprintf("SELECT * FROM %s", "timegroups_details");
    //     $sth = $this->db->prepare($sql);
    //     $sth->execute();
    //     $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $tgd)
	// 	{
	// 		$id = $tgd['timegroupid'];
	// 		if (! isset($dproute['timegroups'][$id]))
	// 		{
	// 			$this->dpplog(1, "timegroups_details id found for unknown timegroup, id=$id");
	// 		}
	// 		else
	// 		{
	// 			if (!isset($dproute['timegroups'][$id]['time']))
	// 			{
	// 				$dproute['timegroups'][$id]['time'] = '';
	// 			}
	// 			$exploded = explode("|", $tgd['time']);
	// 			$time 	  = ($exploded[0] !== '*') ? $exploded[0] : '';
	// 			$dow 	  = ($exploded[1] !== '*') ? sprintf("%s, ", ucwords($exploded[1], '-')) : '';
	// 			$date 	  = ($exploded[2] !== '*') ? sprintf("%s ", $exploded[2]) : '';
	// 			$month 	  = ($exploded[3] !== '*') ? sprintf("%s ", ucfirst($exploded[3])) : '';

	// 			$dproute['timegroups'][$id]['time'] .= sprintf("%s%s%s%s\\l", $dow, $month, $date, $time);
	// 		}
	// 	}

	// 	# Users
	// 	$sql = sprintf("SELECT * FROM %s", "users");
    //     $sth = $this->db->prepare($sql);
    //     $sth->execute();
    //     $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $users)
	// 	{
	// 		$Qresult = array();
	// 		$id 	 = $users['extension'];
	// 		$u[$id]  = $users;

	// 		$dproute['extensions'][$id]= $users;
				
	// 		$Q='grep -E \'^'.$id.'[[:space:]]*[=>]+\' /etc/asterisk/voicemail.conf | cut -d \',\' -f3';
	// 		exec($Q, $Qresult);
	// 		$dproute['extensions'][$id]['email'] = !empty($Qresult[0]) ? $Qresult[0] : 'unassigned';
	// 	}
			
	// 	# Queues
	// 	$sql = sprintf("SELECT * FROM %s", "queues_config");
	// 	$sth = $this->db->prepare($sql);
	// 	$sth->execute();
	// 	$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $q)
	// 	{
	// 		$id = $q['extension'];
	// 		$dproute['queues'][$id] = $q;
	// 		$dproute['queues'][$id]['members']['static']  = array();
	// 		$dproute['queues'][$id]['members']['dynamic'] = array();
	// 	}
			
	// 	# Queue members (static)
	// 	$sql = sprintf("SELECT * FROM %s", "queues_details");
	// 	$sth = $this->db->prepare($sql);
	// 	$sth->execute();
	// 	$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $qd)
	// 	{
	// 		$id = $qd['id'];
	// 		if ($qd['keyword'] == 'member')
	// 		{
	// 			$member = $qd['data'];
	// 			if (preg_match("/Local\/(\d+)/", $member, $matches))
	// 			{
	// 				$enum = $matches[1];
	// 				$dproute['queues'][$id]['members']['static'][] = $enum;
	// 			}
	// 		}
	// 		else
	// 		{
	// 			$dproute['queues'][$id]['data'][$qd['keyword']] = $qd['data'];
	// 		}
	// 	}
		
	// 	# Queue members (dynamic) //options
	// 	if ($dynmembers && !empty($dproute['queues']))
	// 	{
	// 		foreach ($dproute['queues'] as $id=>$details)
	// 		{
	// 			$dynmem=array();
				
	// 			$D='/usr/sbin/asterisk -rx "database show QPENALTY '.$id.'" | grep \'/agents/\' | cut -d\'/\' -f5 | cut -d\':\' -f1';
	// 			exec($D, $dynmem);

	// 			foreach ($dynmem as $enum)
	// 			{
	// 				$dproute['queues'][$id]['members']['dynamic'][] = $enum;
	// 			}
	// 		}
	// 	}
		
	// 	# Inbound Routes
	// 	$sql = sprintf("SELECT * FROM %s", "incoming");
	// 	$sth = $this->db->prepare($sql);
	// 	$sth->execute();
	// 	$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $incoming)
	// 	{
	// 		$id = $incoming['extension'];
	// 		$dproute['incoming'][$id] = $incoming;
	// 	}	
		
	// 	# IVRs
	// 	$sql = sprintf("SELECT * FROM %s", "ivr_details");
	// 	$sth = $this->db->prepare($sql);
	// 	$sth->execute();
	// 	$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $ivr)
	// 	{
	// 		$id = $ivr['id'];
	// 		$dproute['ivrs'][$id] = $ivr;
	// 	}

	// 	# IVR entries
	// 	$sql = sprintf("SELECT * FROM %s", "ivr_entries");
	// 	$sth = $this->db->prepare($sql);
	// 	$sth->execute();
	// 	$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
	// 	foreach($results as $ent)
	// 	{
	// 		$id    = $ent['ivr_id'];
	// 		$selid = $ent['selection'];
	// 		$this->dpplog(9, "entry:  ivr=$id   selid=$selid");
	// 		$dproute['ivrs'][$id]['entries'][$selid] = $ent;
	// 	}

	// 	# Recordings
	// 	$sql = sprintf("SELECT * FROM %s", "recordings");
	// 	$sth = $this->db->prepare($sql);
	// 	$sth->execute();
	// 	$results = $sth->fetchAll(\PDO::FETCH_ASSOC);

	// 	foreach($results as $recordings)
	// 	{
	// 		$id = $recordings['id'];
	// 		$dproute['recordings'][$id] = $recordings;
	// 		$this->dpplog(9, "recordings=$id");
	// 	}
			
		
	// 	// Array of table names to check -not required
	// 	$tables = [
	// 		'announcement',
	// 		'daynight',
	// 		'directory_details',
	// 		'disa',
	// 		'dynroute',
	// 		'dynroute_dests',
	// 		'featurecodes',
	// 		'kvstore_FreePBX_modules_Customappsreg',
	// 		'languages',
	// 		'meetme',
	// 		'miscdests',
	// 		'ringgroups',
	// 		'setcid',
	// 		'tts',
	// 		'vmblast',
	// 		'vmblast_groups'
	// 	];
		
	// 	foreach ($tables as $table)
	// 	{
	// 		// Check if the table exists
	// 		$sql = sprintf("SHOW TABLES LIKE '%s'", $table);
	// 		$tableExists = $this->db->getOne($sql);
		
	// 		if (!$tableExists)
	// 		{
	// 			// Skip to the next table if the current table does not exist
	// 			continue;
	// 		}

	// 		$sql = sprintf("SELECT * FROM %s", $table);
	// 		$sth = $this->db->prepare($sql);
	// 		$sth->execute();
	// 		$results = $sth->fetchAll(\PDO::FETCH_ASSOC);

	// 		if (\DB::IsError($results)) {
	// 			// Log the error but continue to check the other tables
	// 			$this->dpplog(9, "Error selecting from $table: " . $results->getMessage());
	// 			continue;  // Skip to the next table
	// 		}

	// 		switch ($table)
	// 		{
	// 			case 'announcement':
	// 				foreach($results as $an)
	// 				{
	// 					$id = $an['announcement_id'];
	// 					$dproute['announcements'][$id] = $an;
	// 					$dest = $an['post_dest'];
	// 					$this->dpplog(9, "announcement dest:  an=$id   dest=$dest");
	// 					$dproute['announcements'][$id]['dest'] = $dest;
	// 				}
	// 			break;

	// 			case 'daynight':
	// 				foreach($results as $daynight)
	// 				{
	// 					$id = $daynight['ext'];
	// 					$dproute['daynight'][$id][] = $daynight;
	// 					$this->dpplog(9, "daynight=$id");
	// 				}
	// 			break;

	// 			case 'directory_details':
	// 				foreach($results as $directory) {
	// 					$id = $directory['id'];
	// 					$dproute['directory'][$id] = $directory;
	// 					$this->dpplog(9, "directory=$id");
	// 				}
	// 			break;

	// 			case 'disa':
	// 				foreach($results as $disa)
	// 				{
	// 					$id = $disa['disa_id'];
	// 					$dproute['disa'][$id] = $disa;
	// 					$this->dpplog(9, "disa=$id");
	// 				}
	// 			break;

	// 			case 'dynroute':
	// 				foreach ($results as $dynroute)
	// 				{
	// 					$id = $dynroute['id'];
	// 					$dproute['dynroute'][$id] = $dynroute;
	// 					$this->dpplog(9, "dynroute=$id");
	// 				}
	// 			break;

	// 			case 'dynroute_dests':
	// 				foreach ($results as $dynroute_dests)
	// 				{
	// 					$id = $dynroute_dests['dynroute_id'];
	// 					$selid = $dynroute_dests['selection'];
	// 					$this->dpplog(9, "dynroute_dests: dynroute=$id match=$selid");
	// 					$dproute['dynroute'][$id]['routes'][$selid] = $dynroute_dests;
	// 				}
	// 			break;

	// 			case 'featurecodes':
	// 				foreach($results as $featurecodes)
	// 				{
	// 					$id = $featurecodes['defaultcode'];
	// 					$dproute['featurecodes'][$id] = $featurecodes;
	// 					$this->dpplog(9, "featurecodes=$id");
	// 				}
	// 			break;

	// 			case 'kvstore_FreePBX_modules_Customappsreg':
	// 				foreach($results as $Customappsreg)
	// 				{
	// 					if (is_numeric($Customappsreg['key']))
	// 					{
	// 						$id = $Customappsreg['key'];
	// 						$val=json_decode($Customappsreg['val'],true);
	// 						$dproute['customapps'][$id] = $val;
	// 						$this->dpplog(9, "customapps=$id");
	// 					}
	// 				}
	// 			break;

	// 			case 'languages':
	// 				foreach($results as $languages)
	// 				{
	// 					$id = $languages['language_id'];
	// 					$dproute['languages'][$id] = $languages;
	// 					$this->dpplog(9, "languages=$id");
	// 				}
	// 			break;

	// 			case 'meetme':
	// 				foreach($results as $meetme)
	// 				{
	// 					$id = $meetme['exten'];
	// 					$dproute['meetme'][$id] = $meetme;
	// 					$this->dpplog(9, "meetme dest:  conf=$id");
	// 				}
	// 			break;

	// 			case 'miscdests':
	// 				foreach($results as $miscdest)
	// 				{
	// 					$id = $miscdest['id'];
	// 					$dproute['miscdest'][$id] = $miscdest;
	// 					$this->dpplog(9, "miscdest dest: $id");
	// 				}
	// 			break;

	// 			case 'ringgroups':
	// 				foreach($results as $rg) 
	// 				{
	// 					$id = $rg['grpnum'];
	// 					$dproute['ringgroups'][$id] = $rg;
	// 				}
	// 			break;

	// 			case 'setcid':
	// 				foreach($results as $cid)
	// 				{
	// 					$id = $cid['cid_id'];
	// 					$dproute['setcid'][$id] = $cid;
	// 				}
	// 			break;

	// 			case 'tts':
	// 				foreach($results as $tts)
	// 				{
	// 					$id = $tts['id'];
	// 					$dproute['tts'][$id] = $tts;
	// 				}
	// 			break;

	// 			case 'vmblast':
	// 				foreach($results as $vmblasts)
	// 				{
	// 					$id = $vmblasts['grpnum'];
	// 					$this->dpplog(9, "vmblast:  vmblast=$id");
	// 					$dproute['vmblasts'][$id] = $vmblasts;
	// 				}
	// 			break;

	// 			case 'vmblast_groups':
	// 				foreach($results as $vmblastsGrp)
	// 				{
	// 					$id = $vmblastsGrp['grpnum'];
	// 					$this->dpplog(9, "vmblast:  vmblast=$id");
	// 					$dproute['vmblasts'][$id]['members'][] = $vmblastsGrp['ext'];
	// 				}
	// 			break;
	// 		}
	// 	}
	// }
	// # END load gobs of data.

	// public function dpplog($level, $msg)
	// {
	// 	if (self::DPP_LOG_LEVEL < $level) {
	// 		return;
	// 	}
	
	// 	$ts = date('Y-m-d H:i:s');
	// 	$logFile = "/var/log/asterisk/dpviz.log";
	
	// 	$fd = fopen($logFile, "a");
	// 	if (!$fd)
	// 	{
	// 		error_log("Couldn't open log file: $logFile");
	// 		return;
	// 	}
	
	// 	fwrite($fd, "[$ts] [Level $level] $msg\n");
	// 	fclose($fd);
	// }






	






	// #
	// # This is a recursive function.  It digs through various nodes
	// # (ring groups, ivrs, time conditions, extensions, etc.) to find
	// # the path a call takes.  It creates a graph of the path through
	// # the dial plan, stored in the $route object.
	// #
	// #
	// public function dpp_follow_destinations(&$route, $destination, $optional)
	// {
	// 	return;
	// 	global $direction;
		
	// 	$optional = preg_match('/^[_xX+\d\[\]]+$/', $optional) ? '' : $optional;
	// 	if (! isset ($route['dpgraph']))
	// 	{
	// 		$route['dpgraph'] = new \Alom\Graphviz\Digraph('"'.$route['extension'].'"');
	// 		$route['dpgraph']->attr('graph',array('rankdir'=>$direction));
	// 	}

	// 	$dpgraph = $route['dpgraph'];
	// 	$this->dpplog(9, "destination='$destination' route[extension]: " . print_r($route['extension'], true));
	
	// 	# This only happens on the first call.  Every recursive call includes
	// 	# a destination to look at.  For the first one, we get the destination from
	// 	# the route object.
		
	// 	if ($destination == '')
	// 	{
	// 		if (empty($route['extension']))
	// 		{
	// 			$didLabel = 'ANY';
	// 		}
	// 		elseif (is_numeric($route['extension']) && (strlen($route['extension'])==10 || strlen($route['extension'])==11))
	// 		{
	// 			$didLabel = $this->formatPhoneNumbers($route['extension']);
	// 		}
	// 		else
	// 		{
	// 			$didLabel = $route['extension'];
	// 		}

	// 		$didLink = sprintf("%s/", $route['extension']);
	// 		if (!empty($route['cidnum']))
	// 		{
	// 			$didLabel .= sprintf(' / %s', $this->formatPhoneNumbers($route['cidnum']));
	// 			$didLink  .= $route['cidnum'];
	// 		}
	// 		$didLabel .= sprintf('\\n%s', $route['description']);
	
	// 		$node_extension = array(
	// 			'label'		=> $this->sanitizeLabels($didLabel),
	// 			'tooltip'	=> $this->sanitizeLabels($didLabel),
	// 			'width'		=> 2,
	// 			'margin'	=> '.13',
	// 			'shape'		=> 'cds',
	// 			'style'		=> 'filled',
	// 			'URL'		=> htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)),
	// 			'target'  	=>'_blank',
	// 			'fillcolor' => 'darkseagreen'
	// 		);
	// 		$dpgraph->node($route['extension'], $node_extension);
				
	// 		// $graph->node() returns the graph, not the node, so we always
	// 		// have to get() the node after adding to the graph if we want
	// 		// to save it for something.
	// 		// UPDATE: beginNode() creates a node and returns it instead of
	// 		// returning the graph.  Similarly for edge() and beginEdge().
	// 		$route['parent_node'] = $dpgraph->get($route['extension']);

			

	// 		# One of thse should work to set the root node, but neither does.
	// 		# See: https://rt.cpan.org/Public/Bug/Display.html?id=101437
	// 		#$route->{parent_node}->set_attribute('root', 'true');
	// 		#$dpgraph->set_attribute('root' => $route->{extension});
				
	// 		// If an inbound route has no destination, we want to bail, otherwise recurse.
	// 		if ($optional != '')
	// 		{
	// 			$route['parent_edge_label'] = ' ';
	// 			$this->dpp_follow_destinations($route, $optional, '');
	// 		}
	// 		elseif ($route['destination'] != '')
	// 		{
	// 			$route['parent_edge_label'] = ' Always';
	// 			$this->dpp_follow_destinations($route, $route['destination'], '');
	// 		}
	// 		return;
	// 	}
		
	// 	$this->dpplog(9, "Inspecting destination $destination");
	
	// 	// We use get() to see if the node exists before creating it.  get() throws
	// 	// an exception if the node does not exist so we have to catch it.
	// 	try
	// 	{
	// 		$node = $dpgraph->get($destination);
	// 	}
	// 	catch (\Exception $e)
	// 	{
	// 		$this->dpplog(7, "Adding node: $destination");
	// 		$node = $dpgraph->beginNode($destination);
	// 		$node->attribute('margin', '.25,.055');
	// 	}
	
	// 	// Add an edge from our parent to this node, if there is not already one.
	// 	// We do this even if the node already existed because this node might
	// 	// have several paths to reach it.
	// 	$ptxt = $route['parent_node']->getAttribute('label', '');
	// 	$ntxt = $node->getAttribute('label', '');
	// 	$this->dpplog(9, "Found it: ntxt = $ntxt");
		
	// 	if ($ntxt == '' )
	// 	{
	// 		$ntxt = "(new node: $destination)";
	// 	}

	// 	if ($dpgraph->hasEdge(array($route['parent_node'], $node)))
	// 	{
	// 		$this->dpplog(9, "NOT making an edge from $ptxt -> $ntxt");
	// 		$edge= $dpgraph->beginEdge(array($route['parent_node'], $node));
	// 		$edge->attribute('label', $this->sanitizeLabels($route['parent_edge_label']));
	// 		$edge->attribute('labeltooltip', $this->sanitizeLabels($route['parent_edge_label']));
			
	// 	}
	// 	else
	// 	{
	// 		$this->dpplog(9, "Making an edge from $ptxt -> $ntxt");
	// 		$edge= $dpgraph->beginEdge(array($route['parent_node'], $node));
	// 		$edge->attribute('label', $this->sanitizeLabels($route['parent_edge_label']));
	// 		$edge->attribute('labeltooltip', $this->sanitizeLabels($route['parent_edge_label']));
			
	// 		if (preg_match("/^( Match| NoMatch)/", $route['parent_edge_label']))
	// 		{
	// 			$edge->attribute('URL', $route['parent_edge_url']);
	// 			$edge->attribute('target', $route['parent_edge_target']);
	// 		}
	// 		if (preg_match("/^( IVR)./", $route['parent_edge_label']))
	// 		{
	// 			$edge->attribute('style', 'dashed');
	// 		}
	// 		if (preg_match("/^ +$/", $route['parent_edge_label']))
	// 		{
	// 			$edge->attribute('style', 'dotted');
	// 		}
	// 	}
	
	// 	$this->dpplog(9, "The Graph: " . print_r($dpgraph, true));
	
	// 	// Now bail if we have already recursed on this destination before.
	// 	if ($node->getAttribute('label', 'NONE') != 'NONE')
	// 	{
	// 		return;
	// 	}
	
	// 	# Now look at the destination and figure out where to dig deeper.
	
	// 	#
	// 	# Announcements
	// 	#
	// 	if (preg_match("/^app-announcement-(\d+),s,(\d+)/", $destination, $matches))
	// 	{
	// 		$annum	 = $matches[1];
	// 		$another = $matches[2];
	
	// 		$an    = $route['announcements'][$annum];
	// 		$recID = $an['recording_id'];
			
	// 		$announcement = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
	// 		#feature code exist?
	// 		if ( isset($route['featurecodes']['*29'.$recID]) )
	// 		{
	// 			#custom feature code?
	// 			if ($route['featurecodes']['*29'.$an['recording_id']]['customcode']!='')
	// 			{
	// 				$featurenum = $route['featurecodes']['*29'.$an['recording_id']]['customcode'];
	// 			}
	// 			else
	// 			{
	// 				$featurenum = $route['featurecodes']['*29'.$an['recording_id']]['defaultcode'];
	// 			}
	// 			#is it enabled?
	// 			if ( ($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') )
	// 			{
	// 				$rec = '\\nRecord(yes): '.$featurenum;
	// 			}
	// 			else
	// 			{
	// 				$rec = '\\nRecord(no): '.$featurenum;
	// 			}
	// 		}
	// 		else
	// 		{
	// 			$rec='\\nRecord(no): disabled';
	// 		}
			
	// 		$node->attribute('label', 'Announcements: '.$this->sanitizeLabels($an['description']).'\\nRecording: '.$this->sanitizeLabels($announcement).$rec);
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=announcement&view=form&extdisplay='.$annum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'note');
	// 		$node->attribute('fillcolor', 'oldlace');
	// 		$node->attribute('style', 'filled');
	
	// 		# The destinations we need to follow are the no-answer destination
	// 		# (postdest) and the members of the group.
	
	// 		if ($an['post_dest'] != '')
	// 		{
	// 			$route['parent_edge_label'] = ' Continue';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $an['post_dest'],'');
	// 		}
	// 		# end of announcements
	
	// 	#
	// 	# Blackhole
	// 	#
	// 	}
	// 	elseif (preg_match("/^app-blackhole,(hangup|congestion|busy|zapateller|musiconhold|ring|no-service),(\d+)/", $destination, $matches))
	// 	{
	// 		$blackholetype  = str_replace('musiconhold','Music On Hold',$matches[1]);
	// 		$blackholeother = $matches[2];
	// 		$previousURL	= $route['parent_node']->getAttribute('URL', '');
	
	// 		$node->attribute('label', 'Terminate Call: '.ucwords($blackholetype,'-'));
	// 		$node->attribute('tooltip', 'Terminate Call: '.ucwords($blackholetype,'-'));
	// 		$node->attribute('URL', $previousURL);
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'invhouse');
	// 		$node->attribute('fillcolor', 'orangered');
	// 		$node->attribute('style', 'filled');
			
	// 		#end of Blackhole
	
	// 	#
	// 	# Call Flow Control (daynight)
	// 	#
	// 	}
	// 	elseif (preg_match("/^app-daynight,(\d+),(\d+)/", $destination, $matches)) 
	// 	{
	// 		$daynightnum   = $matches[1];
	// 		$daynightother = $matches[2];
	// 		$daynight 	   = $route['daynight'][$daynightnum];
		
	// 		#feature code exist?
	// 		if ( isset($route['featurecodes']['*28'.$daynightnum]) )
	// 		{
	// 			#custom feature code?
	// 			if ($route['featurecodes']['*28'.$daynightnum]['customcode'] != '')
	// 			{
	// 				$featurenum = $route['featurecodes']['*28'.$daynightnum]['customcode'];
	// 			}
	// 			else
	// 			{
	// 				$featurenum = $route['featurecodes']['*28'.$daynightnum]['defaultcode'];
	// 			}
	// 			#is it enabled?
	// 			if ($route['featurecodes']['*28'.$daynightnum]['enabled']=='1')
	// 			{
	// 				$code = '\\nToggle (enabled): '.$featurenum;
	// 			}
	// 			else
	// 			{
	// 				$code = '\\nToggle (disabled): '.$featurenum;
	// 			}
	// 		}
	// 		else
	// 		{
	// 			$code = '';
	// 		}
				
	// 		#check current status and set path to active
	// 		$C ='/usr/sbin/asterisk -rx "database show DAYNIGHT/C'.$daynightnum.'" | cut -d \':\' -f2 | tr -d \' \' | head -1';
	// 		exec($C, $current_daynight);
	// 		$dactive = $nactive = "";
	// 		if ($current_daynight[0]=='DAY')
	// 		{
	// 			$dactive = "(Active)";
	// 		}
	// 		else
	// 		{
	// 			$nactive = "(Active)";
	// 		}
		
	// 		foreach ($daynight as $d)
	// 		{
	// 			if ($d['dmode']=='day')
	// 			{
	// 				$route['parent_edge_label'] = ' Day Mode '.$dactive;
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $d['dest'],'');
	// 			}
	// 			elseif ($d['dmode']=='night')
	// 			{
	// 				$route['parent_edge_label'] = ' Night Mode '.$nactive;
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $d['dest'],'');
	// 			}
	// 			elseif ($d['dmode']=="fc_description")
	// 			{
	// 				$node->attribute('label', "Call Flow: ".$this->sanitizeLabels($d['dest']) .$code);
	// 			}
	// 		}
	// 		$daynight = $route['daynight'][$daynightnum];
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=daynight&view=form&itemid='.$daynightnum.'&extdisplay='.$daynightnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('fillcolor', self::pastels[14]);
	// 		$node->attribute('style', 'filled');
	// 		#end of Call Flow Control (daynight)
	
	// 	#
	// 	# Conferences (meetme)
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-meetme,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$meetmenum 	 = $matches[1];
	// 		$meetmeother = $matches[2];
	// 		$meetme 	 = $route['meetme'][$meetmenum];
	
	// 		$node->attribute('label', 'Conferences: '.$meetme['exten'].' '.$this->sanitizeLabels($meetme['description']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=conferences&view=form&extdisplay='.$meetmenum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('fillcolor', 'burlywood');
	// 		$node->attribute('style', 'filled');
	// 		#end of Conferences (meetme)
	
	// 	#
	// 	# Directory
	// 	#
	// 	}
	// 	elseif (preg_match("/^directory,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$directorynum 	= $matches[1];
	// 		$directoryother = $matches[2];
	// 		$directory 		= $route['directory'][$directorynum];
	
	// 		$node->attribute('label', 'Directory: '.$this->sanitizeLabels($directory['dirname']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=directory&view=form&id='.$directorynum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('fillcolor', self::pastels[9]);
	// 		$node->attribute('shape', 'folder');
	// 		$node->attribute('style', 'filled');
			
	// 		if ($directory['invalid_destination']!='')
	// 		{
	// 			$route['parent_edge_label']= ' Invalid Input';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $directory['invalid_destination'],'');
	// 		}
	// 		#end of Directory
	
	// 	#
	// 	# DISA
	// 	#
	// 	}
	// 	elseif (preg_match("/^disa,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$disanum   = $matches[1];
	// 		$disaother = $matches[2];
	// 		$disa 	   = $route['disa'][$disanum];
	
	// 		$node->attribute('label', 'DISA: '.$this->sanitizeLabels($disa['displayname']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=disa&view=form&itemid='.$disanum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('fillcolor', self::pastels[10]);
	// 		$node->attribute('style', 'filled');
	// 		#end of DISA
	
	// 	#
	// 	# Dynamic Routes
	// 	#
	// 	}
	// 	elseif (preg_match("/^dynroute-(\d+)/", $destination, $matches))
	// 	{
	// 		$dynnum = $matches[1];
	// 		$dynrt  = $route['dynroute'][$dynnum];
			
	// 		$recID = $dynrt['announcement_id'];
			
	// 		$announcement = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
	// 		$node->attribute('label', 'DYN: '.$this->sanitizeLabels($dynrt['name']).'\\nAnnouncement: '.$this->sanitizeLabels($announcement));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=dynroute&action=edit&id='.$dynnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'component');
	// 		$node->attribute('fillcolor', self::pastels[12]);
	// 		$node->attribute('style', 'filled');
	
	// 		if (!empty($dynrt['routes']))
	// 		{
	// 			ksort($dynrt['routes']);
	// 			foreach ($dynrt['routes'] as $selid => $ent)
	// 			{
	// 				$route['parent_edge_label']= '  Match: '.$this->sanitizeLabels($ent['selection']).'\\n'.$this->sanitizeLabels($ent['description']);
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $ent['dest'],'');
	// 			}
	// 		}
			
	// 		//are the invalid and timeout destinations the same?
	// 		if ($dynrt['invalid_dest']==$dynrt['default_dest'])
	// 		{
	// 			$route['parent_edge_label']= ' Invalid Input, Default ('.$dynrt['timeout'].' secs)';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $dynrt['invalid_dest'],'');
	// 		}
	// 		else
	// 		{
	// 			if ($dynrt['invalid_dest'] != '')
	// 			{
	// 				$route['parent_edge_label']= ' Invalid Input';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $dynrt['invalid_dest'],'');
	// 			}
	// 			if ($dynrt['default_dest'] != '')
	// 			{
	// 				$route['parent_edge_label']= ' Default ('.$dynrt['timeout'].' secs)';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $dynrt['default_dest'],'');
	// 			}
	// 		}
	// 		#end of Dynamic Routes
	
	// 	#
	// 	# Extension (from-did-direct)
	// 	#
	// 	}
	// 	elseif (preg_match("/^from-did-direct,(\d+),(\d+)/", $destination, $matches))
	// 	{
			
	// 		$extnum    = $matches[1];
	// 		$extother  = $matches[2];
	// 		$extension = $route['extensions'][$extnum];
	// 		$extname   = $extension['name'];
	// 		$extemail  = $extension['email'];
	// 		$extemail  = str_replace("|",",\\n",$extemail);
	
	// 		$node->attribute('label', 'Extension: '.$extnum.' '.$this->sanitizeLabels($extname).'\\n'.$this->sanitizeLabels($extemail));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$extnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'house');
	// 		$node->attribute('fillcolor', self::pastels[15]);
	// 		$node->attribute('style', 'filled');
			
	// 		//Optional Destinations
	// 		if (!empty($extension['noanswer_dest']) || !empty($extension['busy_dest']) || !empty($extension['chanunavail_dest']) )
	// 		{
	// 			if ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['noanswer_dest'] === $extension['chanunavail_dest'])
	// 			{
	// 				// All three are equal
	// 				$route['parent_edge_label'] = ' No Answer, Busy, Not Reachable';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $extension['noanswer_dest'],'');
	// 			}
	// 			elseif ($extension['noanswer_dest'] === $extension['busy_dest'] && $extension['chanunavail_dest'] !== $extension['noanswer_dest'])
	// 			{
	// 				// No Answer and Busy are the same, but Not Reachable is different
	// 				$route['parent_edge_label'] = ' No Answer & Busy';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $extension['noanswer_dest'],'');
	
	// 				if (!empty($extension['chanunavail_dest']))
	// 				{
	// 					$route['parent_edge_label'] = ' Not Reachable';
	// 					$route['parent_node'] = $node;
	// 					$this->dpp_follow_destinations($route, $extension['chanunavail_dest'],'');
	// 				}
	// 			}
	// 			elseif ($extension['noanswer_dest'] === $extension['chanunavail_dest'] && $extension['busy_dest'] !== $extension['noanswer_dest'])
	// 			{
	// 				// No Answer and Not Reachable are the same
	// 				$route['parent_edge_label'] = ' No Answer & Not Reachable';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $extension['noanswer_dest'],'');
	
	// 				if (!empty($extension['busy_dest'])) {
	// 					$route['parent_edge_label'] = ' Busy';
	// 					$route['parent_node'] = $node;
	// 					$this->dpp_follow_destinations($route, $extension['busy_dest'],'');
	// 				}
	// 			}
	// 			elseif ($extension['busy_dest'] === $extension['chanunavail_dest'] && $extension['noanswer_dest'] !== $extension['busy_dest'])
	// 			{
	// 				// Busy and Not Reachable are the same
	// 				$route['parent_edge_label'] = ' Busy & Not Reachable';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $extension['busy_dest'],'');
	
	// 				if (!empty($extension['noanswer_dest'])) {
	// 					$route['parent_edge_label'] = ' No Answer';
	// 					$route['parent_node'] = $node;
	// 					$this->dpp_follow_destinations($route, $extension['noanswer_dest'],'');
	// 				}
	// 			}
	// 			else
	// 			{
	// 				// All are different
	// 				if (!empty($extension['noanswer_dest'])) {
	// 					$route['parent_edge_label'] = ' No Answer';
	// 					$route['parent_node'] = $node;
	// 					$this->dpp_follow_destinations($route, $extension['noanswer_dest'],'');
	// 				}
	// 				if (!empty($extension['busy_dest'])) {
	// 					$route['parent_edge_label'] = ' Busy';
	// 					$route['parent_node'] = $node;
	// 					$this->dpp_follow_destinations($route, $extension['busy_dest'],'');
	// 				}
	// 				if (!empty($extension['chanunavail_dest'])) {
	// 					$route['parent_edge_label'] = ' Not Reachable';
	// 					$route['parent_node'] = $node;
	// 					$this->dpp_follow_destinations($route, $extension['chanunavail_dest'],'');
	// 				}
	// 			}
	// 		}
	// 		#end of Extension (from-did-direct)
	
	// 	#
	// 	# Feature Codes
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-featurecodes,(\*?\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$featurenum   = $matches[1];
	// 		$featureother = $matches[2];
	// 		$feature 	  = $route['featurecodes'][$featurenum];
			
	// 		if ($feature['customcode']!=''){$featurenum=$feature['customcode'];}
	// 		$node->attribute('label', 'Feature Code: '.$this->sanitizeLabels($feature['description']).' \\<'.$featurenum.'\\>');
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=featurecodeadmin'));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'folder');
	// 		$node->attribute('fillcolor', 'gainsboro');
	// 		$node->attribute('style', 'filled');
	// 		#end of Feature Codes
	
	// 	#
	// 	# Inbound Routes
	// 	#
	// 	}
	// 	elseif (preg_match("/^from-trunk,([^,]*),(\d+)/", $destination, $matches))
	// 	{
	// 		$num 	  = $matches[1];
	// 		$numother = $matches[2];
	
	// 		$incoming = $route['incoming'][$num];
			
	// 		$didLabel = ($num == '') ? 'ANY' : $this->formatPhoneNumbers($num);
	// 		$didLabel .="\n".$incoming['description'];
	// 		$didLink  = $num.'/';
			
	// 		$node->attribute('label', $this->sanitizeLabels($didLabel));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'cds');
	// 		$node->attribute('fillcolor', 'darkseagreen');
	// 		$node->attribute('style', 'filled');
			
	// 		$route['parent_edge_label']= ' Continue';
	// 		$route['parent_node'] = $node;
	// 		$this->dpp_follow_destinations($route, $incoming['destination'],'');
	
	// 		#end of Inbound Routes
	
	// 	#
	// 	# IVRs
	// 	#
	// 	}
	// 	elseif (preg_match("/^ivr-(\d+),([a-z]+),(\d+)/", $destination, $matches))
	// 	{
	// 		$inum   = $matches[1];
	// 		$iflag  = $matches[2];
	// 		$iother = $matches[3];
	
	// 		$ivr		= $route['ivrs'][$inum];
	// 		$recID		= $ivr['announcement'];
	// 		$ivrRecName = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
			
	// 		#feature code exist?
	// 		if ( isset($route['featurecodes']['*29'.$ivr['announcement']]) )
	// 		{
	// 			#custom feature code?
	// 			if ($route['featurecodes']['*29'.$ivr['announcement']]['customcode']!='')
	// 			{
	// 				$featurenum = $route['featurecodes']['*29'.$ivr['announcement']]['customcode'];
	// 			}
	// 			else
	// 			{
	// 				$featurenum = $route['featurecodes']['*29'.$ivr['announcement']]['defaultcode'];
	// 			}
	// 			#is it enabled?
	// 			if (($route['recordings'][$recID]['fcode']== '1') && ($route['featurecodes']['*29'.$recID]['enabled']=='1') )
	// 			{
	// 				$rec = '(yes): '.$featurenum;
	// 			}
	// 			else
	// 			{
	// 				$rec = '(no): '.$featurenum;
	// 			}
	// 		}
	// 		else
	// 		{
	// 			$rec = '(no): disabled';
	// 		}
	
	// 		$node->attribute('label', "IVR: ".$this->sanitizeLabels($ivr['name'])."\\nAnnouncement: ".$this->sanitizeLabels($ivrRecName)."\\lRecord ".$rec."\\l");
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=ivr&action=edit&id='.$inum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'component');
	// 		$node->attribute('fillcolor', 'gold');
	// 		$node->attribute('style', 'filled');
	
	// 		# The destinations we need to follow are the invalid_destination,
	// 		# timeout_destination, and the selection targets
			
			
	// 		#now go through the selections
	// 		if (!empty($ivr['entries']))
	// 		{
	// 			ksort($ivr['entries']);
	// 			foreach ($ivr['entries'] as $selid => $ent)
	// 			{
	// 				$route['parent_edge_label']= ' Selection '.$this->sanitizeLabels($ent['selection']);
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $ent['dest'],'');
	// 			}
	// 		}
			
	// 		#are the invalid and timeout destinations the same?
	// 		if ($ivr['invalid_destination']==$ivr['timeout_destination'])
	// 		{
	// 			$route['parent_edge_label']= ' Invalid Input, Timeout ('.$ivr['timeout_time'].' secs)';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $ivr['invalid_destination'],'');
	// 		}
	// 		else
	// 		{
	// 			if ($ivr['invalid_destination'] != '') 
	// 			{
	// 				$route['parent_edge_label']= ' Invalid Input';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $ivr['invalid_destination'],'');
	// 			}
	// 			if ($ivr['timeout_destination'] != '') {
	// 				$route['parent_edge_label']= ' Timeout ('.$ivr['timeout_time'].' secs)';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, $ivr['timeout_destination'],'');
	// 			}
	// 		}
	// 		# end of IVRs
	
	// 	#
	// 	# Languages
	// 	#
	// 	}
	// 	elseif (preg_match("/^app-languages,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$langnum   = $matches[1];
	// 		$langother = $matches[2];
	
	// 		$lang = $route['languages'][$langnum];
	// 		$node->attribute('label', 'Languages: '.$this->sanitizeLabels($lang['description']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=languages&view=form&extdisplay='.$langnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'note');
	// 		$node->attribute('fillcolor', self::pastels[6]);
	// 		$node->attribute('style', 'filled');
	
	// 		if ($lang['dest'] != '')
	// 		{
	// 			$route['parent_edge_label'] = ' Continue';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $lang['dest'],'');
	// 		}
	// 		#end of Languages
	
	// 	#
	// 	# MISC Destinations
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-miscdests,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$miscdestnum   = $matches[1];
	// 		$miscdestother = $matches[2];
	
	// 		$miscdest = $route['miscdest'][$miscdestnum];
	// 		$node->attribute('label', 'Misc Dest: '.$this->sanitizeLabels($miscdest['description']).' ('.$miscdest['destdial'].')');
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=miscdests&view=form&extdisplay='.$miscdestnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'rpromoter');
	// 		$node->attribute('fillcolor', 'coral');
	// 		$node->attribute('style', 'filled');
	// 		#end of MISC Destinations

	// 	#
	// 	# Play Recording
	// 	#
	// 	}
	// 	elseif (preg_match("/^play-system-recording,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$recID 		= $matches[1];
	// 		$recIDOther = $matches[2];

	// 		$playName = isset($route['recordings'][$recID]) ? $route['recordings'][$recID]['displayname'] : 'None';
	// 		$node->attribute('label', 'Play Recording: '.$this->sanitizeLabels($playName));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=recordings&action=edit&id='.$recID));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'rect');
	// 		$node->attribute('fillcolor', self::pastels['16']);
	// 		$node->attribute('style', 'filled');
	// 		#end of Play Recording
	
	// 	#
	// 	# Queues
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-queues,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$qnum   = $matches[1];
	// 		$qother = $matches[2];
	
	// 		$q = $route['queues'][$qnum];
	// 		if ($q['maxwait'] == 0 || $q['maxwait'] == '' || !is_numeric($q['maxwait']))
	// 		{
	// 			$maxwait = 'Unlimited';
	// 		}
	// 		else
	// 		{
	// 			$maxwait = $this->secondsToTimes($q['maxwait']);
	// 		}

	// 		$node->attribute('label', 'Queue '.$qnum.': '.$this->sanitizeLabels($q['descr']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=queues&view=form&extdisplay='.$qnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'hexagon');
	// 		$node->attribute('fillcolor', 'mediumaquamarine');
	// 		$node->attribute('style', 'filled');
				
	// 		if (!empty($q['members']))
	// 		{
	// 			foreach ($q['members'] as $types=>$type)
	// 			{
	// 				foreach ($type as $members)
	// 				{
	// 					$route['parent_node'] = $node;
	// 					$route['parent_edge_label'] = ($types == 'static') ? ' Static' : ' Dynamic';
	// 					$this->dpp_follow_destinations($route, 'qmember'.$members,'');
	// 				}
	// 			}
	// 		}
			
	// 		# The destinations we need to follow are the queue members (extensions)
	// 		# and the no-answer destination.
	// 		if ($q['dest'] != '') 
	// 		{
	// 			$route['parent_edge_label'] = ' No Answer ('.$maxwait.')';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $q['dest'],'');
	// 		}
			
	// 		if (is_numeric($q['ivr_id']))
	// 		{
	// 			$route['parent_edge_label'] = ' IVR Break Out (every '.$this->secondsToTimes($q['data']['min-announce-frequency']).')';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, 'ivr-'.$q['ivr_id'].',s,1','');
	// 		}
	// 		#end of Queues
			
	// 	#
	// 	# Queue members (static and dynamic)
	// 	#
	// 	}
	// 	elseif (preg_match("/^qmember(\d+)/", $destination, $matches))
	// 	{
	// 		$qextension = $matches[1];
	// 		$qlabel = isset($route['extensions'][$qextension]['name']) ? 'Ext '.$qextension.'\\n'.$route['extensions'][$qextension]['name'] : $qextension;
	// 		$node->attribute('label', $this->sanitizeLabels($qlabel));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		if (!is_numeric($qlabel))
	// 		{
	// 			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$qextension));
	// 			$node->attribute('target', '_blank');
	// 		}
			
	// 		if ($route['parent_edge_label'] == ' Static')
	// 		{
	// 			$node->attribute('fillcolor', self::pastels[20]);
	// 		}
	// 		else
	// 		{
	// 			$node->attribute('fillcolor', self::pastels[8]);
	// 		}
	// 		$node->attribute('style', 'filled');
			
	// 		#end of Queue members (static and dynamic)
	
	// 	#
	// 	# Ring Groups
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-group,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$rgnum 	 = $matches[1];
	// 		$rgother = $matches[2];
		
	// 		$rg = $route['ringgroups'][$rgnum];
	// 		$node->attribute('label', 'Ring Groups: '.$rgnum.' '.$this->sanitizeLabels($rg['description']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=ringgroups&view=form&extdisplay='.$rgnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('fillcolor', self::pastels[12]);
	// 		$node->attribute('style', 'filled');
			
	// 		$grplist = preg_split("/-/", $rg['grplist']);
		
	// 		foreach ($grplist as $member)
	// 		{
	// 			$route['parent_node'] = $node;
	// 			$route['parent_edge_label'] = '';
	// 			$this->dpp_follow_destinations($route, "rg$member",'');
	// 		} 
			
	// 		# The destinations we need to follow are the no-answer destination
	// 		# (postdest) and the members of the group.
	// 		if ($rg['postdest'] != '')
	// 		{
	// 			$route['parent_edge_label'] = ' No Answer ('.$this->secondsToTimes($rg['grptime']).')';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $rg['postdest'],'');
	// 		}
	// 		# End of Ring Groups
		
	// 	#
	// 	# Ring Group Members
	// 	#
	// 	}
	// 	elseif (preg_match("/^rg(\d+)/", $destination, $matches))
	// 	{
	// 		$rgext   = $matches[1];
	// 		$rglabel = isset($route['extensions'][$rgext]) ? 'Ext '.$rgext.'\\n'.$route['extensions'][$rgext]['name'] : $rgext;
	
	// 		$node->attribute('label', $this->sanitizeLabels($rglabel));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		if (!is_numeric($rglabel))
	// 		{
	// 			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$rgext));
	// 			$node->attribute('target', '_blank');
	// 		}
	// 		$node->attribute('fillcolor', self::pastels[2]);
	// 		$node->attribute('style', 'filled');
	// 		# end of ring group members
	
	// 	#
	// 	# Set CID
	// 	#
	// 	}
	// 	elseif (preg_match("/^app-setcid,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$cidnum   = $matches[1];
	// 		$cidother = $matches[2];
	// 		$cid 	  = $route['setcid'][$cidnum];
	// 		$cidLabel = 'Set CID\\nName= '.preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name']).'\\lNumber= '.preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']).'\\l';
	
	// 		$node->attribute('label', $this->sanitizeLabels($cidLabel));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=setcid&view=form&id='.$cidnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'note');
	// 		$node->attribute('fillcolor', self::pastels[6]);
	// 		$node->attribute('style', 'filled');
	
	// 		if ($cid['dest'] != '')
	// 		{
	// 			$route['parent_edge_label'] = ' Continue';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $cid['dest'],'');
	// 		}
	// 		#end of Set CID
			
	// 	#
	// 	# TTS
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-tts,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$ttsnum 	= $matches[1];
	// 		$ttsother 	= $matches[2];
	// 		$tts 		= $route['tts'][$ttsnum];
	// 		$ttsLabel	= 'TTS: '.$tts['name'];
	// 		$ttsTooltip = 'Engine: '.$tts['engine'].'\\nDesc: '.$tts['text'];
			
	// 		$node->attribute('label', $this->sanitizeLabels($ttsLabel));
	// 		$node->attribute('tooltip', $this->sanitizeLabels($ttsTooltip));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=tts&view=form&id='.$ttsnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'note');
	// 		$node->attribute('fillcolor', self::pastels[6]);
	// 		$node->attribute('style', 'filled');
	
	// 		if ($tts['goto'] != '') 
	// 		{
	// 			$route['parent_edge_label'] = ' Continue';
	// 			$route['parent_node'] = $node;
	// 			$this->dpp_follow_destinations($route, $tts['goto'],'');
	// 		}
	// 		#end of TTS
			
	// 	#
	// 	# Time Conditions
	// 	#
	// 	}
	// 	elseif (preg_match("/^timeconditions,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$tcnum 	 = $matches[1];
	// 		$tcother = $matches[2];
	
	// 		$tc = $route['timeconditions'][$tcnum];
	// 		$node->attribute('label', "TC: ".$this->sanitizeLabels($tc['displayname']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=timeconditions&view=form&itemid='.$tcnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'invhouse');
	// 		$node->attribute('fillcolor', 'dodgerblue');
	// 		$node->attribute('style', 'filled');
		
	// 		# Not going to use the time group info for right now.  Maybe put it in the edge text?
	// 		$tgname = $route['timegroups'][$tc['time']]['description'];
	// 		$tgtime = $route['timegroups'][$tc['time']]['time'];
	// 		$tgnum = $route['timegroups'][$tc['time']]['id'];
			
	// 		# Now set the current node to be the parent and recurse on both the true and false branches
	// 		$route['parent_edge_label'] = ' Match:\\n'.$this->sanitizeLabels($tgname).'\\n'.$tgtime;
	// 		$route['parent_edge_url'] = htmlentities('/admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum);
	// 		$route['parent_edge_target'] = '_blank';
	
	// 		$route['parent_node'] = $node;
	// 		$this->dpp_follow_destinations($route, $tc['truegoto'],'');
	
	// 		$route['parent_edge_label'] = ' NoMatch';
	// 		$route['parent_edge_url'] = htmlentities('/admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum);
	// 		$route['parent_edge_target'] = '_blank';
	// 		$route['parent_node'] = $node;
	// 		$this->dpp_follow_destinations($route, $tc['falsegoto'],'');
	// 		#end of Time Conditions
	
	// 	#
	// 	# Voicemail
	// 	#
	// 	}
	// 	elseif (preg_match("/^ext-local,vm([b,i,s,u])(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$vmtype	 = $matches[1];
	// 		$vmnum 	 = $matches[2];
	// 		$vmother = $matches[3];
			
	// 		$vm_array=array('b'=>'(Busy Message)','i'=>'(Instructions Only)','s'=>'(No Message)','u'=>'(Unavailable Message)' );
	// 		$vmname= $route['extensions'][$vmnum]['name'];
	// 		$vmemail= $route['extensions'][$vmnum]['email'];
	// 		$vmemail= str_replace("|",",\\n",$vmemail);
		
	// 		$node->attribute('label', 'Voicemail: '.$vmnum.' '.$this->sanitizeLabels($vmname).' '.$vm_array[$vmtype].'\\n'.$this->sanitizeLabels($vmemail));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$vmnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'folder');
	// 		$node->attribute('fillcolor', self::pastels[11]);
	// 		$node->attribute('style', 'filled');
	// 		#end of Voicemail
		
	// 	#
	// 	# VM Blast
	// 	#
	// 	}
	// 	elseif (preg_match("/^vmblast\-grp,(\d+),(\d+)/", $destination, $matches))
	// 	{
	// 		$vmblastnum   = $matches[1];
	// 		$vmblastother = $matches[2];
	// 		$vmblast 	  = $route['vmblasts'][$vmblastnum];
			
	// 		$node->attribute('label', 'VM Blast: '.$vmblastnum.' '.$this->sanitizeLabels($vmblast['description']));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=vmblast&view=form&extdisplay='.$vmblastnum));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'folder');
	// 		$node->attribute('fillcolor', 'gainsboro');
	// 		$node->attribute('style', 'filled');
			
	// 		if (!empty($vmblast['members']))
	// 		{
	// 			foreach ($vmblast['members'] as $member)
	// 			{
	// 				$route['parent_edge_label']= '';
	// 				$route['parent_node'] = $node;
	// 				$this->dpp_follow_destinations($route, 'vmblast-mem,'.$member,'');
	// 			}
	// 		}
	// 		#end of VM Blast
			
	// 	#VM Blast members
	// 	}
	// 	elseif (preg_match("/^vmblast\-mem,(\d+)/", $destination, $matches))
	// 	{
	// 		$member 	  = $matches[1];
	// 		$vmblastname  = $route['extensions'][$member]['name'];
	// 		$vmblastemail = $route['extensions'][$member]['email'];
	// 		$vmblastemail = str_replace("|",",\\n",$vmblastemail);

	// 		$node->attribute('label', 'Ext '.$member.' '.$this->sanitizeLabels($vmblastname).'\\n'.$this->sanitizeLabels($vmblastemail));
	// 		$node->attribute('tooltip', $node->getAttribute('label'));
	// 		$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$member));
	// 		$node->attribute('target', '_blank');
	// 		$node->attribute('shape', 'rect');
	// 		$node->attribute('fillcolor', self::pastels['16']);
	// 		$node->attribute('style', 'filled');
		
	// 	#preg_match not found
	// 	}
	// 	else
	// 	{
	// 		#custom destinations
	// 		foreach ($route['customapps'] as $entry)
	// 		{
	// 			if ($entry['target'] === $destination)
	// 			{
	// 				$custDest = $entry;
	// 				break;
	// 			}
	// 		}
	// 		#end of Custom Destinations
			
	// 		if (!empty($custDest))
	// 		{
	// 			$custId	   = $entry['destid'];
	// 			$custLabel = 'Cust Dest: '.$entry['description'].'\\nTarget: '.$entry['target'].'\\l';
	// 			$custNotes = $entry['notes'];
				
	// 			$node->attribute('label', $this->sanitizeLabels($custLabel));
	// 			if (empty($custNotes))
	// 			{
	// 				$node->attribute('tooltip', $node->getAttribute('label'));
	// 			}
	// 			else
	// 			{
	// 				$node->attribute('tooltip', $this->sanitizeLabels($entry['notes']));
	// 			}
	// 			$node->attribute('URL', htmlentities('/admin/config.php?display=customdests&view=form&destid='.$custId));
	// 			$node->attribute('target', '_blank');
	// 			$node->attribute('shape', 'component');
	// 			$node->attribute('fillcolor', self::pastels[27]);
	// 			$node->attribute('style', 'filled');
	// 		}
	// 		else
	// 		{
	// 			$this->dpplog(1, "Unknown destination type: $destination");
	// 			$node->attribute('fillcolor', self::pastels[12]);
	// 			$node->attribute('label', $this->sanitizeLabels($destination));
	// 			$node->attribute('style', 'filled');
	// 		}
	// 	}
	// }





	// public function formatPhoneNumbers($phoneNumber)
	// {
	// 	$phoneNumber = preg_replace('/[^0-9]/','',$phoneNumber);
	
	// 	if(strlen($phoneNumber) > 10) {
	// 		$countryCode = substr($phoneNumber, 0, strlen($phoneNumber)-10);
	// 		$areaCode 	 = substr($phoneNumber, -10, 3);
	// 		$nextThree 	 = substr($phoneNumber, -7, 3);
	// 		$lastFour 	 = substr($phoneNumber, -4, 4);
	
	// 		$phoneNumber = sprintf("+%s (%s) %s-%s", $countryCode, $areaCode, $nextThree, $lastFour);
	// 	}
	// 	else if(strlen($phoneNumber) == 10)
	// 	{
	// 		$areaCode  = substr($phoneNumber, 0, 3);
	// 		$nextThree = substr($phoneNumber, 3, 3);
	// 		$lastFour  = substr($phoneNumber, 6, 4);
	
	// 		$phoneNumber = sprintf("(%s) %s-%s", $areaCode, $nextThree, $lastFour);
	// 	}
	// 	return $phoneNumber;
	// }

	// public function sanitizeLabels($text)
	// {
	// 	if ($text === null) {
	// 		$text = '';
	// 	}

	// 	// Convert HTML special characters
	// 	$text = htmlentities($text, ENT_QUOTES, 'UTF-8');
	
	// 	// Replace actual newlines with Graphviz-style escaped newline
	// 	$text = str_replace(["\r\n", "\r", "\n"], '\\n', $text);
	
	// 	return $text;
	// }

	// public function secondsToTimes($seconds)
	// {
	// 	$seconds = (int) round($seconds); // Ensure whole number input
	
	// 	$hours   = (int) ($seconds / 3600);
	// 	$minutes = (int) (($seconds % 3600) / 60);
	// 	$seconds = $seconds % 60;
	
	// 	return $hours > 0 ? "$hours hrs, $minutes mins" : ($minutes > 0 ? "$minutes mins, $seconds secs" : "$seconds secs");
	// }
	
}