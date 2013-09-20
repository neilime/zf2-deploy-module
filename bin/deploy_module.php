#!/usr/bin/env php
<?php
/**
 * Deploy ZF2 module into a ZendSkeletonApplication
 *
 * Usage:
 * --help|-h                    Get usage message
 * --module|-m [ <string> ] 	Module to deploy; if none provided, assumes current directory
 * --dir|-d [ <string> ]    	Directory path where to deploy the module (ex: apache/www/my-module)
 * --app|z [ <string> ]   		(optionnal) ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Phar, Rar, Zip) depending on PHP installed libraries',
 * --modules|a [ <string> ]		(optionnal) Additionnal module namespaces (comma separated) to be used in the application',
 * --overwrite|-w 				Whether or not to overwrite existing ZendSkeletonApplication
 */


//Config
$sZendSkeletonApplicationPath = 'https://github.com/zendframework/ZendSkeletonApplication/archive/master.zip';

//Auloading
if(is_dir($sZendLibraryPath = getenv('LIB_PATH')?:__DIR__.'/../library')){
	// Try to load StandardAutoloader from library
	if(false === include($sStandardAutoloaderPath = $sZendLibraryPath.'/Zend/Loader/StandardAutoloader.php'))throw new \InvalidArgumentException('StandardAutoloader file "'.$sStandardAutoloaderPath.'" does not exist');
}
//Try to load StandardAutoloader from include_path
elseif(false === include('Zend/Loader/StandardAutoloader.php')) {
	echo 'Unable to locate autoloader via include_path; aborting'.PHP_EOL;
	exit(2);
}

//Setup autoloading
$oAutoLoader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
$oAutoLoader->register();

try{
	$oGetopt = new \Zend\Console\Getopt(array(
		'help|h'    	=> 'Get usage message',
		'module|m-s' 	=> 'Module to deploy; if none provided, assumes current directory',
		'dir|d-s' 		=> 'Directory path where to deploy the module (ex: apache/www/my-module)',
		'app|z-s' 		=> '(optionnal) ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Phar, Rar, Zip) depending on PHP installed libraries',
		'modules|a-s'	=> '(optionnal) Additionnal module namespaces (comma separated) to be used in the application',
		'overwrite|-w' 	=> 'Whether or not to overwrite existing ZendSkeletonApplication'
	));
	$oGetopt->parse();
}
catch(\Zend\Console\Exception\RuntimeException $oException){
	echo $oException->getUsageMessage();
	exit(2);
}

//Display help
if($oGetopt->getOption('help')) {
	echo $oGetopt->getUsageMessage();
	exit(0);
}

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
if($oModuleClass = $oFileScanner->getClass('Module')){
	$sModuleClassName = $oModuleClass->getName();
	$sCurrentModuleName = substr($sModuleClassName,0,strrpos($sModuleClassName,'\\'));
}
else throw new \InvalidArgumentException('"'.$sModuleClassPath.'" does not provide a "Module" class');

//Check "dir" option
if(!($sDeployDirPath = $oGetopt->getOption('dir')))throw new \InvalidArgumentException('Deploy directory path is empty');
if(!is_string($sDeployDirPath))throw new \InvalidArgumentException('Deploy directory path expects string, "'.gettype($sDeployDirPath).'" given');

//Create deploy dir if needed
if(!is_dir($sDeployDirPath) && !mkdir($sDeployDirPath,0777 ,true))throw new \InvalidArgumentException('Deploy directory "'.$sDeployDirPath.'" does not exist and can\'t be created');
$sDeployDirPath = realpath($sDeployDirPath);

/**
 * Assert that ZendSkeletonApplication mandatory files / dir exist
 * @param string $sDirPath
 * @return boolean
 */
