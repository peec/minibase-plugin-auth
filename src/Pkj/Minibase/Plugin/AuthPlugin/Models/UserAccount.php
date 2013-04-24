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
	
	public function __construct () {
		$this->providers = new ArrayCollection();
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
	
	
	public function addProvider (AccountProvider $provider) {
		$this->providers[] = $provider;
		$provider->setUserAccount($this);
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