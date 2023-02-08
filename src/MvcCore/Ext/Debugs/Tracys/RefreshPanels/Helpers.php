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

namespace MvcCore\Ext\Debugs\Tracys\RefreshPanels;

/**
 * Responsibility - helper functions for classes RefreshPanel and Instalation.
 */
class Helpers {
	
	/**
	 * Relative path to Client.js and Server.js in node_modules directory.
	 * @var string
	 */
	const JS_NODE_MODULE_PATH = '/node_modules/@mvccore/ext-debug-tracy-refresh-js/build';

	/**
	 * System config path to record in `[debug]` section 
	 * for Node.JS websocket server port, default 
	 * Node.JS websocket server port and Node.JS executable
	 * full path (including node.exe).
	 * @var array
	 */
	protected static $sysConfigProps = [
		'address'		=> 'refresh.address',
		'port'			=> 'refresh.port',
		'nodePath'		=> 'refresh.nodePath',
		'addressDefault'=> 'SERVER_NAME',
		'portDefault'	=> 9006,
	];

	/**
	 * MvcCore CSP policy tool full class name.
	 * @var string
	 */
	protected static $cspFullClassName = '\\MvcCore\\Ext\\Tools\\Csp';

	/**
	 * JS XMLHttpRequest GET param name to start background 
	 * process with Node.JS WebSocket server to monitor file changes.
	 * @var string
	 */
	protected static $xhrStartMonitoringParamName = '_tracy_panel_refresh_start';

	
	/**
	 * Set MvcCore CSP policy tool full class name.
	 * @param  string $cspFullClassName
	 * @return string
	 */
	public static function SetCspFullClassName ($cspFullClassName) {
		return static::$cspFullClassName = $cspFullClassName;
	}
	
	/**
	 * Get MvcCore CSP policy tool full class name.
	 * @return string
	 */
	public static function GetCspFullClassName () {
		return static::$cspFullClassName;
	}
	
	/**
	 * Set JS XMLHttpRequest GET param name to start background 
	 * process with Node.JS WebSocket server to monitor file changes.
	 * @param  string $xhrStartMonitoringParamName
	 * @return string
	 */
	public static function SetXhrStartMonitoringParamName ($xhrStartMonitoringParamName) {
		return static::$xhrStartMonitoringParamName = $xhrStartMonitoringParamName;
	}
	
	/**
	 * Get JS XMLHttpRequest GET param name to start background 
	 * process with Node.JS WebSocket server to monitor file changes.
	 * @return string
	 */
	public static function GetXhrStartMonitoringParamName () {
		return static::$xhrStartMonitoringParamName;
	}
	
	/**
	 * Set system config paths to records in `[debug]` section. 
	 * For Node.JS directory and websocket server port.
	 * @param  array $sysConfigProps 
	 * @return array
	 */
	public static function SetSysConfigProps (array $sysConfigProps) {
		return static::$sysConfigProps = $sysConfigProps;
	}
	
	/**
	 * Get system config paths to records in `[debug]` section. 
	 * For Node.JS directory and websocket server port.
	 * @return array
	 */
	public static function GetSysConfigProps () {
		return static::$sysConfigProps;
	}
	
	/**
	 * Get system config path record.
	 * @param  string $propName
	 * @return string|int
	 */
	public static function GetSysConfigProp ($propName) {
		return static::$sysConfigProps[$propName];
	}
	
	/**
	 * Get Node.JS websocket server url.
	 * @param  bool            $isSecure
	 * @param  string          $address
	 * @param  string|int|NULL $port
	 * @return string
	 */
	public static function GetWsUrl ($isSecure, $address, $port) {
		$scheme = $isSecure ? 'wss' : 'ws';
		$portStr = '';
		if ($port !== NULL && $port !== '') 
			$portStr = ($port === 80 || $port === 443) ? '' : ':' . $port;
		return "{$scheme}://{$address}{$portStr}/";
	}

	/**
	 * Get system command output code and stdout.
	 * @param  string      $cmd 
	 * @param  string|NULL $dirPath 
	 * @return [string, int]
	 */
	public static function System ($cmd, $dirPath = NULL) {
		if (!function_exists('system')) 
			throw new \Exception('Function `system` is not allowed.');
		$dirPathPresented = $dirPath !== NULL && mb_strlen($dirPath) > 0;
		if ($dirPathPresented) {
			$cwd = getcwd();
			chdir($dirPath);
		}
		ob_start();
		system($cmd . ' 2>&1', $code);
		$sysOut = ob_get_clean();
		if ($dirPathPresented) chdir($cwd);
		return [trim($sysOut), $code];
	}
	
	/**
	 * Loads Node.JS executable full path from system 
	 * config or by `which` (`where`) system command.
	 * @throws \Exception  There was not possible to determinate Node.JS executable full path.
	 * @return \string[]
	 */
	public static function GetNodePaths () {
		$nodePath = NULL;
		$sysCfg = \MvcCore\Debug::GetSystemCfgDebugSection();
		if ($sysCfg !== NULL) {
			$cfgNodePathSegments = explode('.', static::GetSysConfigProp('nodePath'));
			$cfgNodePathSegmentsCount = count($cfgNodePathSegments);
			foreach ($cfgNodePathSegments as $index => $cfgNodePathSegment) {
				if (!isset($sysCfg->{$cfgNodePathSegment})) 
					break;
				if ($index + 1 === $cfgNodePathSegmentsCount) {
					$nodePath = $sysCfg->{$cfgNodePathSegment};
				} else {
					$sysCfg = $sysCfg->{$cfgNodePathSegment};
				}
			}
		}
		$isWin = static::IsWin();
		$nodeCli = $isWin ? 'node.exe' : 'node';
		if ($nodePath === NULL) {
			$whichCmd = $isWin ? 'where' : 'which';
			list($whichNodePath, $code) = static::System($whichCmd.' '.$nodeCli);
			if ($code === 0 && mb_strlen($whichNodePath) > 0) 
				$nodePath = dirname($whichNodePath);
		}
		if ($nodePath !== NULL) {
			$nodePath = str_replace('\\', '/', $nodePath);
			$nodeDirFullPath = rtrim($nodePath, '/');
			$nodeExecFullPath = $nodeDirFullPath . '/' . $nodeCli;
			return [$nodeDirFullPath, $nodeExecFullPath];
		}
		throw new \Exception(
			"There was not possible to determinate Node.JS executable full path. \n".
			"Try to add directory with Node.JS executable into \$PATH environment variable or \n".
			"try to add the directory into your system config.ini into section [debug] with this line: \n".
			"`refresh.nodePath = \"/your/custom/path/to/node/directory\"`"
		);
	}
	
	/**
	 * Return absolute path to Client.js and Server.js in node_modules directory.
	 * @return string
	 */
	public static function GetJsDirFullPath () {
		return str_replace('\\', '/', realpath(
			dirname(__DIR__, 6) . static::JS_NODE_MODULE_PATH
		));
	}
	
	/**
	 * Return `TRUE` for Windows operating systems.
	 * @return bool
	 */
	public static function IsWin () {
		return mb_substr(mb_strtolower(PHP_OS), 0, 3) === 'win';
	}
	
}