function assertZendSkeletonApplicationIsValid($sDirPath){
	//Assert that mandatory files / dir exist into the deploy directory and are writable
	if(!is_writable($sDirPath.DIRECTORY_SEPARATOR.'init_autoloader.php'))return false;
	if(!is_writable($sDirPath.DIRECTORY_SEPARATOR.'config/application.config.php'))return false;
	if(!is_dir($sDirPath.DIRECTORY_SEPARATOR.'public'))return false;
	if(!is_dir($sDirPath.DIRECTORY_SEPARATOR.'module/Application'))return false;
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
		) as $oFileinfo){
			if($oFileinfo->isDir())rmdir($oFileinfo->getRealPath());
			else unlink($oFileinfo->getRealPath());
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
			rmdir($sDestinationPath);
		}
		elseif(is_file($sDestinationPath))unlink($sDestinationPath);

		//Copy directory
		if(is_dir($sSourcePath)){
			if(!mkdir($sDestinationPath,0777 ,true))throw new \InvalidArgumentException('Destination directory "'.$sDestinationPath.'" can\'t be created');
			foreach(scandir($sSourcePath) as $sFileName){
				if($sFileName != '.' && $sFileName != '..') rcopy($sSourcePath.DIRECTORY_SEPARATOR.$sFileName,$sDestinationPath.DIRECTORY_SEPARATOR.$sFileName);
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
	$sZendSkeletonApplicationPath = $oGetopt->getOption('app')?:$sZendSkeletonApplicationPath;
	$sTempDirPath = $sDeployDirPath.DIRECTORY_SEPARATOR.'tmp';
	if(!mkdir($sTempDirPath,0777 ,true))throw new \InvalidArgumentException('Temp directory "'.$sTempDirPath.'" can\'t be created');

	//Copy directory into temp directory
	if(is_dir($sZendSkeletonApplicationPath))foreach(scandir($sZendSkeletonApplicationPath) as $sFileName){
		rcopy($sFileName,$sTempDirPath.DIRECTORY_SEPARATOR.$sFileNme);
	}

	//Remote or locale file
	else{
		if(!copy($sZendSkeletonApplicationPath,$sTempZendSkeletonApplicationFilePath  = $sTempDirPath.DIRECTORY_SEPARATOR.$sZendSkeletonApplicationPath))throw new \RuntimeException(sprintf(
			'"%s" can\'t by moved in "%s"',
			$sZendSkeletonApplicationPath,$sTempZendSkeletonApplicationFilePath
		));

		//ZendSkeletonApplication is an archive
		if(is_file($sTempZendSkeletonApplicationFilePath)){
			//Get file type
			$oFileInfo = new \finfo(FILEINFO_MIME);
			switch($oFileInfo->file($sTempZendSkeletonApplicationFilePath)){
				case 'phar':
					if(!class_exists('Phar'))throw new \InvalidArgumentException('Phar manipulation is not available, please install "Phar" php extension');
					$oPhar = new \Phar($sTempZendSkeletonApplicationFilePath);
					if(!$oPhar->extractTo($sTempDirPath))throw new \InvalidArgumentException('Phar "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be extracted');
					unset($oPhar);
					break;
				case 'rar':
					if(!class_exists('RarArchive'))throw new \InvalidArgumentException('Rar manipulation is not available, please install "Rar" php extension');
					if(!(($oRar = \RarArchive::open($sTempDirPath)) instanceof \RarArchive))throw new \InvalidArgumentException('Rar "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be opened');
					if(!is_array($aRarEntries = $oRar->getEntries()))throw new \InvalidArgumentException('Rar "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be extracted');

					foreach($aRarEntries as $oRarEntry){
						if(!$oRarEntry->extract($sTempDirPath))throw new \InvalidArgumentException('Rar entry"'.$oRarEntry.'" can\'t be extracted');
					}
					$oRar->close();
					unset($oRar,$aRarEntries,$oRarEntry);
					break;
				case 'zip':
					if(!class_exists('ZipArchive'))throw new \InvalidArgumentException('Zip manipulation is not available, please install "ZipArchive" php extension');
					$oZip = new \ZipArchive();
					if(!$oZip->open($sTempZendSkeletonApplicationFilePath))throw new \InvalidArgumentException('Zip "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be opened');
					if(!$oZip->extractTo($sTempDirPath))throw new \InvalidArgumentException('Zip "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be extracted');
					$oZip->close();
					unset($oZip);
					break;
				default:
					throw new \InvalidArgumentException('File type "'.$sFileType.'" is not supported by Deploy ZF2 module');
			}
			//Remove ZendSkeletonApplication archive
			if(!unlink($sTempZendSkeletonApplicationFilePath))throw new \RuntimeException('File "'.$sTempZendSkeletonApplicationFilePath.'" can\'t be removed');
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
			if(is_dir($sFilePath) && ($sFilePath = findZendSkeletonApplication($sFilePath)))return $sDirPath.DIRECTORY_SEPARATOR.$sFilePath;
		}
		return false;
	}

	//Find ZendSkeletonApplication files
	if(!($sTempZendSkeletonApplication = findZendSkeletonApplication($sTempDirPath)))throw new \InvalidArgumentException('ZendSkeletonApplication is not valid');

	//Copy ZendSkeletonApplication files into the deploy directory
	foreach(scandir($sTempZendSkeletonApplication) as $sFileName){
		if($sFileName != '.' && $sFileName != '..')rcopy($sFileName,$sDeployDirPath.DIRECTORY_SEPARATOR.$sFileName);
	}

	//Remove temp directory
	emptyDir($sTempDirPath);
	if(!rmdir($sTempDirPath))new \RuntimeException('Unable to remove directory "'.$sTempDirPath.'"');
}

//Adapt ZendSkeletonApplication autoloader

//Adapt ZendSkeletonApplication configuration
if(!is_array(($aApplicationConfig = include($sApplicationConfigPath = $sDirPath.DIRECTORY_SEPARATOR.'config/application.config.php'))))throw new \InvalidArgumentException('"'.$sApplicationConfigPath.'" expects retrieving an array, "'.gettype($aApplicationConfig).'" given');
if(isset($aApplicationConfig['modules'])){
	if(!is_array($aApplicationConfig['modules']))throw new \InvalidArgumentException('Application config "modules" expects an array, "'.gettype($aApplicationConfig['modules']).'" given');
}
else $aApplicationConfig['modules'] = array();

$aModules = array($sCurrentModuleName);
if($sModules = $oGetopt->getOption('modules'))$aModules += explode(',',$sModules);

$aApplicationConfig['modules'] = array_unique(array_filter(array_merge(array_values($aApplicationConfig['modules']),array_values($aModules))));
//Write new config into file
if(!file_put_contents($sApplicationConfigPath, var_export($aApplicationConfig)))new \RuntimeException('An error occurred while writing application config values into file "'.$sApplicationConfigPath.'"');

echo 'Module "'.$sCurrentModuleName.'" has been deployed into "'.$sDeployDirPath.'" with success';
exit(0);