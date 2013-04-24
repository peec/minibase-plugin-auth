<?php
namespace Pkj\Minibase\Plugin\AuthPlugin;

use Pkj\Minibase\Plugin\AuthPlugin\Models\AccountProvider;

use Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount;

use Doctrine\ORM\EntityRepository;



class AuthRepository extends EntityRepository {
	
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
		
		if ($user && $user->isPasswordCorrect($password)) {
			
			return $user;
		} else {
			return null;
		}
	}
	
	public function updateProvider ($oauthProvider, $oauthUid, $email) {
		$query = $this->_em->createQuery("
				SELECT u FROM Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount u
				WHERE u.username = ?1
				");
		$query->setParameter(1, $email);
		
		$user = $query->getOneOrNullResult();
		

		$createProvider = false;
		if ($user) {
			$providers = $user->getProviders();
			foreach($providers as $eProvider) {
				if ($eProvider->getOauthProvider() === $oauthProvider) {
					return $user;
				}
			}
			$createProvider = true;
		} else {
			$user = $this->register($email, null);
			$createProvider = true;
		}
		
		if ($createProvider) {
			
			$provider = new AccountProvider();
			$provider->setOauthProvider($oauthProvider);
			$provider->setOauthUid($oauthUid);
			
			$user->getProviders()->add($provider);
			$this->_em->persist($provider);
			$this->_em->persist($user);
			$this->_em->flush();
			
			return $user;
		}
		
	}
	
	public function register ($username, $password, array $fields = array()) {
		$user = new UserAccount();
		if (!$username) {
			throw new \Exception("Username must be provided.");
		}
		
		if ($password) {
			$user->setPassword($password);
		}
		
		$user->setUsername($username);
		
		if (!empty($fields)) {
			
			$ref = new \ReflectionObject($user);
			foreach($fields as $k => $v) {
				$setter = 'set' . ucfirst($k);
				if (method_exists($user, $setter)) {
					$user->$setter($v);
				} else {
					$prop = $ref->getProperty($k);
					$prop->setAccessible(true);
					$prop->setValue($user, $v);
				}
			}
			
		}
		
		$this->_em->persist($user);
		$this->_em->flush($user);
		return $user;
	}
	
	
	
	public function changePassword (UserAccount $u, $old, $new, $confirm) {
		if (!$u->isPasswordCorrect($old)){
			throw new \Exception("Old password is incorrect.");
		}
		
		if ($new !== $confirm || !$new) {
			throw new \Exception("The password confirmation was incorrect.");
		}
		
		$u->setPassword($new);
		$this->_em->persist($u);
		$this->_em->flush($u);
	}
	
	
}