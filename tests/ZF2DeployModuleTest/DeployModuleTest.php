<?php
namespace ZF2DeployModuleTest;
class DeployModuleTest extends \PHPUnit_Framework_TestCase{
	public function testDeployModuleWithWrongLibPath(){
		define('LIB_PATH','wrong');
	}
}