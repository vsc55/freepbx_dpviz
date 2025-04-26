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

	Const default_setting = [
		'panzoom'	  		 => 1,
		'horizontal'  		 => 0,
		'datetime'	  		 => 1,
		'scale'		  		 => 1,
		'dynmembers'  		 => 0,
		'combine_queue_ring' => 0,
		'ext_optional' 		 => 0,
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

		$settings = $this->getSettingAll();

		$data = array(
			"dpviz"	  	 => $this,
			'request' 	 => $request,
			'page' 	  	 => $page ?? '',
			'settings' 	 => $settings,
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
				// $data['datetime'] 			= $settings['datetime'];
				$data['horizontal'] 		= $settings['horizontal'];
				$data['panzoom'] 			= $settings['panzoom'];
				$data['scale'] 				= $settings['scale'];
				$data['dynmembers'] 		= $settings['dynmembers'];
				$data['direction'] 			= $settings['direction'];
				$data['clickedNodeTitle'] 	= $request['clickedNodeTitle'] ?? '';
				
				$data_return = load_view(__DIR__."/views/view.options.php", $data);
				break;

			case 'dialplan':
				$data['iroute'] 	  = sprintf("%s%s", $data['extdisplay'], $data['cid']);
				$data['isExistRoute'] = $this->dpp->isExistRoute($data['iroute']);

				if (!isset($_GET['extdisplay']))
				{
					$data_return = load_view(__DIR__."/views/view.dialplan.select.null.php", $data);
				}
				else if (! $data['isExistRoute'])
				{
					$data_return = load_view(__DIR__."/views/view.dialplan.err.route.php", $data);
				}
				else
				{
					$data['datetime'] 	= $settings['datetime'];
					$data['scale'] 		= $settings['scale'];
					$data['panzoom'] 	= $settings['panzoom'];
					$data['direction'] 	= $settings['direction'];

					$this->dpp->setDirection($data['direction']);

					$data['clickedNodeTitle'] = $request['clickedNodeTitle'] ?? '';
					$data['filename'] 		  = ($data['iroute'] == '') ? 'ANY.png' : sprintf("%s.png", $data['iroute']);
					$data['isExistRoute'] 	  = $this->dpp->isExistRoute($data['iroute']);

					if (is_numeric($data['extdisplay']) && (strlen($data['extdisplay'])==10 || strlen($data['extdisplay'])==11))
					{
						$data['number'] = $this->dpp->formatPhoneNumbers($data['extdisplay']);
					}
					else
					{
						$data['number'] = $data['extdisplay'];
					}

					$gtext = $this->dpp->render($data['iroute'], $data['clickedNodeTitle']);
					$this->dpp->log(5, sprintf("Dial Plan Graph for %s %s:\n%s", $data['extdisplay'], $data['cid'], $gtext));
					$gtext = str_replace(["\n","+"], ["\\n","\\+"], $gtext);  // ugh, apparently viz chokes on newlines, wtf?
					$data['gtext'] = $gtext;

					$data_return = load_view(__DIR__."/views/view.dialplan.php", $data);
				}
				break;

			default:
				$data_return = sprintf(_("❌Unknown page: %s"), $page);
				break;
		}
		return $data_return;
	}

	public function getRightNav($request, $params = array())
	{
		$data = array(
			'request'  => $request,
			'url_ajax' => 'ajax.php?module=core&amp;command=getJSON&amp;jdata=allDID'
		);
		$data = array_merge($data, $params);
		return load_view(__DIR__.'/views/rnav.php', $data);
	}
		
	public function ajaxRequest($req, &$setting)
	{
		// ** Allow remote consultation with Postman, debugging, etc. **
		// ********************************************
		// $setting['authenticate'] = false;
		// $setting['allowremote'] = true;
		// return true;
		// ********************************************
		switch ($req)
		{
			case 'reset_setting_default':
			case 'save_settings':
			case 'get_destinations':
			case 'check_update':
				return true;
				break;
		}
		return false;
	}

	public function ajaxHandler()
	{
		$request = freepbxGetSanitizedRequest();
		$command = $request['command'] ?? '';
		$data_return = false;
		switch ($command)
		{
			case 'save_settings':

				$new_setting = $request['data'] ?? [];
				if ($this->setSettingAll($new_setting))
				{
					$data_return = [
						'status' => 'success',
						'message' => _('✔ Settings saved successfully')
					];
				}
				else
				{
					$data_return = [
						'status' => 'error',
						'message' => _('❌ Failed to save settings')
					];
				}
				break;

			case 'reset_setting_default':
				// Reset the settings to default values
				$this->resetSetting();
				$data_return = [
					'status' => "success",
					'message' => _('✔ Settings reset to default values')
				];
				break;

			case 'get_destinations':
				$data_return = [
					'status' => 'success',
					'message' => '',
					'destinations' => $this->freepbx->Modules->getDestinations()
				];
				break;

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
					if ($result['up_to_date'])
					{
						$data_return['message'] = sprintf(_('✔ You are using the latest version: %s'), $result['current']);
					}
					else
					{
						$data_return['message'] = sprintf(_('⚠ A new version is available: %s, current version: %s'), $result['latest'], $result['current']);
					}
				}
				break;

			default:
				$data_return = ['status' => 'error', 'message' => _('❌ Unknown command')];
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