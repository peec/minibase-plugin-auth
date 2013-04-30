<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount;

use Doctrine\Common\Annotations\AnnotationRegistry;

use Minibase\Plugin\Plugin;

class AuthPlugin extends Plugin{
	
	const DOMAIN_NAME = 'authPlugin';
	
	private $eventCollection;
	
	public $modelDir;
	
	public function defaultConfig () {
		return array(
			// Custom Providers login.
			'providers' => array(),
			// Default API settings.
			'api' => array(
				// Makes sure that the token is the same.
				'ensure_token' => true,
				// Sets a timeout of when token expires. Uses DateInterval
				'expire_timeout' => false
			)
		);	
	}
	
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
			} else if (is_object($userid) && $userid instanceof UserAccount) {
				return $userid;
			}else {
				return $that->getRepo()->findOneById($userid);
			}
		});
		
		$this->mb->trans->load(
				self::DOMAIN_NAME, 
				__DIR__ . '/locale',
				'en_GB',
				array('php:' . __DIR__, 
						'twig:'. __DIR__ . '/views'
						));
		
	}
	
	
	/**
	 * @return Pkj\Minibase\Plugin\AuthPlugin\AuthRepository
	 */
	public function getRepo () {
		$repo = $this->mb->em->getRepository('Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount');
		$repo->setPluginConfig($this->config);
		$repo->setEvents($this->mb->events);
		$repo->setTrans($this->mb->trans);
		return $repo;
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
		} elseif (isset($_GET['auth_token'])) {
			return $this->getRepo()->findOneByAuthToken($_GET['auth_token']);
		}else {
			return null;
		}
	}
	
}