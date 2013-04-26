<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Minibase\Mvc\Controller;

use Pkj\Minibase\Plugin\AuthPlugin\Annotation as Restrict;


class AuthController extends Controller {

	private $plugin;
	
	/**
	 * Returns the AuthPlugin instance.
	 */
	protected function getPlugin () {
		return $this->mb->get('Pkj\Minibase\Plugin\AuthPlugin\AuthPlugin');
	}
	
	/**
	 * @Restrict\NotAuthenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage")
	 */
	public function login () {
		
		return $this->respond("html")
			->view("AuthPlugin/login.html");
	}
	
	
	/**
	 * @Restrict\NotAuthenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage")
	 */
	public function postLogin () {
		$username = $_POST['username'];
		$password = $_POST['password'];
		
		$user = $this->getPlugin()->getRepo()->login($username, $password);
		
		if ($user) {
			$_SESSION['userAccount'] = $user->getId();
			
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage')->reverse())
				->flash(array("success" => "Welcome {$user->getUsername()}."));
		} else {
			return $this->respond("html")
				->view("AuthPlugin/login.html", array("authMessage" => array('msg' => 'Invalid login credentials.')));
		}
	}
	
	/**
	 * @Restrict\NotAuthenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage")
	 */
	public function oAuthLogin ($params) {
		switch($params[0]) {
			case "facebook":
				return $this->facebook();
				break;
		}
	}
	
	/**
	 * Facebook Provider
	 */
	protected function facebook () {
		$facebook = $this->mb->facebookProvider;
		
		$url = $this->request->domain . $this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.facebookCallback')->reverse();
		$params = array(
				'scope' => 'email',
				'redirect_uri' => $url
		);
		$loginUrl = $facebook->getLoginUrl($params);
		
		return $this->respond("redirect")
			->to($loginUrl);
		
	}
	
	
	public function facebookCallback () {
		$facebook = $this->mb->facebookProvider;
		
		
		$userId = $facebook->getUser();
		
		
		if (!$userId) {
			die("User id not found.");
		}
		try {
			$userProfile = $facebook->api("/me");

			
			$user = $this->getPlugin()->getRepo()->updateProvider('facebook', $userId, $userProfile['email']);

			$_SESSION['userAccount'] = $user->getId();
			return $this->respond("redirect")
			->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage')->reverse());
		} catch (\FacebookApiException $e) {
			error_log($e);
					
			throw $e;
		}
		
	}
	
	
	/**
	 * @Restrict\Authenticated
	 */
	public function logout () {
		unset($_SESSION['userAccount']);
		session_regenerate_id();
		return $this->respond("redirect")
			->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.login')->reverse())
			->flash(array("success" => "You are logged out. Welcome back."));
	}
	
	
	/**
	 * @Restrict\NotAuthenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage")
	 */
	public function register () {
		
		
		
		return $this->respond("html")
			->view("AuthPlugin/register.html");
	}
	
	
	/**
	 * @Restrict\NotAuthenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage")
	 */
	public function postRegister () {
		$username = $_POST['username'];
		$password = $_POST['password'];
		
		$fields = array();
		$this->mb->events->trigger("plugin:AuthPlugin:register:customfields", array(&$fields));
		try {
			$this->getPlugin()->getRepo()->register($username, $password, $fields);
			
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.login')->reverse())
				->flash(array('success' => 'Thank you for creating an account. You may login.'));
		} catch(\Exception $e) {
			return $this->respond("html")
				->view("AuthPlugin/register.html", array('authMessage' => array('msg' => "Username and password must be defined.")));
		}
	}
	
	/**
	 * @Restrict\Authenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.login")
	 */
	public function manage () {
		return $this->respond("html")
			->view("AuthPlugin/manage.html");
	}
	
	
	/**
	 * @Restrict\Authenticated(redirect="Pkj/Minibase/Plugin/AuthPlugin/AuthController.login")
	 */
	public function changePassword () {
		$old_password = $_POST['old_password'];
		$password = $_POST['password'];
		$password_confirm = $_POST['password_confirm'];
		
		try{
			$this->getPlugin()->getRepo()->changePassword($this->mb->currentUser, $old_password, $password, $password_confirm);
			return $this->respond("redirect")
			->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage')->reverse())
			->flash(array('success' => 'Your password has been changed.'));
		}catch (\Exception $e) {
			
			return $this->respond("html")
				->view("AuthPlugin/manage.html", array('passwordChangeMessage' => array('msg' => $e->getMessage())));
		}
		
	}
	
	
}