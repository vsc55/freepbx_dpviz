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
		$this->dpp 	   = new \FreePBX\modules\Dpviz\dpp($this->freepbx);
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
}