<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Minibase\Mvc\Controller;

use Pkj\Minibase\Plugin\Csrf\Annotation\IgnoreCsrfProtection;

/**
 * 
 * @IgnoreCsrfProtection
 */
class ApiAuthController extends Controller {
	private $plugin;
	
	/**
	 * Returns the AuthPlugin instance.
	 * @return Pkj\Minibase\Plugin\AuthPlugin\AuthPlugin 
	 */
	protected function getPlugin () {
		return $this->mb->get('Pkj\Minibase\Plugin\AuthPlugin\AuthPlugin');
	}
	
	
	public function login () {
		$data = $this->request->json();
		
		if (!isset($data->username) || !isset($data->password)) {
			return $this->respond("json")
				->data([
						"error" => "username and password must be provided."
						])
				->with(400);
		}
		
		$username = $data->username;
		$password = $data->password;
		
		
		
		$user = $this->getPlugin()->getRepo()->login($username, $password);
		if ($user) {
			$data = [
						"success" => true,
						"authToken" => $user->getAuthToken(),
						"userId" => $user->getId()
						];
			if ($user->getAuthTokenExpire()) {
				$data['authTokenExpire'] = $user->getAuthTokenExpire()->getTimestamp();
			}
			
			$data['ensuredToken'] = $this->getPlugin()->config['api']['ensure_token'];
			
			return $this->respond("json")
				->data($data)
				->with(200);
		} else {
			return $this->respond("json")
				->data(["error" => "Invalid username or password."])
				->with(400);
		}
	}
	
	public function register () {
		$data = $this->request->json();
		
		if (!isset($data->username) || !isset($data->password) || !isset($data->password_confirm)) {
			return $this->respond("json")
				->data([
					"error" => "username, password and password_confirm must be provided."
				])
				->with(400);
		}
		
		
		$username = $data->username;
		$password = $data->password;
		$password_confirm = $data->password_confirm;
		
		
		try {
			$user = $this->getPlugin()->getRepo()->register($username, $password, $password_confirm);
			return $this->respond("json")
			->data([
					"success" => "Registered new user account.", 
					"user" => array(
							"id" => $user->getId(),
							"username" => $user->getUsername()
							)
					]);
		} catch (\Exception $e) {
			return $this->respond("json")
			->data([
					"error" => $e->getMessage()
					])
					->with(400);
		}
		
		
	}
	
	
}