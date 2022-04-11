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
 * Responsibility - install fresh Node.JS and client code via npm.
 */
class ComposerScripts {
	
	/**
	 * Remove previous npm code and install fresh content.
	 * @return void
	 */
	public static function Run () {
		$projectDir = realpath(__DIR__ . '/../../../../../..');
		$nodeModulesDirFp = $projectDir . DIRECTORY_SEPARATOR . 'node_modules';
		$packageLockFp = $projectDir . DIRECTORY_SEPARATOR . 'package-lock.json';
		$isWin = static::isWin();
		if (is_dir($nodeModulesDirFp)) {
			$cmd = $isWin
				? "rmdir /S /Q \"{$nodeModulesDirFp}\""
				: "rm -rf \"{$nodeModulesDirFp}\"";
			list($sysOut, $code) = static::system($cmd);
			var_dump([$sysOut, $code]);
			// windows: ['', 0]
		}
		if (file_exists($packageLockFp)) {
			$cmd = $isWin
				? "del /F /Q \"{$packageLockFp}\""
				: "rm -f \"{$packageLockFp}\"";
			list($sysOut, $code) = static::system($cmd);
			var_dump([$sysOut, $code]);
			// windows: ['', 0]
		}

		list($sysOut, $code) = static::system("npm -v");
		var_dump([$sysOut, $code]);

		$cmd = $isWin
			? "call npm install"
			: "npm install";
		list($sysOut, $code) = static::system($cmd);
		var_dump([$sysOut, $code]);
		// windows: ['added 18 packages, and audited 19 packages in 3s ... found 0 vulnerabilities', 0]
	}
	
	/**
	 * Return `TRUE` for Windows operating systems.
	 * @return bool
	 */
	protected static function isWin () {
		return mb_substr(mb_strtolower(PHP_OS), 0, 3) === 'win';
	}

	/**
	 * Get system command output code and stdout.
	 * @param  string      $cmd 
	 * @param  string|NULL $dirPath 
	 * @return [string, int]
	 */
	protected static function system ($cmd, $dirPath = NULL) {
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

}