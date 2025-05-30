<?php

// License for all code of this FreePBX module can be found in the license file inside the module directory
// Copyright 2015 Sangoma Technologies.
// vim: set ai ts=4 sw=4 ft=php:

namespace FreePBX\modules;

include_once __DIR__ . '/Dpp.php';

use FreePBX\Modules\Dpviz\Dpp;

class Dpviz extends \FreePBX_Helpers implements \BMO
{
    private $freepbx;
    private $db;

    public $astman = null;
    public $dpp    = null;

    public const DEFAULT_SETTING = [
        'panzoom'            => 1,
        'horizontal'         => 0,
        'datetime'           => 1,
        'dynmembers'         => 0,
        'combine_queue_ring' => 0,
        'ext_optional'       => 0,
        'fmfm'               => 0,
    ];
    public const RECORDING_LANG_DEFAULT   = 'en';
    public const RECORDING_FORMAT_DEFAULT = 'wav';
    public const RECORDING_FORMAT_ALLOW   = ['wav'];

    public function __construct($freepbx = null)
    {
        include_once __DIR__ . '/vendor/autoload.php';

        if ($freepbx == null) {
            throw new \Exception("Not given a FreePBX Object");
        }

        // parent::__construct($freepbx);
        $this->freepbx = $freepbx;
        $this->db      = $freepbx->Database;
        $this->astman  = $freepbx->astman;
        $this->dpp     = new Dpp($this->freepbx, $this);
    }

    public function install()
    {
        // Save the default configuration in the kvstore.
        $this->setSettingAll($this->getSettingAll());
    }

    public function uninstall()
    {
    }


    /**
     * Hook to be called when the module is loaded.
     * This is where you can set up any necessary dependencies or configurations.
     */
    protected function hookGetRecording($id): array
    {
        // $data_return = \FreePBX::Recordings()->getRecordingById($id);
        $data_return = [];
        try {
            $recordings  = \FreePBX::create()->Recordings;
            $data_return = $recordings->getRecordingById($id);
        } catch (\Exception $e) {
            freepbx_log(FPBX_LOG_ERROR, "Recordings is missing, please install it.");
        }
        return $data_return;
    }

    /**
     * Hook to be called when the module is loaded.
     * This is where you can set up any necessary dependencies or configurations.
     */
    protected function hookGetSoundlang(): string
    {
        try {
            $soundlang = \FreePBX::create()->Soundlang;
            return $soundlang->getLanguage();
        } catch (\Exception $e) {
            freepbx_log(FPBX_LOG_ERROR, "Soundlang is missing, please install it.");
            return self::RECORDING_LANG_DEFAULT;
        }
    }

