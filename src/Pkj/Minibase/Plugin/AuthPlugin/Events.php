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
		$resp = null;
		
		if ($annotation instanceof Restrict\UserGroups) {
			$ok = false;
			if ($this->mb->currentUser) {
				$groups = $this->mb->currentUser->getGroups();
				foreach($groups as $group) {
					if (in_array($group->getIdentifier(), $annotation->groups)) {
						$ok = true;
						continue;
					}
				}
			}
			
			if (!$ok) {
				$resp = $controller->respond("html")
					->view("AuthPlugin/not_enough_privelegies.html");
			}
			
		} else if ($annotation instanceof Restrict\AuthAnnotation) {
			
			if ($annotation instanceof Restrict\Authenticated) {
				if ($this->plugin->getAuthenticatedUser() === null) {
					if ($annotation->redirect) {						
						$resp = $controller->respond("redirect")
						->to($controller->call($annotation->redirect)->reverse());
					}else {
						$resp = $controller->respond("html")
							->view("AuthPlugin/must_authenticate.html");
					}
				}
			}else if ($annotation instanceof Restrict\NotAuthenticated) {
				if ($this->plugin->getAuthenticatedUser() !== null) {
					if ($annotation->redirect) {
						$resp = $controller->respond("redirect")
						->to($controller->call($annotation->redirect)->reverse());
					} else {
						$resp = $controller->respond("html")
						->view("AuthPlugin/must_not_authenticate.html");	
					}
				}
			}	
		}
		
		return $resp;
	}
	
	/**
	 * @Annotation\Event("before:render")
	 */
	public function addViewVar ($view, &$vars) {
		$vars['currentUser'] = $this->mb->currentUser;
	}
	
	
}