<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount;

use Minibase\Mvc\Controller;

use Pkj\Minibase\Plugin\AuthPlugin\Annotation as Restrict;


class AuthController extends Controller {

	private $plugin;
	
	
	/**
	 * Logs in a user, also regenerates the session ID.
	 * @param UserAccount $user
	 */
	private function loginUser (UserAccount $user) {
		// Security prevention
		$this->mb->session->migrate();
		$this->mb->session->set('userAccount', $user->getId());
	}
	
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
			$this->loginUser($user);
			$this->session->getFlashBag()->add('success', sprintf(dgettext("authPlugin", "Welcome %s."), $user->getUsername()));
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage')->reverse());
		} else {
			return $this->respond("html")
				->view("AuthPlugin/login.html", array("authMessage" => array('msg' => 
						dgettext("authPlugin", 'Invalid login credentials.')
						)));
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

			$this->loginUser($user);
			
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
		
		$this->mb->session->remove('userAccount');
		$this->mb->session->migrate();
		$this->session->getFlashBag()->add('success', dgettext("authPlugin", "You are logged out. Welcome back."));
		
		return $this->respond("redirect")
			->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.login')->reverse());
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
		$password_confirm = $_POST['password_confirm'];
		
		$fields = array();
		try {
			$user = $this->getPlugin()->getRepo()->register($username, $password, $password_confirm);
			
			

			$this->session->getFlashBag()->add('success', dgettext("authPlugin", 'Thank you for creating an account. You may login.'));
			
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.login')->reverse());
		} catch(\Exception $e) {
			return $this->respond("html")
				->view("AuthPlugin/register.html", array('authMessage' => array('msg' => 
						$e->getMessage()
						)));
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
			
			$this->session->getFlashBag()->add('success', dgettext("authPlugin", 'Your password has been changed.'));
			
			return $this->respond("redirect")
			->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.manage')->reverse());
			
		}catch (\Exception $e) {
			
			return $this->respond("html")
				->view("AuthPlugin/manage.html", array('passwordChangeMessage' => array('msg' => 
						$e->getMessage())
						));
		}
		
	}
	
	
	public function recoverAccount () {
		return $this->respond("html")
			->view("AuthPlugin/recover_account.html");
	}
	
	
	public function postRecoverAccount () {
		$email = $_POST['email'];
		
		try {
			$user = $this->getPlugin()->getRepo()->accquireResetPassword($email);
			
			$link = $this->request->domain . $this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.resetPassword')->reverse() . '?reskey='. $user->getForgotPasswordKey();
			$fromEmail = $this->mb->get('Pkj\Minibase\Plugin\MailerPlugin\MailerPlugin')->cfg('defaultFromEmail', $this->getPlugin()->cfg('fromEmail'));
			
			$message = \Swift_Message::newInstance()
				->setFrom($fromEmail)
				->setTo($email)
				->setSubject(dgettext("authPlugin", 'Password recovery'))
				->setBody($this->renderView('AuthPlugin/Mail/forgot_password.twig', array(
						'user' => $user,
						'reset_link' => $link
						)));
			
			$this->mb->mailer->send($message);
			
			$this->session->getFlashBag()->add('success', dgettext("authPlugin", 'Please check your email, we have sent instructions on how to reset your password.'));
			
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.recoverAccount')->reverse());
		} catch (\Exception $e) {
			return $this->respond("html")
				->view("AuthPlugin/recover_account.html", array('recoverMessage' => array('msg' => $e->getMessage())));
		}
		
		
	}
	
	
	public function resetPassword () {
		
		
		try {
			
			if (!isset($_GET['reskey'])) {
				throw new \Exception(dgettext("authPlugin", "No reset key defined. Please try again."));	
			}
			$key = $_GET['reskey'];
		
			$user = $this->getPlugin()->getRepo()->validateResetKey($key);
			
			
			$link = $this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.postResetPassword')->reverse() . '?auth_token=' . $user->getAuthToken();
			
			return $this->respond("html")
				->view("AuthPlugin/reset_password.html", array(
						'user' => $user,
						'resetPasswordPostLink' => $link));
		} catch(\Exception $e) {
			$this->session->getFlashBag()->add('success',dgettext("authPlugin", 'We could not validate the password-reset code. Please try again.'));
			
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.recoverAccount')->reverse());
		}
		
	}
	
	/**
	 * @Restrict\Authenticated
	 */
	public function postResetPassword () {
		$password = $_POST['password'];
		$password_confirm = $_POST['password_confirm'];
		try {
			// Validate the current token.
			$user = $this->getPlugin()->getRepo()->validateResetKey($this->currentUser->getForgotPasswordKey());
			// Remove the old passwords.
			$user->removePassword();
			// Change the password
			$this->getPlugin()->getRepo()->changePassword($user, null, $password, $password_confirm);
			
			$this->session->getFlashBag()->add('success', dgettext("authPlugin", 'Your password has been reset, you may now login with your new password.'));
			
			return $this->respond("redirect")
				->to($this->call('Pkj/Minibase/Plugin/AuthPlugin/AuthController.login')->reverse());
		} catch(\Exception $e) {
			return $this->respond("html")
			->view("AuthPlugin/reset_password.html", array('recoverMessage' => array('msg' => $e->getMessage())));
		}
	}
	
	
}