    /**
     * Get all configuration values.
     * @return array An associative array of configuration settings, with default values if not set.
     */
    public function getSettingAll(): array
    {
        $settings = $this->getAll("setting");

        // Filter only valid keys defined in DEFAULT_SETTING
        $settings = array_merge(self::DEFAULT_SETTING, $settings);

        // Direction: LR (left-to-right) o TB (top-to-bottom)
        $settings['direction'] = ($settings['horizontal']) == 1 ? 'LR' : 'TB';
        $Setting['lang']       = $this->hookGetSoundlang();

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
        if (empty($settings)) {
            return false;
        }

        // Filter only valid keys defined in DEFAULT_SETTING
        $validSettings = array_intersect_key($settings, self::DEFAULT_SETTING);
        foreach ($validSettings as $key => $val) {
            if (is_null($val)) {
                $val = self::DEFAULT_SETTING[$key];
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
        return $settings[$key] ?? $default ?? (self::DEFAULT_SETTING[$key] ?? null);
    }

    /**
     * Set a configuration value.
     * @param string $key The configuration key to set.
     * @param string|null $val The value to set. If null, the key will be deleted.
     * @return bool True if the configuration was set successfully, false otherwise.
     */
    public function setSetting(string $key, ?string $val): bool
    {
        if ($key == '') {
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

    public function getDefualtLanguage(): string
    {
        return self::RECORDING_LANG_DEFAULT;
    }

    public function doConfigPageInit($page)
    {
        // $request = $_REQUEST;
        $request = freepbxGetSanitizedRequest();
        $action  = $request['action'] ?? '';

        switch ($action) {
            case 'edit':
                //TODO: Implement via AJAX
                if (isset($request['reset'])) {
                    $this->resetSetting();
                } else {
                    $new_setting = array_intersect_key($request, self::DEFAULT_SETTING);
                    if (!empty($new_setting)) {
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
            "dpviz"      => $this,
            'request'    => $request,
            'page'       => $page ?? '',
            'settings'   => $settings,
            'extdisplay' => $request['extdisplay'] ?? '',
            'cid'        => $request['cid'] ?? '',
        );

        $data = array_merge($data, $params);
        switch ($page) {
            case 'main':
                $data['action'] = $request['action'] ?? '';

                $data_return = load_view(__DIR__ . "/views/page.main.php", $data);
                break;

            case 'NavAndUsage':
                $data_return = load_view(__DIR__ . "/views/view.nav_and_usage.php", $data);
                break;

            case 'options':
                // definition the parameters for the dynamic generation of the options list of the settings tab
                $data['tab']['settings'] = array();
                foreach ($settings as $key => $val) {
                    switch ($key) {
                        case 'datetime':
                            $data['tab']['settings']["0"] = array(
                                'type'  => 'checkbox',
                                'label' => _("Date & Time Stamp"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("Displays the date and time on the graph."),
                            );
                            break;

                        case 'panzoom':
                            $data['tab']['settings']["1"] = array(
                                'type'  => 'checkbox',
                                'label' => _("Pan & Zoom"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("Allows you to use pan and zoom functions. Click and hold to pan, and use the mouse wheel to zoom."),
                            );
                            break;

                        case 'horizontal':
                            $data['tab']['settings']["2"] = array(
                                'type'  => 'checkbox',
                                'label' => _("Horizontal Layout"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("Displays the dial plan in a horizontal layout."),
                            );
                            break;

                        case 'combine_queue_ring':
                            $data['tab']['settings']["3"] = array(
                                'type'  => 'radioset',
                                'label' => _("Shared extension node handling"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("'None' displays individual extension nodes. 'Queues and Ring Groups Only' combines them into one node. 'All' merges all destinations into a single extension node."),
                                'options' => array(
                                    sprintf('%s_none', $key) => [
                                        'value' => '0',
                                        'label' => _("None"),
                                    ],
                                    sprintf('%s_only', $key) => [
                                        'value' => '1',
                                        'label' => _("Queues and Ring Groups Only"),
                                    ],
                                    sprintf('%s_all', $key) => [
                                        'value' => '2',
                                        'label' => _("All Destinations"),
                                    ],
                                ),
                            );
                            break;

                        case 'dynmembers':
                            $data['tab']['settings']["4"] = array(
                                'type'  => 'checkbox',
                                'label' => _("Show Dynamic Members for Queues"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("Displays the list of dynamic agents currently assigned to the queues."),
                            );
                            break;

                        case 'fmfm':
                            $data['tab']['settings']["5"] = array(
                                'type'  => 'checkbox',
                                'label' => _("Show Find Me Follow Me for Extensions"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("Displays Find Me Follow Me data for extensions."),
                            );
                            break;

                        case 'ext_optional':
                            $data['tab']['settings']["6"] = array(
                                'type'  => 'checkbox',
                                'label' => _("Show Extension Optional Destinations"),
                                'key'   => $key,
                                'val'   => $val,
                                'id'    => $key,
                                'help'  => _("Displays and follows the optional destinations (No Answer, Busy, Not Reachable) set for the extension in the Advanced tab."),
                            );
                            break;
                    }

                    // We sort the array so that the html is generated in the correct order
                    ksort($data['tab']['settings']);
                }

                $data['clickedNodeTitle'] = $request['clickedNodeTitle'] ?? '';

                $data_return = load_view(__DIR__ . "/views/view.options.php", $data);
                break;

            case 'dialplan':
                $data['iroute']           = sprintf("%s%s", $data['extdisplay'], $data['cid']);
                $data['iroute']           = (empty($data['iroute'])) ? 'ANY' : $data['iroute'];
                $data['clickedNodeTitle'] = $request['clickedNodeTitle'] ?? '';
                $data['basefilename']     = ($data['iroute'] == '') ? 'ANY' : $data['iroute'];
                $data['filename']         = sprintf("%s.png", $data['basefilename']);

                $data_return = load_view(__DIR__ . "/views/view.dialplan.php", $data);

                break;

            default:
                $data_return = sprintf(_("❌Unknown page: %s"), $page);
                break;
        }
        return $data_return;
    }

    public function ajaxRequest($req, &$setting)
    {
        // ** Allow remote consultation with Postman, debugging, etc. **
        // ********************************************
        $setting['authenticate'] = false;
        $setting['allowremote'] = true;
        return true;
        // ********************************************
        switch ($req) {
            case 'get_i18n':
            case 'make':
            case 'reset_setting_default':
            case 'save_settings':
            case 'get_destinations':
            case 'get_settings':
            case 'check_update':
            case 'getrecording':
            case 'getfile':
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
        switch ($command) {
            case 'get_i18n':
                $data_return = [
                    'status' => 'success',
                    'message' => '',
                    'i18n' => [
                        'yes'                         => _("Yes"),
                        'no'                          => _("No"),
                        'loading'                     => _("⏳ Loading..."),
                        'ANY'                         => _("ANY"),
                        'ajax_failed'                 => _("⚠ Could not connect to the server"),
                        'ajax_response_status_err'    => _("⚠ Something went wrong"),
                        'ajax_response_empty'         => _("⚠ Received empty or invalid response"),
                        'reset_settings_confirm'      => _("Are you sure you want to reset all settings to default?"),
                        'submit_settings_confirm'     => _("Are you sure you want to save the settings?"),
                        'settings_get_error'          => _("⚠ An unexpected error occurred: %s"),
                        'export_filename_missing'     => _("Error: Filename is Empty!"),
                        'export_error_image'          => _("❌ Error exporting image:"),
                        'export_blocked_popup'        => _("⚠ The browser blocked the popup."),
                        'btn_highlight'               => _("Highlight Paths"),
                        'btn_highlight_remove'        => _("Remove Highlights"),
                        'destination_empty'           => _("No Destination"),
                        'destination_err_loading'     => _("⚠ Error loading destination pretty name"),
                        'destination_err_unknown'     => _("⚠ Unknown error while loading destinations"),
                        'inbound_routes_loading_dest' => _("⏳ Loading destination pretty name..."),
                        'inbound_routes_empty'        => _("⚠ No Inbound Routes found"),
                        'inbound_routes_select'       => _("Select an Inbound Route"),
                        'inbound_routes_refresh'      => _("✔ Refresh Inbound Routes Successfully"),
                        'inbound_routes_loading'      => _("⏳ Loading Inbound Routes..."),
                    ]
                ];
                break;

            case 'make':
                $extdisplay   = $request['ext'] ?? '';
                $cid          = $request['cid'] ?? '';
                $iroute       = sprintf("%s%s", $extdisplay, $cid);
                $iroute       = (empty($iroute)) ? 'ANY' : $iroute;
                $isExistRoute = $this->dpp->isExistRoute($iroute);

                if (! $isExistRoute) {
                    $data_return = [
                        'status'       => 'error',
                        'message'      => sprintf(_("❌ Error: Could not find inbound route for %s / %s"), $extdisplay, $cid),
                        'iroute'       => $iroute,
                        'ext'          => $extdisplay,
                        'cid'          => $cid,
                        'isExistRoute' => $isExistRoute,
                    ];
                } else {
                    $settings         = $this->getSettingAll();
                    $clickedNodeTitle = $request['clickedNodeTitle'] ?? '';
                    $jump             = $request['jump'] ?? '';
                    $vizReload        = sprintf('%s,%s', $extdisplay, $cid);

                    $basefilename     = ($iroute == '') ? 'ANY' : $iroute;
                    $filename         = sprintf("%s.png", $basefilename);

                    if (is_numeric($extdisplay) && (in_array(strlen($extdisplay), [10, 11, 12]))) {
                        $number = $this->dpp->formatPhoneNumbers($extdisplay);
                    } elseif (empty($extdisplay)) {
                        $number = 'ANY';
                    } else {
                        $number = $extdisplay;
                    }

                    $this->dpp->setDirection($settings['direction']);
                    $gtext = $this->dpp->render($iroute, $clickedNodeTitle);

                    $this->dpp->log(5, sprintf("Dial Plan Graph for %s %s:\n%s", $extdisplay, $cid, $gtext));

                    //$gtext = str_replace(["\n"], ["\\n"], $gtext);
                    $gtext = str_replace(array("\r\n", "\r", "\n"), "\\n", $gtext);

                    $title_cid = !empty($cid) ?  sprintf(' / %s', $this->dpp->formatPhoneNumbers($cid)) : '';
                    $data_return = [
                        'status'       => 'success',
                        'message'      => _("✔ Graph generated successfully"),
                        'filename'     => $filename,
                        'basefilename' => $basefilename,
                        'ext'          => $extdisplay,
                        'cid'          => $cid,
                        'number'       => $number,
                        'isExistRoute' => $isExistRoute,
                        'iroute'       => $iroute,
                        'gtext'        => $gtext,
                        'title'        => sprintf(_("Dial Plan For Inbound Route %s%s: %s"), $number, $title_cid, $this->dpp->dproutes['description']),
                        'datetime'     => $settings['datetime'] == '1' ? date('Y-m-d H:i:s') : '',
                    ];
                }
                break;

            case 'get_settings':
                $settings = $this->getSettingAll();
                $data_return = [
                    'status' => 'success',
                    'message' => '',
                    'settings' => $settings
                ];
                break;

            case 'save_settings':
                $new_setting = $request['data'] ?? [];
                if ($this->setSettingAll($new_setting)) {
                    $data_return = [
                        'status' => 'success',
                        'message' => _("✔ Settings saved successfully")
                    ];
                } else {
                    $data_return = [
                        'status' => 'error',
                        'message' => _("❌ Failed to save settings")
                    ];
                }
                break;

            case 'reset_setting_default':
                // Reset the settings to default values
                $this->resetSetting();
                $data_return = [
                    'status' => "success",
                    'message' => _("✔ Settings reset to default values")
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
                if (isset($result['error'])) {
                    $data_return = ['status' => 'error', 'message' => $result['error']];
                } else {
                    $data_return = [
                        'status'     => 'success',
                        'current'    => $result['current'],
                        'latest'     => $result['latest'],
                        'up_to_date' => $result['up_to_date'],
                    ];
                    if ($result['up_to_date']) {
                        $data_return['message'] = sprintf(_("✔ You are using the latest version: %s"), $result['current']);
                    } else {
                        $data_return['message'] = sprintf(_("⚠ A new version is available: %s, current version: %s"), $result['latest'], $result['current']);
                    }
                }
                break;

            case 'getrecording':
                $id          = intval($request['id'] ?? 0);
                $format      = $request['format'] ?? self::RECORDING_FORMAT_DEFAULT;
                $recording   = $this->hookGetRecording($id);
                $filename    = $recording['filename'] ?? '';
                $fcode_lang  = $recording['fcode_lang'] ?? self::RECORDING_LANG_DEFAULT;
                $lang        = $request['lang'] ?? $fcode_lang;

                $playbacklist = is_array($recording['playbacklist'] ?? null) ? $recording['playbacklist'] : [];

                $audiolist = [];
                $codecs    = [];
                foreach ($playbacklist as $f) {
                    $codec = [
                        'filename'      => $f,
                        'filename_lang' => $recording['soundlist'][$f]['filenames'][$lang] ?? '',
                        'filename_def'  => $recording['soundlist'][$f]['filenames'][self::RECORDING_LANG_DEFAULT] ?? '',
                        'lang'          => $lang,
                        'hasFormat'     => in_array($format, $recording['soundlist'][$f]['codecs'][$lang] ?? []),
                        'hasFormat_def' => in_array($format, $recording['soundlist'][$f]['codecs'][self::RECORDING_LANG_DEFAULT] ?? []),
                    ];
                    $codecs[$f]  = $codec;
                    $audiolist[] = $codec['hasFormat'] ? $codec['filename_lang'] : $codec['filename_def'];
                }
                $audiolist_str = implode('&', $audiolist);

                $data_return = [
                    'displayname' => $recording['displayname'] ?? _('Unknown'),
                    'lang'        => $lang,
                    'format'      => $format,
                    'filename'    => $audiolist_str,
                    'codecs'      => $codecs,
                    'recording'   => $recording,  // full info, used in frontend
                ];
                break;

            case 'getfile':
                $base_filename = $request['file'] ?? '';
                $format_file   = $request['format'] ?? self::RECORDING_FORMAT_DEFAULT;

                $base_filename = str_replace(['../', '..\\'], '', $base_filename); // remove any ../ or ..\


                // error_log($base_filename);
                if (empty($base_filename)) {
                    http_response_code(400);
                    echo _("File name is empty.");
                    exit;
                }

                if (!in_array($format_file, self::RECORDING_FORMAT_ALLOW)) {
                    http_response_code(400);
                    echo sprintf(_("File format '%s' not supported, onley allow '%s'."), $format_file, implode(',', self::RECORDING_FORMAT_ALLOW));
                    exit;
                }

                $path_filename = realpath(sprintf("/var/lib/asterisk/sounds/%s.%s", $base_filename, $format_file));
                if (empty($path_filename)) {
                    http_response_code(400);
                    echo sprintf(_("File '%s' not found."), $base_filename);
                    exit;
                }

                if (! file_exists($path_filename) || !is_readable($path_filename)) {
                    http_response_code(404);
                    echo _("File not found or not readable.");
                    exit;
                }

                switch ($format_file) {
                    case 'wav':
                        $mime_type = 'audio/wav';
                        break;

                    default:
                        $mime_type = 'application/octet-stream';
                        break;
                }

                header(sprintf('Content-Type: %s', $mime_type));
                header(sprintf('Content-Length: %s', filesize($path_filename)));
                header(sprintf('X-Filename: %s.%s', $base_filename, $format_file));
                readfile($path_filename);
                exit;

            default:
                $data_return = ['status' => 'error', 'message' => _("❌ Unknown command")];
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
        $json    = file_get_contents($url, false, $context);
        if ($json === false) {
            return ['error' => _("Failed to fetch release info.")];
        }

        $data = json_decode($json, true);
        if (!isset($data['tag_name'])) {
            return ['error' => _("Invalid response from GitHub.")];
        }

        $latestVersion = ltrim($data['tag_name'], 'v');
        $upToDate = version_compare($ver, $latestVersion, '>=');

        return [
            'current' => $ver,
            'latest' => $latestVersion,
            'up_to_date' => $upToDate,
        ];
    }

    public function asteriskRunCmd($cmd, $return_string = false)
    {
        if ($this->astman) {
            $response = $this->astman->send_request('Command', [ 'Command' => $cmd ]);
            if (!empty($response['data'])) {
                $response = explode("\n", (string) $response['data']);
                unset($response[0]); //remove the Priviledge Command line

                if ($return_string) {
                    $response = implode("\n", $response);
                    $response = htmlspecialchars($response);
                }
                return $response;
            }
        }
        return $return_string ? '' : [];
    }
}
