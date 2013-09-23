<?php
namespace TestModule\Controller;
class IndexController extends \Zend\Mvc\Controller\AbstractActionController{
    /**
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
	public function indexAction(){
        $this->getServiceLocator()->get('console')->write('ok');
    	return new \Zend\View\Model\ConsoleModel();
    }
}