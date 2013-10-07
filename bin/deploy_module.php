#!/usr/bin/env php
<?php
/**
 * Deploy ZF2 module into a ZendSkeletonApplication
 *
 * Usage:
 * --help|-h                    Get usage message
 * --module|-m [ <string> ] 	Module path to deploy; if none provided, assumes current directory
 * --dir|-d [ <string> ]    	Directory path where to deploy the module (ex: apache/www/my-module), the directory could be created if needed
 * --modules|-a [ <string> ]	(optionnal) Additionnal module namespaces (comma separated) to be used in the application
 * --zapp|-z [ <string> ]   		(optionnal) ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Phar, Rar, Zip) depending on PHP installed libraries
 * --composer|-c [ <string> ]   (optionnal) Composer.phar file path, allows locale or remote directory
 * --overwrite|-w 				Whether or not to overwrite existing deployed ZendSkeletonApplication
 * --verbose|-v 				Whether or not to display trace string when an error occured
 */


//Config
$sZendSkeletonApplicationPath = 'https://github.com/zendframework/ZendSkeletonApplication/archive/master.zip';
$sComposerPath = 'https://getcomposer.org/installer';
$bVerbose = true;
try{
	//Auloading
	if($sZendLibraryPath = getenv('LIB_PATH')){
		if(!is_dir($sZendLibraryPath))throw new \InvalidArgumentException('Zend Framework library path "'.$sZendLibraryPath.'" is not an existing directory path');
		if(!is_readable($sZendLibraryPath))throw new \InvalidArgumentException('Zend Framework library directory "'.$sZendLibraryPath.'" is unreadable');
	}
	//Composer install path
	else $sZendLibraryPath = __DIR__.'/../../../zendframework/zendframework/library';
	if(is_dir($sZendLibraryPath)){
		if(!is_readable($sStandardAutoloaderPath = $sZendLibraryPath.'/Zend/Loader/StandardAutoloader.php'))throw new \InvalidArgumentException('StandardAutoloader file "'.$sStandardAutoloaderPath.'" is unreadable');
		// Try to load StandardAutoloader from library
		if(false === include($sStandardAutoloaderPath))throw new \RuntimeException('An error occurred while including file "'.$sStandardAutoloaderPath.'"');
	}
	//Try to load StandardAutoloader from include_path
	else{
		if(!is_readable($sStandardAutoloaderPath = 'Zend/Loader/StandardAutoloader.php'))throw new \InvalidArgumentException('StandardAutoloader file "'.$sStandardAutoloaderPath.'" is unreadable');
		if(false === include($sStandardAutoloaderPath))throw new \RuntimeException('An error occurred while including file "'.$sStandardAutoloaderPath.'"');
	}

	if(!class_exists('Zend\Loader\StandardAutoloader'))throw new \InvalidArgumentException('"'.$sStandardAutoloaderPath.'" does not provide "Zend\Loader\StandardAutoloader" class');

	//Setup autoloading
	$oAutoLoader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
	$oAutoLoader->register();
	$oConsole = \Zend\Console\Console::getInstance();
}
catch(\Exception $oException){
	echo 'An error occured'.PHP_EOL;
	if($bVerbose)echo $oException.PHP_EOL;
	exit(2);
}

try{
	$oGetopt = new \Zend\Console\Getopt(array(
		'help|h'    	=> 'Get usage message',
		'module|m-s' 	=> 'Module path to deploy; if none provided, assumes current directory',
		'dir|d-s' 		=> 'Directory path where to deploy the module (ex: apache/www/my-module), the directory could be created if needed',
		'modules|a-s'	=> '(optionnal) Additionnal module namespaces (comma separated) to be used in the application',
		'zapp|z-s' 		=> '(optionnal) ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Phar, Rar, Zip) depending on PHP installed libraries',
		'composer|c-s' 	=> '(optionnal) Composer.phar file path, allows locale or remote directory',
		'overwrite|w' 	=> 'Whether or not to overwrite existing deployed ZendSkeletonApplication',
		'verbose|v' 	=> 'Whether or not to display process infos',
	));
	$oGetopt->parse();
	$bVerbose = !!$oGetopt->getOption('verbose');
}
catch(\Zend\Console\Exception\RuntimeException $oException){
	$oConsole->writeLine($oException->getUsageMessage());
	exit(2);
}

