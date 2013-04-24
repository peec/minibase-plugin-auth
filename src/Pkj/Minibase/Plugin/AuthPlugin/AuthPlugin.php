<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Doctrine\Common\Annotations\AnnotationRegistry;

use Minibase\Plugin\Plugin;

class AuthPlugin extends Plugin{
	private $eventCollection;
	
	public $modelDir;
	
	public function setup () {
		$this->modelDir = __DIR__ . '/Models';
		
		// Configuration.
		$providers = $this->cfg('providers', array());
		
		
		// Register providers on the mb object.
		foreach($providers as $provider => $config) {
			switch($provider) {
				case "facebook":
					$this->mb->plugin("facebookProvider", function () use ($config) {
						
						return new \Facebook($config);
					});
					break;
			}
		}
		
		
		$events = new Events($this);
		$this->eventCollection = $events;
		
		// Load the routes file.
		$this->mb->loadRouteFile(__DIR__ . '/routes.json');
		
		// Load custom annotations.
		AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Annotations.php');
		
		$that = $this;
		
		$this->mb->plugin("currentUser", function () use ($that) {
			$userid = $that->getAuthenticatedUser();
			if ($userid === null) {
				return null;
			} else {
				return $that->getRepo()->findOneById($userid);
			}
		});
		
		
	}
	
	
	/**
	 * @return Pkj\Minibase\Plugin\AuthPlugin\AuthRepository The repository for user.
	 */
	public function getRepo () {
		return $this->mb->em->getRepository('Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount');
	}
	
	public function start () {
		$this->mb->addEventCollection($this->eventCollection);
	}
	
	/**
	 * Gets the user id of the authenticated user, NULL if none.
	 */
	public function getAuthenticatedUser () {
		if (isset($_SESSION['userAccount']) && $_SESSION['userAccount']) {
			return $_SESSION['userAccount'];
		} else {
			return null;
		}
	}
	
}