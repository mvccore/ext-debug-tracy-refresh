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

use \MvcCore\Ext\Debugs\Tracys\RefreshPanels\Helpers;

/**
 * Responsibility - install fresh Node.JS and client code via npm.
 */
class Instalation {
	
	/**
	 * Remove previous npm code and install fresh content.
	 * @return void
	 */
	public static function Run () {

		var_dump($_SERVER); // ['SCRIPT_FILENAME']
		var_dump(defined('MVCCORE_APP_ROOT'));
		if (!defined('MVCCORE_APP_ROOT')) {
			define('MVCCORE_APP_ROOT', str_replace('\\', '/', dirname(__DIR__, 9)));
		}

		$nodePaths = Helpers::GetNodePaths();
		var_dump($nodePaths);

		$projectDir = realpath(dirname(__DIR__, 6));
		$nodeModulesDirFp = $projectDir . DIRECTORY_SEPARATOR . 'node_modules';
		$packageLockFp = $projectDir . DIRECTORY_SEPARATOR . 'package-lock.json';
		$isWin = Helpers::IsWin();
		if (is_dir($nodeModulesDirFp)) {
			$cmd = $isWin
				? "rmdir /S /Q \"{$nodeModulesDirFp}\""
				: "rm -rf \"{$nodeModulesDirFp}\"";
			list($sysOut, $code) = Helpers::System($cmd);
			//var_dump([$sysOut, $code]);
			// windows: ['', 0]
		}
		if (file_exists($packageLockFp)) {
			$cmd = $isWin
				? "del /F /Q \"{$packageLockFp}\""
				: "rm -f \"{$packageLockFp}\"";
			list($sysOut, $code) = Helpers::System($cmd);
			//var_dump([$sysOut, $code]);
			// windows: ['', 0]
		}
		
		list($sysOut, $code) = Helpers::System("npm -v");
		var_dump([$sysOut, $code]); 
		if ($code !== 0) {
			$cmd = $isWin
				? "where npm"
				: "which npm";
			list($sysOut, $code) = Helpers::System($cmd);
			var_dump([$sysOut, $code]);
		}
		
		$cmd = $isWin
			? "call npm install"
			: "npm install";
		list($sysOut, $code) = Helpers::System($cmd);
		var_dump([$sysOut, $code]);
		// windows: ['added 18 packages, and audited 19 packages in 3s ... found 0 vulnerabilities', 0]
	}
	
}