//Display help
if($oGetopt->getOption('help')){
	$oConsole->writeLine($oGetopt->getUsageMessage());
	exit(0);
}
//Perform deployment process
try{

	//Define module to deploy path
	if(($sModulePath = $oGetopt->getOption('module'))){
		if(!is_string($sModulePath))throw new \InvalidArgumentException('Module directory path expects string, "'.gettype($sModulePath).'" given');
		if(!is_dir($sModulePath))throw new \InvalidArgumentException('Module directory path "'.$sModulePath.'" does not exist');
		$sModulePath = realpath($sModulePath);
	}
	else $sModulePath = getcwd();

	//Assert that mandatory files / dir exist into the deploy directory and are writable
	if(!is_readable($sModuleClassPath = $sModulePath.DIRECTORY_SEPARATOR.'Module.php'))throw new \InvalidArgumentException('Module class File "'.$sModuleClassPath.'" is unreadable');
	$oFileScanner = new \Zend\Code\Scanner\FileScanner($sModuleClassPath);
	//Retrieve current module name
	foreach($oFileScanner->getNamespaces() as $sNameSpace){
		if($oModuleClass = $oFileScanner->getClass($sNameSpace.'\Module')){
			$sCurrentModuleName = $sNameSpace;
			break;
		}
	}
	if(empty($sCurrentModuleName))throw new \InvalidArgumentException('"'.$sModuleClassPath.'" does not provide a "Module" class');

	//Check "dir" option
	if(!($sDeployDirPath = $oGetopt->getOption('dir')))throw new \InvalidArgumentException('Deploy directory path is empty');
	if(!is_string($sDeployDirPath))throw new \InvalidArgumentException('Deploy directory path expects string, "'.gettype($sDeployDirPath).'" given');

	//Create deploy dir if needed
	if(!is_dir($sDeployDirPath)){
		$oPrompt = new \Zend\Console\Prompt\Confirm('Deploy dir "'.$sDeployDirPath.'" does not exist, create it (y/n) ?','y','n');
		$oPrompt->setConsole($oConsole);
		if($oPrompt->show()){
			if(!@mkdir($sDeployDirPath)){
				$aLastError = error_get_last();
				throw new \InvalidArgumentException('Deploy directory "'.$sDeployDirPath.'" can\'t be created - '.$aLastError['message']);
			}
		}
		else{
			$oConsole->writeLine(PHP_EOL.'Deploying module "'.$sCurrentModuleName.'" aborted'.PHP_EOL, \Zend\Console\ColorInterface::LIGHT_RED);
			exit(0);
		}
	}
	$sDeployDirPath = realpath($sDeployDirPath);

	if($bVerbose)$oConsole->writeLine(PHP_EOL.'### Deploy module "'.$sCurrentModuleName.'" into "'.$sDeployDirPath.'" ###',\Zend\Console\ColorInterface::GREEN);

	/**
	 * Assert that ZendSkeletonApplication mandatory files / dir exist
	 * @param string $sDirPath
	 * @return boolean
	 */
	function assertZendSkeletonApplicationIsValid($sDirPath){
		//Assert that mandatory files / dir exist into the deploy directory and are writable
		if(!is_writable($sDirPath.DIRECTORY_SEPARATOR.'init_autoloader.php'))return false;
		if(!is_writable($sDirPath.DIRECTORY_SEPARATOR.'composer.json'))return false;
		if(!is_writable($sDirPath.DIRECTORY_SEPARATOR.'config/application.config.php'))return false;
		return true;
	}

	//Check if ZendSkeletonApplication should be loaded
	if(!($bLoadZendSkeletonApplication = !!$oGetopt->getOption('overwrite')) && !assertZendSkeletonApplicationIsValid($sDeployDirPath))$bLoadZendSkeletonApplication = true;

	//Load ZendSkeletonApplication
	if($bLoadZendSkeletonApplication){

		/**
		 * @param string $sDirPath
		 * @throws \InvalidArgumentException
		 */
		function emptyDir($sDirPath){
			if(!is_dir($sDirPath))throw new \InvalidArgumentException('"'.$sSourcePath.'" is not a directory');
			foreach(new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($sDirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			) as $oFileInfo){
				if($oFileInfo->isDir()){
					if(!@rmdir($oFileInfo->getPathname())){
						$aLastError = error_get_last();
						throw new \RuntimeException('Unable to remove directory "'.$oFileInfo->getPathname().'" - '.$aLastError['message']);
					}
				}
				elseif(!@unlink($oFileInfo->getPathname())){
					$aLastError = error_get_last();
					throw new \RuntimeException('Unable to remove file "'.$oFileInfo->getPathname().'" - '.$aLastError['message']);
				}
			}
		}

		/**
		 * Recursive copy
		 * @param string $sSourcePath
		 * @param string $sDestinationPath
		 * @throws \InvalidArgumentException
		 */
		function rcopy($sSourcePath, $sDestinationPath){
			if(!file_exists($sSourcePath))throw new \InvalidArgumentException('Source file "'.$sSourcePath.'" does not exist');
			//Remove destination
			if(is_dir($sDestinationPath)){
				emptyDir($sDestinationPath);
				if(!rmdir($sDestinationPath)){
					$aLastError = error_get_last();
					throw new \RuntimeException('Unable to remove directory "'.$sDestinationPath.'" - '.$aLastError['message']);
				}
			}
			elseif(is_file($sDestinationPath) && !@unlink($sDestinationPath)){
				$aLastError = error_get_last();
				throw new \RuntimeException('Unable to remove file "'.$sDestinationPath.'" - '.$aLastError['message']);
			}

			//Copy directory
			if(is_dir($sSourcePath)){
				if(!mkdir($sDestinationPath,0777 ,true))throw new \InvalidArgumentException('Destination directory "'.$sDestinationPath.'" can\'t be created');
				foreach(scandir($sSourcePath) as $sFileName){
					if($sFileName != '.' && $sFileName != '..')rcopy($sSourcePath.DIRECTORY_SEPARATOR.$sFileName,$sDestinationPath.DIRECTORY_SEPARATOR.$sFileName);
				}
			}
			//Copy file
			elseif(!copy($sSourcePath,$sDestinationPath))throw new \RuntimeException(sprintf(
				'"%s" can\'t by moved in "%s"',
				$sSourcePath,$sDestinationPath
			));
		}

		//Empty deploy directory path
		emptyDir($sDeployDirPath);

		//Create temp directory
		$sZendSkeletonApplicationPath = $oGetopt->getOption('zapp')?:$sZendSkeletonApplicationPath;

		$sTempDirPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'tmp';
		if(!mkdir($sTempDirPath,0777 ,true))throw new \InvalidArgumentException('Temp directory "'.$sTempDirPath.'" can\'t be created');

		if($bVerbose)$oConsole->writeLine(PHP_EOL.'  * Install ZendSkeletonApplication',\Zend\Console\ColorInterface::LIGHT_MAGENTA);

		//Copy directory into temp directory
		if(is_dir($sZendSkeletonApplicationPath)){

			$aFiles = scandir($sZendSkeletonApplicationPath = realpath($sZendSkeletonApplicationPath));
			if($bVerbose){
				$oConsole->writeLine('    - Copy ZendSkeletonApplication from "'.$sZendSkeletonApplicationPath.'"',\Zend\Console\ColorInterface::GRAY);
				$oProgressBar = new Zend\ProgressBar\ProgressBar(new \Zend\ProgressBar\Adapter\Console(), 0, count($aFiles));
			}

			foreach($aFiles as $iKey => $sFileName){
				if($bVerbose)$oProgressBar->update($iKey+1);
				if($sFileName != '.' && $sFileName != '..')rcopy($sZendSkeletonApplicationPath.DIRECTORY_SEPARATOR.$sFileName,$sTempDirPath.DIRECTORY_SEPARATOR.$sFileName);
			}
			if($bVerbose)$oProgressBar->finish();
		}

		//Remote or locale file
		else{
			if($bVerbose)$oConsole->writeLine('    - Load ZendSkeletonApplication from "'.$sZendSkeletonApplicationPath.'"',\Zend\Console\ColorInterface::WHITE);
			if(!copy($sZendSkeletonApplicationPath,$sTempZendSkeletonApplicationFilePath  = $sTempDirPath.DIRECTORY_SEPARATOR.basename($sZendSkeletonApplicationPath)))throw new \RuntimeException(sprintf(
				'"%s" can\'t by moved in "%s"',
				$sZendSkeletonApplicationPath,$sTempZendSkeletonApplicationFilePath
			));
			//ZendSkeletonApplication is an archive
			if(is_file($sTempZendSkeletonApplicationFilePath)){
				//Get file type
				$oFileInfo = new \finfo(FILEINFO_MIME_TYPE);
				switch($sFileMimeType = $oFileInfo->file($sTempZendSkeletonApplicationFilePath)){
					case 'application/x-gzip':
						if(!class_exists('PharData'))throw new \InvalidArgumentException('Phar manipulation is not available, please install "Phar" php extension');
						$oPhar = new \PharData($sTempZendSkeletonApplicationFilePath);
						if(!$oPhar->extractTo($sTempDirPath))throw new \InvalidArgumentException('Phar "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be extracted');
						unset($oPhar);
						break;
					case 'application/zip':
						if(!class_exists('ZipArchive'))throw new \InvalidArgumentException('Zip manipulation is not available, please install "ZipArchive" php extension');
						$oZip = new \ZipArchive();
						if(!$oZip->open($sTempZendSkeletonApplicationFilePath))throw new \InvalidArgumentException('Zip "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be opened');
						if(!$oZip->extractTo($sTempDirPath))throw new \InvalidArgumentException('Zip "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be extracted');
						if(!$oZip->close())throw new \InvalidArgumentException('Zip "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be closed');
						unset($oZip);
						break;
					default:
						throw new \InvalidArgumentException('File type "'.$sFileMimeType.'" is not supported by Deploy ZF2 module');
				}
				//Remove ZendSkeletonApplication archive
				if(!@unlink($sTempZendSkeletonApplicationFilePath)){
					$aLastError = error_get_last();
					throw new \RuntimeException('Unable to remove file "'.$sTempZendSkeletonApplicationFilePath.'" - '.$aLastError['message']);
				}
			}
		}

		/**
		 * Find ZendSkeletonApplication files
		 * @param string $sDirPath
		 * @return string|boolean
		 */
		function findZendSkeletonApplication($sDirPath){
			if(assertZendSkeletonApplicationIsValid($sDirPath))return $sDirPath;
			foreach(scandir($sDirPath) as $sFilePath){
				if($sFilePath != '.' && $sFilePath != '..' && is_dir($sFilePath = $sDirPath.DIRECTORY_SEPARATOR.$sFilePath) && ($sFilePath = findZendSkeletonApplication($sFilePath)))return realpath($sFilePath);
			}
			return false;
		}

		//Find ZendSkeletonApplication files
		if(!($sTempZendSkeletonApplication = findZendSkeletonApplication($sTempDirPath)))throw new \InvalidArgumentException('ZendSkeletonApplication is not valid');

		//Copy ZendSkeletonApplication files into the deploy directory
		$aFiles = scandir($sTempZendSkeletonApplication);
		if($bVerbose){
			$oConsole->writeLine('    - Copy "ZendSkeletonApplication" into deploy directory "'.$sDeployDirPath.'"',\Zend\Console\ColorInterface::GRAY);
			$oProgressBar = new Zend\ProgressBar\ProgressBar(new \Zend\ProgressBar\Adapter\Console(), 0, count($aFiles));
		}
		foreach($aFiles as $iKey => $sFileName){
			if($bVerbose)$oProgressBar->update($iKey+1);
			if($sFileName != '.' && $sFileName != '..')rcopy($sTempZendSkeletonApplication.DIRECTORY_SEPARATOR.$sFileName,$sDeployDirPath.DIRECTORY_SEPARATOR.$sFileName);
		}
		if($bVerbose)$oProgressBar->finish();

		emptyDir($sTempDirPath);
		if(!rmdir($sTempDirPath))throw new \RuntimeException('Unable to remove directory "'.$sTempDirPath.'"');
	}
	elseif($bVerbose)$oConsole->writeLine(PHP_EOL.'  * "ZendSkeletonApplication" is already loaded',\Zend\Console\ColorInterface::LIGHT_MAGENTA);

	//Adapt ZendSkeletonApplication autoloader
	if($bVerbose)$oConsole->writeLine(PHP_EOL.'  * Adapt "ZendSkeletonApplication" init_autoloader',\Zend\Console\ColorInterface::LIGHT_MAGENTA);

	//Add autoloading module
	if(!($sInitAutoloaderContents = file_get_contents($sInitAutoloaderPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'init_autoloader.php')))throw new \RuntimeException('An error occurred while getting contents from file "'.$sInitAutoloaderPath.'"');

	$sAutoloadingContents = '//[deploy-module-autoloading]'.PHP_EOL.'\Zend\Loader\AutoloaderFactory::factory(';
	//Define autoliading from module's "getAutoloaderConfig" method
 	if($oModuleClass->getMethod('getAutoloaderConfig')){
 		include($sModuleClassPath);
 		$sModuleClassName = $sCurrentModuleName.'\Module';
 		$oModule = new $sModuleClassName();
 		$aAutoloaderConfig = $oModule->getAutoloaderConfig();
 		if(isset($aAutoloaderConfig['Zend\Loader\ModuleAutoloader']))$aAutoloaderConfig['Zend\Loader\ModuleAutoloader'][$sCurrentModuleName] = $sModulePath;
 		else $aAutoloaderConfig['Zend\Loader\ModuleAutoloader'] = array($sCurrentModuleName => $sModulePath);
 		$sAutoloadingContents .= var_export($aAutoloaderConfig,true);
 	}
	//Add "src" directory
 	elseif(is_dir($sModulePath.DIRECTORY_SEPARATOR.'src'))$sAutoloadingContents .= '
 		array(
	 		\'Zend\Loader\ModuleAutoloader\' => array(\''.$sCurrentModuleName.'\' => \''.$sModulePath.'\'),
	 		\'Zend\Loader\StandardAutoloader\' => array(
				\'namespaces\' => array(\''.$sCurrentModuleName.'\' => \''.$sModulePath.'/src/'.$sCurrentModuleName.'\')
			)
		)
 	';

 	$sAutoloadingContents .= ');'.PHP_EOL.'//[/deploy-module-autoloading]';

 	//Remove previous autoloading contents
 	$sInitAutoloaderContents = preg_replace('/\/\/\[deploy\-module\-autoloading\].*\/\/\[\/deploy\-module\-autoloading\]/s', '', $sInitAutoloaderContents);
	if($bVerbose)$oConsole->writeLine('    - Add "'.$sCurrentModuleName.'" namespace autoloading to file "'.$sInitAutoloaderPath.'"',\Zend\Console\ColorInterface::WHITE);
	if(!file_put_contents(
		$sInitAutoloaderPath,
		trim($sInitAutoloaderContents,' '.PHP_EOL).PHP_EOL.$sAutoloadingContents
	))throw new \RuntimeException('An error occurred while putting contents into file "'.$sInitAutoloaderPath.'"');

	//Adapt ZendSkeletonApplication application configuration
	if($bVerbose)$oConsole->writeLine(PHP_EOL.'  * Adapt "ZendSkeletonApplication" application configuration',\Zend\Console\ColorInterface::LIGHT_MAGENTA);
	$sApplicationConfigPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'config/application.config.php';

	if(!is_array(($aApplicationConfig = require $sApplicationConfigPath)))throw new \InvalidArgumentException('"'.$sApplicationConfigPath.'" expects retrieving an array, "'.gettype($aApplicationConfig).'" given');

	if(isset($aApplicationConfig['modules'])){
		if(!is_array($aApplicationConfig['modules']))throw new \InvalidArgumentException('Application config "modules" expects an array, "'.gettype($aApplicationConfig['modules']).'" given');
	}
	else $aApplicationConfig['modules'] = array();

	$aModules = array($sCurrentModuleName);
	if($sModules = $oGetopt->getOption('modules'))$aModules += explode(',',$sModules);

	$aApplicationConfig['modules'] = array_unique(array_filter(array_merge(array_values($aApplicationConfig['modules']),array_values($aModules))));

	//Write new config into file
	if($bVerbose)$oConsole->writeLine('    - Define modules namespaces "'.join(', ',$aApplicationConfig['modules']).'"',\Zend\Console\ColorInterface::WHITE);
	if(!file_put_contents($sApplicationConfigPath,'<?php'.PHP_EOL.'return '.var_export($aApplicationConfig,true).';'))throw new \RuntimeException('An error occurred while writing application config values into file "'.$sApplicationConfigPath.'"');

	//Manage composer install / update
	if(is_readable($sModuleComposerPath = $sModulePath.DIRECTORY_SEPARATOR.'composer.json')){
		//Load composer
		if(!file_exists($sDeployComposerPharPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'composer.phar')){
			$sComposerPath = $oGetopt->getOption('c')?:$sComposerPath;
			if($bVerbose)$oConsole->writeLine('    - Load composer.phar from "'.$sComposerPath.'"',\Zend\Console\ColorInterface::WHITE);
			if(!copy($sComposerPath,$sDeployComposerPharPath))throw new \RuntimeException(sprintf(
					'"%s" can\'t by moved in "%s"',
					$sComposerPath,$sDeployComposerPharPath
			));
		}
		else{
			if($bVerbose)$oConsole->writeLine('    - composer self-update:',\Zend\Console\ColorInterface::WHITE);
			exec($sComposerSelfUpdateCommand = 'php '.$sDeployComposerPharPath.' self-update',$aOutputs, $iReturn);
			if($bVerbose)$oConsole->writeLine('      '.join(PHP_EOL.'      ',$aOutputs).PHP_EOL,\Zend\Console\ColorInterface::WHITE);
			if($iReturn !== 0)throw new \RuntimeException('An error occurred while running "'.$sComposerSelfUpdateCommand.'"');
		}

		//Retrieve application composer.json config
		if(($sModuleComposer = file_get_contents($sModuleComposerPath)) === false)throw new \RuntimeException('An error occurred while getting contents from file "'.$sModuleComposerPath.'"');
		if(is_null($aModuleComposer = json_decode($sModuleComposer,true)))throw new \RuntimeException('An error occurred while decoding json contents from file "'.$sModuleComposerPath.'"');

		//Module composer has requiring
		if(!empty($aModuleComposer['require']) || !empty($aModuleComposer['require_dev'])){

			//Retrieve application composer.json config
			if(($sApplicationComposer = file_get_contents($sApplicationComposerPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'composer.json')) === false)throw new \RuntimeException('An error occurred while getting contents from file "'.$sApplicationComposerPath.'"');
			if(is_null($aApplicationComposer = json_decode($sApplicationComposer,true)))throw new \RuntimeException('An error occurred while decoding json contents from file "'.$sApplicationComposerPath.'"');

			if(!empty($aApplicationComposer['require'])){
				if(!empty($aModuleComposer['require']))$aApplicationComposer['require'] = array_unique(array_merge($aApplicationComposer['require'],$aModuleComposer['require']));
			}

			if(!empty($aApplicationComposer['require_dev'])){
				if(!empty($aModuleComposer['require_dev']))$aApplicationComposer['require_dev'] = array_unique(array_merge($aApplicationComposer['require_dev'],$aModuleComposer['require_dev']));
			}

			//Write new composer.json config if needed
			if(($sNewApplicationComposer = json_encode($aApplicationComposer)) != $sApplicationComposer){
				//Remove composer.lock if exists
				if(
					file_exists($sComposerLockPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'composer.lock')
					&& !@unlink($sComposerLockPath)
				){
					$aLastError = error_get_last();
					throw new \RuntimeException('Unable to remove file "'.$sComposerLockPath.'" - '.$aLastError['message']);
				}
				if(!file_put_contents(
					$sApplicationComposerPath,
					$sNewApplicationComposer
				))throw new \RuntimeException('An error occurred while putting contents into file "'.$sApplicationComposerPath.'"');
			}
		}

		//Change current working directory
		$sCurrentWorkingDir = getcwd();
		chdir($sDeployDirPath);

		//Update
		if(file_exists($sDeployDirPath.DIRECTORY_SEPARATOR.'composer.lock')){
			if($bVerbose)$oConsole->writeLine(PHP_EOL.'  * Composer update',\Zend\Console\ColorInterface::LIGHT_MAGENTA);
			exec($sComposerSelfUpdateCommand = 'php '.$sDeployComposerPharPath.' update',$aOutputs, $iReturn);
			if($bVerbose)$oConsole->writeLine('      '.join(PHP_EOL.'      ',$aOutputs).PHP_EOL,\Zend\Console\ColorInterface::WHITE);
			if($iReturn !== 0)throw new \RuntimeException('An error occurred while running "'.$sComposerSelfUpdateCommand.'"');
			chdir($sCurrentWorkingDir);
		}
		//Install
		else{
			if($bVerbose)$oConsole->writeLine(PHP_EOL.'  * Composer install',\Zend\Console\ColorInterface::LIGHT_MAGENTA);
			exec($sComposerSelfUpdateCommand ='php '. $sDeployComposerPharPath.' install',$aOutputs, $iReturn);
			if($bVerbose)$oConsole->writeLine('      '.join(PHP_EOL.'      ',$aOutputs).PHP_EOL,\Zend\Console\ColorInterface::WHITE);
			if($iReturn !== 0)throw new \RuntimeException('An error occurred while running "'.$sComposerSelfUpdateCommand.'"');
			chdir($sCurrentWorkingDir);
		}
	}

	if($bVerbose)$oConsole->writeLine(PHP_EOL.'### Module "'.$sCurrentModuleName.'" has been deployed into into "'.$sDeployDirPath.'" with success ###'.PHP_EOL,\Zend\Console\ColorInterface::GREEN);
	exit(0);
}
catch(\Exception $oException){
	$oConsole->writeLine(PHP_EOL.'======================================================================', \Zend\Console\ColorInterface::GRAY);
	$oConsole->writeLine('An error occured : '.$oException->getMessage(), \Zend\Console\ColorInterface::RED);
	$oConsole->writeLine('======================================================================', \Zend\Console\ColorInterface::GRAY);
	if($bVerbose)$oConsole->writeLine($oException.PHP_EOL);
	exit(2);
}