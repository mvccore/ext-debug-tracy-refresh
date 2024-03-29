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

use \MvcCore\Ext\Debugs\Tracys\RefreshPanels\Helpers;

/**
 * Remove previous npm code and install fresh content.
 * @return void
 */
call_user_func(function () {
	// Detect environment first:
	\MvcCore\Application::GetInstance()->GetEnvironment()->GetName();
	// If there are any previous node modules installed - remove it:
	$projectDir = realpath(dirname(__DIR__, 6));
	$nodeModulesDirFp = $projectDir . DIRECTORY_SEPARATOR . 'node_modules';
	$packageLockFp = $projectDir . DIRECTORY_SEPARATOR . 'package-lock.json';
	$isWin = Helpers::IsWin();
	if (is_dir($nodeModulesDirFp)) {
		$cmd = $isWin
			? "rmdir /S /Q \"{$nodeModulesDirFp}\""
			: "rm -rf \"{$nodeModulesDirFp}\"";
		list($sysOut, $code) = Helpers::System($cmd, $projectDir);
		if ($code !== 0)
			throw new \Exception($sysOut);
	}
	if (file_exists($packageLockFp)) {
		$cmd = $isWin
			? "del /F /Q \"{$packageLockFp}\""
			: "rm -f \"{$packageLockFp}\"";
		list($sysOut, $code) = Helpers::System($cmd, $projectDir);
		if ($code !== 0)
			throw new \Exception($sysOut);
	}
	// Install node modules via npm:
	list($nodeDirFullPath) = Helpers::GetNodePaths();
	// Make sure PATH env variable contains node path:
	$cmd = $isWin
		? 'echo %PATH%'
		: 'echo "$PATH"';
	list($sysOut, $code) = Helpers::System($cmd, $projectDir);
	if ($code !== 0)
		throw new \Exception($sysOut);
	if ($isWin) $sysOut = str_replace('\\', '/', $sysOut);
	$pathDelim = $isWin ? ';' : ':';
	$sysOut = $pathDelim . trim($sysOut, $pathDelim) . $pathDelim;
	$pathContainsNode = mb_strpos($sysOut, $pathDelim . $nodeDirFullPath . $pathDelim);
	$cmd = $nodeDirFullPath . '/npm install "' . $projectDir . '" --loglevel verbose';
	if (!$isWin && !$pathContainsNode)
		$cmd = 'export PATH=' . $nodeDirFullPath . ':$PATH;' . $cmd;
	list($sysOut, $code) = Helpers::System($cmd, $projectDir);
	if ($code !== 0)
		throw new \Exception($sysOut);
});