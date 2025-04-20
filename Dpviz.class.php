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

	Const default_setting = [
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
		// Save the default configuration in the kvstore.
		$this->setSettingAll($this->getSettingAll());
    }

    public function uninstall() {
        // Required by BMO, but can remain empty
    }
	
	/**
	 * Get all configuration values.
	 * @return array An associative array of configuration settings, with default values if not set.
	 */
	public function getSettingAll(): array
	{
		$settings = $this->getAll("setting");

		// Filter only valid keys defined in default_setting
		$settings = array_merge(self::default_setting, $settings);

		// Direction: LR (left-to-right) o TB (top-to-bottom)
		$settings['direction'] = ($settings['horizontal']) == 1 ? 'LR' : 'TB';
		return $settings;
	}

	/**
	 * Set multiple configuration values at once.
	 * @param array $settings An associative array of configuration settings to set.
	 * @return bool True if the configuration was set successfully, false otherwise.
	 * @throws \Exception If the settings is not an array.
	 */
	public function setSettingAll(array $settings): bool
	{
		if (empty($settings))
		{
			return false;
		}

		// Filter only valid keys defined in default_setting
		$validSettings = array_intersect_key($settings, self::default_setting);
		foreach ($validSettings as $key => $val)
		{
			if (is_null($val))
			{
				$val = self::default_setting[$key];
			}
			parent::setConfig($key, $val, "setting");
		}
		return true;
	}

	/**
	 * Get a configuration value.
	 * @param string $key The configuration key to retrieve.
	 * @param string|null $default The default value to return if the key does not exist.
	 * @return string|null The configuration value or null if not found.
	 * @throws \Exception If the key is not a string.
	 */
	public function getSetting(string $key, ?string $default = null): ?string
	{
		if ($key === '') {
			return $default ?? null;
		}
		$settings = $this->getSettingAll();
		return $settings[$key] ?? $default ?? (self::default_setting[$key] ?? null);
	}

	/**
	 * Set a configuration value.
	 * @param string $key The configuration key to set.
	 * @param string|null $val The value to set. If null, the key will be deleted.
	 * @return bool True if the configuration was set successfully, false otherwise.
	 */
	public function setSetting(string $key, ?string $val): bool
	{
		if ($key == '')
		{
			return false;
		}
		parent::setConfig($key, $val, "setting");
		return true;
	}

	/**
	 * Reset the configuration to default values.
	 * This will delete the current configuration and set it to the default values.
	 * @return bool
	 */
	public function resetSetting(): bool
	{
		$this->delById('setting');
		$this->setSettingAll($this->getSettingAll());
		return true;
	}
	
    public function doConfigPageInit($page) {
        $request = freepbxGetSanitizedRequest();
		// $request = $_REQUEST;
        $action	 = $request['action'] ?? '';

        switch ($action)
		{
            case 'edit':
				//TODO: Implement via AJAX
				if (isset($request['reset']))
				{
					$this->resetSetting();
				}
				else
				{
					$new_setting = array_intersect_key($request, self::default_setting);
					if (!empty($new_setting))
					{
						$this->setSettingAll($new_setting);
					}
				}
                break;

            default:
                break;
        }
    }

	public function showPage($page, $params = array())
	{
		$request = freepbxGetSanitizedRequest();
		// $request = $_REQUEST;

		$setting = $this->getSettingAll();

		$data = array(
			"dpviz"	  	 => $this,
			'request' 	 => $request,
			'page' 	  	 => $page ?? '',
			'setting' 	 => $setting,
			// 'options' 	 => $options,
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
				$data['datetime'] 			= $setting['datetime'];
				$data['horizontal'] 		= $setting['horizontal'];
				$data['panzoom'] 			= $setting['panzoom'];
				$data['destinationColumn'] 	= $setting['destination'];
				$data['scale'] 				= $setting['scale'];
				$data['dynmembers'] 		= $setting['dynmembers'];
				$data['direction'] 			= $setting['direction'];
				$data['clickedNodeTitle'] 	= $request['clickedNodeTitle'] ?? '';
				
				$data_return = load_view(__DIR__."/views/view.options.php", $data);
				break;

			case 'dialplan':
				$data['iroute'] 	= sprintf("%s%s", $data['extdisplay'], $data['cid']);
				$data['datetime'] 	= $setting['datetime'];
				$data['scale'] 		= $setting['scale'];
				$data['panzoom'] 	= $setting['panzoom'];
				$data['direction'] 	= $setting['direction'];

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
		$data = array(
		 	"dpviz"   			=> $this,
		 	"request" 			=> $request,
			'setting' 			=> $this->getSettingAll(),
		 	"display" 			=> strtolower(trim($request['display'] ?? '')),
		 	'destinationColumn' => $this->getSetting('destination') == 1 ? true : false,
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