<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Debugs\Tracys;

use \MvcCore\Ext\Debugs\Tracys\RefreshPanels\Helpers;

/**
 * Responsibility - render panel to create websocket connection
 *                  with Node.JS on server side to monitor filesystem 
 *                  changes to automatically refresh current browser tab.
 */
class RefreshPanel implements \Tracy\IBarPanel {

	/**
	 * MvcCore Extension - Debug - Tracy - Session - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.0';
	

	/**
	 * Unique panel id.
	 * @var string|NULL
	 */
	protected $panelUniqueId = NULL;

	/**
	 * Boolean `TRUE` if request is standard html GET output, 
	 * not AJAX, not a redirect, not POST or HEAD, etc...
	 * @var bool
	 */
	protected $active = FALSE;

	/**
	 * Node.JS websocket server port.
	 * @var int|NULL
	 */
	protected $port = NULL;

	/**
	 * Application root dir.
	 * @var string
	 */
	protected $appRoot;

	/**
	 * Default locations to monitor filesystem changes.
	 * @var \string[]
	 */
	protected $defaultLocations = [];

	/**
	 * Default exclude patterns.
	 * @var \string[]
	 */
	protected $defaultEcludePatterns = [
		'/.*\\/\\.(git|hg|svn|vs)/g',
		'~/Var',
		'~/vendor',
	];

	/**
	 * Default file extensions.
	 * @var \string[][]
	 */
	protected $defaultExtensions = [
		['php','phtml',],
		['ini','yaml','json',],
		['js','css',],
		['jpg','png','gif','svg',]
	];

	/**
	 * Absolute path to Client.js in node_modules directory.
	 * @var string
	 */
	protected $clientJsFullPath;

	/**
	 * JS XMLHttpRequest GET param name to start background 
	 * process with Node.JS WebSocket server to monitor file changes.
	 * @var string
	 */
	protected $startMonitoringParamName;
	
	/**
	 * Assets CSP nonce attribute for web debugging.
	 * @var string
	 */
	protected $nonceAttr = '';

	/**
	 * Debug code for this panel, printed at panel bottom.
	 * @var string
	 */
	protected $debugCode = '';

	public function __construct () {
		$app = \MvcCore\Application::GetInstance();
		if (
			isset($_GET[Helpers::GetXhrStartMonitoringParamName()]) &&
			$app->GetRequest()->GetMethod() === \MvcCore\IRequest::METHOD_POST
		) {
			$this->initWsServer($app);
		} else {
			$app->AddPreSentHeadersHandler(
				function (\MvcCore\IRequest $req, \MvcCore\IResponse $res) {
					$this->initCtor($req, $res);
				}, PHP_INT_MAX
			);
		}
	}

	/**
	 * Start Node.JS WebSocket server to monitor file 
	 * changes and send JSON response about it.
	 * @param  \MvcCore\IApplication $app
	 * @return void
	 */
	protected function initWsServer (\MvcCore\IApplication $app) {
		\Tracy\Debugger::enable(TRUE);
		if (!\MvcCore\Debug::GetDebugging()) {
			$result = [
				'success'	=> FALSE,
				'message'	=> 'Debugging is not enabled.',
			];
		} else {
			try {
				$this->initCtorPort();
				$jsDir = Helpers::GetJsDirFullPath();
				list($nodeDirFullPath, $nodeExecFullPath) = Helpers::GetNodePaths();
				list($sysOutput, $code) = Helpers::System($nodeExecFullPath . ' -v', $jsDir);
				if ($code !== 0 || !preg_match("#^v\d+\.\d+\.\d+$#", $sysOutput)) 
					throw new \Exception($sysOutput);
				$nodeVersion = preg_replace("#[^\d\.]#", '', $sysOutput);
				if (version_compare($nodeVersion, '10.0.0', '<'))
					throw new \Exception("Node version is too old: {$sysOutput}, min. required version is 10.0.0.");
				list($sysOutput, $code) = Helpers::System(
					$nodeExecFullPath . ' -e "'
						.'var subprocess=require(\'child_process\')'
							.'.spawn('
								.'\''.$nodeExecFullPath.'\','
								.'[\''.$jsDir.'/Server.js\','.$this->port.'],'
								.'{detached:true,stdio:\'ignore\',cwd:\''.$nodeDirFullPath.'\'}'
							.');'
						.'subprocess.unref();'
						.'console.log(1);"',
					$jsDir
				);
				if ($code !== 0) 
					throw new \Exception($sysOutput);
				$result = [
					'success'	=> TRUE,
					'message'	=> $sysOutput === '1'
						? 'WebSocket server has been started.'
						: $sysOutput,
				];
			} catch (\Throwable $e) {
				$result = [
					'success'	=> FALSE,
					'message'	=> $e->getMessage(),
				];
			}
		}
		header('Content-Type: application/json');
		echo \MvcCore\Tool::JsonEncode($result);
		if (ob_get_level())
			ob_end_flush();
		flush();
		die();
	}
	
