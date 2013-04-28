<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Minibase\Wreqr\EventBinder;

use Pkj\Minibase\Plugin\AuthPlugin\Models\AccountProvider;

use Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount;

use Doctrine\ORM\EntityRepository;


/**
 * 
 * @author peec
 *
 */
class AuthRepository extends EntityRepository {
	
	private $pluginConfig;
	private $events;
	
	public function setPluginConfig (array $pluginConfig) {
		$this->pluginConfig = $pluginConfig;
	}
	
	public function setEvents (EventBinder $events) {
		$this->events = $events;
	}
	
	/**
	 * Returns entity (object) if successful, else NULL
	 * @param string $username The username
	 * @param string $password The password
	 */
	public function login ($username, $password) {
		$query = $this->_em->createQuery("
				SELECT u FROM Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount u
				WHERE u.username = ?1
				");
		$query->setParameter(1, $username);
		
		$user = $query->getOneOrNullResult();
		
		$apiConfig = $this->pluginConfig['api'];
		
		if ($password && $user && $user->isPasswordCorrect($password)) {
			
			if (!$user->getAuthToken() || !$apiConfig['ensure_token']) {
				$user->generateAuthToken();
			}
			// If configured to set a timeout, lets set it.
			if ($apiConfig['expire_timeout']) {
				$expire = new \DateTime("now");
				$expire->add(\DateInterval::createFromDateString($apiConfig['expire_timeout']));
				$user->setAuthTokenExpire($expire);
			} else {
				$user->setAuthTokenExpire(null);
			}
			
			$this->events->trigger('auth:login:before', array($user));
			
			$this->_em->persist($user);
			$this->_em->flush($user);
			
			$this->events->trigger('auth:login:after', array($user));
			return $user;
		} else {
			return null;
		}
	}
	
	public function updateProvider ($oauthProvider, $oauthUid, $email) {
		$query = $this->_em->createQuery("
				SELECT u, p FROM Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount u
				LEFT JOIN u.providers p
				WHERE u.username = ?1
				");
		$query->setParameter(1, $email);
		
		$user = $query->getOneOrNullResult();
		

		$createProvider = false;
		if ($user) {
			$providers = $user->getProviders();
			foreach($providers as $eProvider) {
				if ($eProvider->getOauthProvider() === $oauthProvider && $eProvider->getOauthUid() === $oauthUid) {
					return $user;
				}
			}
			$createProvider = true;
		} else {
			$user = $this->register($email, null, null, true);
			$createProvider = true;
		}
		
		if ($createProvider) {
			
			$provider = new AccountProvider();
			$provider->setOauthProvider($oauthProvider);
			$provider->setOauthUid($oauthUid);
			$user->addProvider($provider);
			$this->_em->persist($user);
			$this->_em->flush($user);
			
			return $user;
		}
		
	}
	
	/**
	 * Checks if a user exists, if it does it returns the user.
	 * @param string $username Email / Username.
	 * @return Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount
	 */
	public function userExists ($username) {
		return $this->findOneByUsername($username);
	}
	
	
	public function register ($username, $password, $password_confirm, $providerRegistration=false) {
		$user = new UserAccount();
		if (!$username) {
			throw new \Exception("Username must be provided.");
		}
		
		if ($this->userExists($username)) {
			throw new \Exception("Username already registered.");
		}
		
		if (!$providerRegistration) {
			if ($password_confirm != $password) {
				throw new \Exception("Password confirmation is incorrect.");
			}
			if ($password) {
				$user->setPassword($password);
			} else {
				throw new \Exception("Password must be set.");
			}
		}
		
		
		$user->setUsername($username);
		
		if (!$providerRegistration) {
			$this->events->trigger('auth:register:before', array($user));
		}
		
		$this->_em->persist($user);
		$this->_em->flush($user);
		
		if (!$providerRegistration) {
			$this->events->trigger('auth:register:after', array($user));
		}
		return $user;
	}
	
	
	public function accquireResetPassword ($email) {

		$user = $this->userExists($email);
		if (!$user) {
			throw new \Exception ("User does not exist.");
		}
		
		if (!$user->hasPasswordSet()) {
			$providers = $user->getProviders();
			$provStrings = '';
			foreach($providers as $provider) {
				$provStrings .= $provider->getOauthProvider() . ', ';
			}
			throw new \Exception("Your account has no password because you have used other login providers and not set a password. Login with one of $provStrings.");
		}
		
		if (!$user->getForgotPasswordKey()) {
			$user->generateForgotPasswordKey();
			
			$this->_em->persist($user);
			$this->_em->flush($user);
		}
		return $user;
	}
	
	public function changePassword (UserAccount $u, $old, $new, $confirm) {
		if ($u->hasPasswordSet() && !$u->isPasswordCorrect($old)){
			throw new \Exception("Old password is incorrect.");
		}
		
		if ($new !== $confirm || !$new) {
			throw new \Exception("The password confirmation was incorrect.");
		}
		
		$u->setPassword($new);
		$this->_em->persist($u);
		$this->_em->flush($u);
		
		return $u;
	}
	
	
}