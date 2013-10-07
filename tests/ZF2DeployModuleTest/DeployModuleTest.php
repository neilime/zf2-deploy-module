<?php
namespace ZF2DeployModuleTest;
class DeployModuleTest extends \PHPUnit_Framework_TestCase{
	/**
	 * @var string
	 */
	protected $deployModuleCommand;

	public function setUp(){

		$this->deployModuleCommand = 'php '.getcwd().'/../bin/deploy_module.php -m '.__DIR__.'/../_file/module -d '.__DIR__.'/../_file/deploy -v';
	}
	
	public function testDeployModuleWithRemoteComposer(){
		//Run proccess
		exec($this->deployModuleCommand.' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication',$aOutput,$iReturn);
		$this->assertEquals(0,$iReturn,join(PHP_EOL,$aOutput));
	
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithRemoteZipZendSkeletonApplication(){
		//Run proccess
		exec($this->deployModuleCommand.' -c '.__DIR__.'/../_file/archives/composer.phar',$aOutput,$iReturn);
		$this->assertEquals(0,$iReturn,join(PHP_EOL,$aOutput));

		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithLocalZendSkeletonApplication(){
		//Run proccess
		exec(
			$this->deployModuleCommand.' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication -c '.__DIR__.'/../_file/archives/composer.phar',
			$aOutput,
			$iReturn
		);
		$this->assertEquals(0,$iReturn,join(PHP_EOL,$aOutput));
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithLocaleZipZendSkeletonApplication(){
		//Run proccess
		exec(
			$this->deployModuleCommand.' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication.zip -c '.__DIR__.'/../_file/archives/composer.phar',
			$aOutput,
			$iReturn
		);
		$this->assertEquals(0,$iReturn,join(PHP_EOL,$aOutput));
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithLocaleTarZendSkeletonApplication(){
		//Run proccess
		exec(
			$this->deployModuleCommand.' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication.tar.gz -c '.__DIR__.'/../_file/archives/composer.phar',
			$aOutput,
			$iReturn
		);
		$this->assertEquals(0,$iReturn,join(PHP_EOL,$aOutput));
		$this->assertRunModuleAction();
	}

	/**
	 * Run module "test-import" console action
	 */
	protected function assertRunModuleAction(){
		$sCurrentWorkingDir = getcwd();
		chdir(__DIR__.'/../_file/deploy/public');
		exec('php index.php test-module',$aOutput,$iReturn);
		chdir($sCurrentWorkingDir);
		$this->assertEquals(0,$iReturn,join(PHP_EOL,$aOutput));
		$this->assertEquals(array('ok'),$aOutput);
	}

	/**
	 * @throws \RuntimeException
	 */
	public function tearDown(){
		//Empty deploy dir
		foreach(new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(__DIR__.'/../_file/deploy', \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		) as $oFileInfo){
			if($oFileInfo->isDir()){
				if(!rmdir($oFileInfo->getPathname())){
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
}