	/**
	 * Initialize necesary panel properties before http headers are sent.
	 * @param  \MvcCore\IRequest  $req 
	 * @param  \MvcCore\IResponse $res 
	 * @return void
	 */
	protected function initCtor (\MvcCore\IRequest $req, \MvcCore\IResponse $res) {
		$this->active = (
			$req->GetMethod() === \MvcCore\IRequest::METHOD_GET && 
			$res->GetHeader('Location') === NULL &&
			!$req->IsAjax()
		);
		if (!$this->active) return;
		$this->initCtorPort();
		$this->initCtorCsp($req, $res);
	}
	
	/**
	 * Initialize Node.JS websocket server port from config or with default value. 
	 * @return void
	 */
	protected function initCtorPort () {
		$sysCfg = \MvcCore\Debug::GetSystemCfgDebugSection();
		$cfgPortSegments = explode('.', Helpers::GetSysConfigProp('port'));
		$cfgPortSegmentsCount = count($cfgPortSegments);
		foreach ($cfgPortSegments as $index => $cfgPortSegment) {
			if (!isset($sysCfg->{$cfgPortSegment})) 
				break;
			if ($index + 1 === $cfgPortSegmentsCount) {
				$this->port = $sysCfg->{$cfgPortSegment};
			} else {
				$sysCfg = $sysCfg->{$cfgPortSegment};
			}
		}
		if ($this->port === NULL)
			$this->port = Helpers::GetSysConfigProp('portDefault');
	}

	/**
	 * Initialize content security policy (if necessary)
	 * for Node.JS web socket server address.
	 * @param  \MvcCore\IRequest  $req 
	 * @param  \MvcCore\IResponse $res 
	 * @return void
	 */
	protected function initCtorCsp (\MvcCore\IRequest $req, \MvcCore\IResponse $res) {
		$cpsClass = Helpers::GetCspFullClassName();
		$wsUrl = Helpers::GetWsUrl($this->port);
		if (!class_exists($cpsClass)) {
			/** @var \MvcCore\Ext\Tools\Csp $csp */
			$csp = $cpsClass::GetInstance();
			$csp->AllowHosts($csp::FETCH_CONNECT_SRC, [$wsUrl]);
			$res->SetHeader($csp->GetHeaderName(), $csp->GetHeaderValue());
		} else {
			$cspHeaderName = 'Content-Security-Policy';
			$rawHeaderValue = " ".trim($res->GetHeader($cspHeaderName))." ";
			$sections = ['connect', 'default'];
			foreach ($sections as $section) {
				if (preg_match_all("#^(.*)([\s;]+)({$section}\-src)(\s+)(.*)$#i", $rawHeaderValue, $sectionMatches)) {
					array_shift($sectionMatches);
					$sectionMatches = array_map(function ($item) { return $item[0]; }, $sectionMatches);
					$sectionMatches[3] = " $wsUrl ";
					$res->SetHeader($cspHeaderName, trim(implode("", $sectionMatches)));
					break;
				}
			}
		}
	}

	/**
	 * Get unique `Tracy` debug bar panel id.
	 * @return string
	 */
	public function getId () {
		return 'refresh-panel';
	}

	/**
	 * Return rendered debug panel heading HTML code displayed all time in `Tracy` debug  bar.
	 * @return string
	 */
	public function getTab () {
		if (!$this->active) return '';
		$this->initPanel();
		ob_start();
		include(__DIR__ . '/refresh.tab.phtml');
		return ob_get_clean();
	}

	/**
	 * Return rendered debug panel content window HTML code.
	 * @return string
	 */
	public function getPanel () {
		if (!$this->active) return '';
		$this->initPanel();
		ob_start();
		include(__DIR__ . '/refresh.panel.phtml');
		return ob_get_clean();
	}

	/**
	 * Prepare view data for rendering.
	 * @return void
	 */
	protected function initPanel () {
		if ($this->panelUniqueId !== NULL) return;
		$this->panelUniqueId = number_format(microtime(TRUE), 6, '', '');
		$req = \MvcCore\Application::GetInstance()->GetRequest();
		$this->appRoot = $req->GetAppRoot();
		$this->defaultLocations[] = $this->appRoot;
		$this->clientJsFullPath = Helpers::GetJsDirFullPath() . '/Client.js';
		$this->startMonitoringParamName = Helpers::GetXhrStartMonitoringParamName();
		$nonce = \Tracy\Helpers::getNonce();
		$this->nonceAttr = $nonce ? ' nonce="' . \Tracy\Helpers::escapeHtml($nonce) . '"' : '';
	}

	/**
	 * Print any variable in panel body under database queries.
	 * @param  mixed $var
	 * @return void
	 */
	protected function debug ($var) {
		$this->debugCode .= \Tracy\Dumper::toHtml($var, [
			\Tracy\Dumper::LIVE		=> FALSE,
			//\Tracy\Dumper::DEPTH	=> 5,
		]);
	}
}