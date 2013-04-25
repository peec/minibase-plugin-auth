<?php
namespace Pkj\Minibase\Plugin\AuthPlugin\Models;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Pkj\Minibase\Plugin\AuthPlugin\AuthRepository")
 * @ORM\Table(name="user_groups")
 **/
class UserGroup {
	
	/** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue **/
	private $id;
	
	
	/** @ORM\Column(type="string", unique=true) **/
	private $identifier;
	
	
	/** @ORM\Column(type="string") **/
	private $title;
	
	/** @ORM\Column(type="text") **/
	private $description;
	
	
	public function getIdentifier () {
		return $this->identifier;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function getId () {
		return $this->id;
	}
	
	public function getTitle () {
		return $this->title;
	}
	
	public function getDescription () {
		return $this->description;
	}
	
	public function setIdentifier ($identifier) {
		$this->identifier = $identifier;
	}
	
	public function setTitle ($title) {
		$this->title = $title;
	}
	
	public function setDescription ($description) {
		$this->description = $description;
	}
	
	
}