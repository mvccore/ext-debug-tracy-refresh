<?php

namespace MvcCore\Ext\Debugs\Tracys\RefreshPanels;

class ComposerScripts {
	
	public static function PostInstall (\Composer\Script\Event $event) {
		static::process($event->getComposer());
	}
	public static function PostUpdate (\Composer\Script\Event $event) {
		static::process($event->getComposer());
	}
	public static function PostPackageInstall (\Composer\Installer\PackageEvent $event) {
		static::process($event->getComposer());
	}
	public static function PostPackageUpdate (\Composer\Installer\PackageEvent $event) {
		static::process($event->getComposer());
	}

	protected static function process (\Composer\Composer $composer) {
		$projectDir = dirname($composer->getConfig()->get('vendor-dir'));
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