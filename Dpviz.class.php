<?php
// License for all code of this FreePBX module can be found in the license file inside the module directory
// Copyright 2015 Sangoma Technologies.
// vim: set ai ts=4 sw=4 ft=php:

namespace FreePBX\modules;

class Dpviz extends \FreePBX_Helpers implements \BMO {
    
    private $freepbx;
    
    public function __construct($freepbx = null) {
        parent::__construct($freepbx);
        $this->freepbx = $freepbx;
        $this->db = $this->freepbx->Database;
    }

    public function install() {
        // Required by BMO, but can remain empty
    }

    public function uninstall() {
        // Required by BMO, but can remain empty
    }

    public function getOptions() {
        $sql = "SELECT * FROM dpviz";
        $sth = $this->db->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function editDpviz($panzoom, $horizontal, $datetime, $destination, $scale, $dynmembers) {
        $sql = "UPDATE dpviz SET
            `panzoom` = :panzoom,
            `horizontal` = :horizontal,
						`datetime` = :datetime,
						`destination` = :destination,
						`scale` = :scale,
						`dynmembers` = :dynmembers
            WHERE `id` = 1";
        $insert = [
            ':panzoom' => $panzoom,
            ':horizontal' => $horizontal,
						':datetime' => $datetime,
						':destination' => $destination,
						':scale' => $scale,
						':dynmembers' => $dynmembers
        ];
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($insert);
    }
    
    public function doConfigPageInit($page) {
        $request = $_REQUEST;
        $action = isset($request['action']) ? $request['action'] : '';
        $panzoom = isset($request['panzoom']) ? $request['panzoom'] : '';
        $horizontal = isset($request['horizontal']) ? $request['horizontal'] : '';
				$datetime = isset($request['datetime']) ? $request['datetime'] : '';
				$destination = isset($request['destination']) ? $request['destination'] : '';
				$scale = isset($request['scale']) ? $request['scale'] : '';
				$dynmembers = isset($request['dynmembers']) ? $request['dynmembers'] : '';
				
        switch ($action) {
            case 'edit':
                $this->editDpviz($panzoom, $horizontal, $datetime, $destination, $scale, $dynmembers);
                break;
            default:
                break;
        }
    }
		public function getRightNav($request) {
			return load_view(__DIR__."/views/rnav.php",[]);
		}
		
		public function ajaxRequest($req, &$setting){
			switch ($req) {
				case 'check_update':
				return true;
				break;
			}
			return false;
		}
	
		public function ajaxHandler() {
				$action = isset($_REQUEST['command']) ? $_REQUEST['command'] : '';
				switch ($action) {
						case 'check_update':
								// Call the function to check for updates
								$result = $this->checkForGitHubUpdate();

								if (isset($result['error'])) {
										return ['status' => 'error', 'message' => $result['error']];
								}
								return [
										'status' => 'success',
										'current' => $result['current'],
										'latest' => $result['latest'],
										'up_to_date' => $result['up_to_date'],
								];
						default:
								return ['status' => 'error', 'message' => 'Unknown command'];
				}
		}
		
		public function checkForGitHubUpdate() {
	
			$ver = \FreePBX::Modules()->getInfo('dpviz')['dpviz']['version']; // current version
			$url = "https://api.github.com/repos/madgen78/dpviz/releases/latest"; // GitHub version

			$opts = [
					"http" => [
							"method" => "GET",
							"header" => "User-Agent: dpviz\r\n"
					]
			];
			$context = stream_context_create($opts);
			$json = file_get_contents($url, false, $context);

			if ($json === false) {
					return ['error' => 'Failed to fetch release info.'];
			}

			$data = json_decode($json, true);
			if (!isset($data['tag_name'])) {
					return ['error' => 'Invalid response from GitHub.'];
			}

			$latestVersion = ltrim($data['tag_name'], 'v');
			$upToDate = version_compare($ver, $latestVersion, '>=');
			$now = new \DateTime();

			return [
					'current' => $ver,
					'latest' => $latestVersion,
					'up_to_date' => $upToDate,
					'checked' => $now->format('Y-m-d H:i:s')
			];
			
		}
}
