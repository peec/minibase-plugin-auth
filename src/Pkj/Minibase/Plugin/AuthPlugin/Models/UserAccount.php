<?php
namespace Pkj\Minibase\Plugin\AuthPlugin\Models;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Pkj\Minibase\Plugin\AuthPlugin\AuthRepository") 
 * @ORM\Table(name="user_accounts")
 **/
class UserAccount {
	
	/** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue **/
	private $id;
	
	/** @ORM\Column(type="string", unique=true) **/
	private $username;
	
	/** @ORM\Column(type="string", nullable=true) **/
	private $password;
	
	
	
	/** @ORM\Column(type="string", nullable=true) **/
	private $forgotPasswordKey;
	
	
	/** @ORM\Column(type="string", nullable=true) **/
	private $resetPasswordKey;
	
	
	/** @ORM\Column(type="string", nullable=true) **/
	private $salt;
	
	/**
	 * @ORM\OneToMany(targetEntity="Pkj\Minibase\Plugin\AuthPlugin\Models\AccountProvider", mappedBy="userAccount", cascade={"all"})
	 */
	private $providers;
	
	/**
	 * @ORM\ManyToMany(targetEntity="Pkj\Minibase\Plugin\AuthPlugin\Models\UserGroup")
	 */
	private $groups;
	
	
	/** @ORM\Column(type="string", nullable=true, unique=true) **/
	private $authToken;
	
	
	/** @ORM\Column(type="datetime", nullable=true) **/
	private $authTokenExpire;
	
	
	public function __construct () {
		$this->providers = new ArrayCollection();
		$this->groups = new ArrayCollection();
	}
	
	public function hasPasswordSet () {
		return $this->password !== null;
	}
	
	public function getId () {
		return $this->id;
	}
	
	public function getProviders () {
		return $this->providers;
	}
	
	public function getAuthToken () {
		return $this->authToken;
	}
	public function generateAuthToken () {
		$this->authToken = sha1(uniqid(rand(), true) . $this->id);
	}
	public function setAuthTokenExpire (\DateTime $date) {
		$this->authTokenExpire = $date;
	}
	public function getAuthTokenExpire () {
		return $this->authTokenExpire;
	}
	
	/**
	 * Checks if a token is valid
	 * @param string $token The token to validate.
	 * @return boolean true if token is valid. false otherwise.
 	 */
	public function isTokenValid ($token) {
		return $this->isTokenNotExpired() && $this->authToken === $token;
	}
	
	/**
	 * Checks if the authToken is valid.
	 * @return boolean true if valid, false otherwise.
	 */
	public function isTokenNotExpired () {
		if ($this->authTokenExpire === null)return true;
		if ($this->authToken === null) {
			return false;
		}
		$now = new \DateTime("now");
		
		$diff = $now->diff($this->authTokenExpire);
		if (!$diff) {
			return false;
		}
		
		if ($diff->s < 0) {
			return false;
		} else {
			return true;
		}
	}
	
	
	/**
	 * Checks if user is in a specific group
	 * @param string $identifier The identifier of the group.
	 */
	public function hasGroup ($identifier) {
		foreach($this->getGroups() as $group) {
			if ($group->getIdentifier() === $identifier) {
				return true;
			}
		}
		return false;
	}
	
	public function addProvider (AccountProvider $provider) {
		$this->providers[] = $provider;
		$provider->setUserAccount($this);
	}
	
	public function addGroup (UserGroup $userGroup) {
		$this->groups[] = $userGroup;
	}
	public function getGroups () {
		return $this->groups;
	}
	
	public function setUsername ($username) {
		$this->username = $username;
	}
	public function getUsername () {
		return $this->username;
	}
	
	public function setPassword($password) {
		$this->salt = uniqid(rand(), true);
		$this->password = crypt($password, '$6$rounds=5000$'.$this->salt.'$');
	}
	
	public function isPasswordCorrect ($password) {
		return $this->password === crypt($password, '$6$rounds=5000$'.$this->salt.'$');
	}
	
	
	
	
	
}