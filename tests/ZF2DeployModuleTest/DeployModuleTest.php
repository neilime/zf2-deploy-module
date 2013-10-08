<?php
namespace ZF2DeployModuleTest;
class DeployModuleTest extends \PHPUnit_Framework_TestCase{
	/**
	 * @var \Symfony\Component\Process\Process
	 */
	protected $process;

	public function setUp(){
		//Empty deploy dir
		if(is_dir(__DIR__.'/../_file/deploy'))foreach(new \RecursiveIteratorIterator(
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
		$this->process = new \Symfony\Component\Process\Process(
			'php '.getcwd().'/../bin/deploy_module.php -m '.__DIR__.'/../_file/module -d '.__DIR__.'/../_file/deploy -v',
			null,null,null,null
		);
	}

	public function testDeployModuleWithRemoteComposer(){
		//Run proccess
		$this->process
		->setCommandLine($this->process->getCommandLine().' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication')
		->run(function($sType, $sBuffer){fwrite(STDERR,$sBuffer);});
		$this->assertTrue($this->process->isSuccessful());
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithRemoteZipZendSkeletonApplication(){
		//Run proccess
		$this->process
		->setCommandLine($this->process->getCommandLine().' -c '.__DIR__.'/../_file/archives/composer.phar')
		->run(function($sType, $sBuffer){fwrite(STDERR,$sBuffer);});
		$this->assertTrue($this->process->isSuccessful());
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithLocalZendSkeletonApplication(){
		//Run proccess
		$this->process
		->setCommandLine($this->process->getCommandLine().' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication -c '.__DIR__.'/../_file/archives/composer.phar')
		->run(function($sType, $sBuffer){fwrite(STDERR,$sBuffer);});
		$this->assertTrue($this->process->isSuccessful());
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithLocaleZipZendSkeletonApplication(){
		//Run proccess
		$this->process
		->setCommandLine($this->process->getCommandLine().' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication.zip -c '.__DIR__.'/../_file/archives/composer.phar')
		->run(function($sType, $sBuffer){fwrite(STDERR,$sBuffer);});
		$this->assertTrue($this->process->isSuccessful());
		$this->assertRunModuleAction();
	}

	public function testDeployModuleWithLocaleTarZendSkeletonApplication(){
		//Run proccess
		$this->process
		->setCommandLine($this->process->getCommandLine().' -z '.__DIR__.'/../_file/archives/ZendSkeletonApplication.tar.gz -c '.__DIR__.'/../_file/archives/composer.phar')
		->run(function($sType, $sBuffer){fwrite(STDERR,$sBuffer);});
		$this->assertTrue($this->process->isSuccessful());
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