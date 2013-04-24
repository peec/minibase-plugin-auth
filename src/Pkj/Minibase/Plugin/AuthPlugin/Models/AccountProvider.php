<?php
namespace Pkj\Minibase\Plugin\AuthPlugin\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Pkj\Minibase\Plugin\AuthPlugin\AuthRepository")
 * @ORM\Table(name="user_providers")
 **/
class AccountProvider {
	
	/** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue **/
	private $id;
	
	/** @ORM\Column(type="string", nullable=false) **/
	private $oauthProvider;
	
	/** @ORM\Column(type="string", nullable=false) **/
	private $oauthUid;
	
	/**
	 * @ORM\ManyToOne(targetEntity="Pkj\Minibase\Plugin\AuthPlugin\Models\UserAccount", inversedBy="providers", cascade={"all"})
	 * @ORM\JoinColumn(name="user_account_id", referencedColumnName="id")
	 */
	private $userAccount;
	
	public function setOauthProvider ($oauthProvider) {
		$this->oauthProvider = $oauthProvider;
	}
	public function getOauthProvider () {
		return $this->oauthProvider;
	}
	public function setOauthUid ($oauthUid) {
		$this->oauthUid = $oauthUid;
	}
	public function getOauthUid () {
		return $this->oauthUid;
	}
	
	public function setUserAccount (UserAccount $userAccount) {
		$this->userAccount = $userAccount;
	}
}