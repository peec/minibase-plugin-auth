<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Minibase\Wreqr\EventCollection;
use Minibase\Annotation;
use Pkj\Minibase\Plugin\AuthPlugin\Annotation as Restrict;

class Events extends EventCollection{

	private $plugin;
	
	public function __construct(AuthPlugin $plugin) {
		$this->plugin = $plugin;
	}
	
	/**
	 * @Annotation\Event("plugin:doctrine:entityDirs")
	 */
	public function extendEntityDirs (&$entityDirs) {
		$entityDirs[] = $this->plugin->modelDir;
	}
	
	/**
	 * @Annotation\Event("plugin:twig:loader")
	 */
	public function addViewFolder ($loader) {
		$loader->addPath(__DIR__ . '/views');
	}
	
	/**
	 * @Annotation\Event("mb:call:execute:annotation")
	 */
	public function listenToAnnotations ($annotation, $controller) {
		if ($annotation instanceof Restrict\Authenticated) {
			if ($this->plugin->getAuthenticatedUser() === null) {
				if ($annotation->redirect) {
					
					$resp = $controller->respond("redirect")
						->to($controller->call($annotation->redirect)->reverse());
				}else {
					$resp = $controller->respond("html")
					->view("AuthPlugin/must_authenticate.html");
				}
				return $resp;
			}
		}else if ($annotation instanceof Restrict\NotAuthenticated) {
			if ($this->plugin->getAuthenticatedUser() !== null) {
				return $controller->respond("html")
					->view("AuthPlugin/must_not_authenticate.html");
			}
		}
	}
	
	/**
	 * @Annotation\Event("before:render")
	 */
	public function addViewVar ($view, &$vars) {
		$vars['currentUser'] = $this->mb->currentUser;
	}
	
	
}