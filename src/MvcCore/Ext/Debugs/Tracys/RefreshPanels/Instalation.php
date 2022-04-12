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

define('MVCCORE_APP_ROOT', str_replace('\\', '/', dirname(__DIR__, 9)));

use \MvcCore\Ext\Debugs\Tracys\RefreshPanels\Helpers;

/**
 * Remove previous npm code and install fresh content.
 * @return void
 */
call_user_func(function () {
	
	var_dump(defined('MVCCORE_APP_ROOT'));
	var_dump(MVCCORE_APP_ROOT);

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
});