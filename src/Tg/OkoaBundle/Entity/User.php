<?php

namespace Tg\OkoaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Serializable;
use Symfony\Component\Security\Core\User\UserInterface;
use Tg\OkoaBundle\Behavior\Persistable;

/**
 * @ORM\Entity(repositoryClass="Tg\OkoaBundle\Entity\UserRepository")
 */
class User extends Persistable implements UserInterface, Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @var string
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    protected $salt;

    public function __construct()
    {
        $this->salt = hash('sha256', uniqid(time(), true));
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
        ));
    }

    public function unserialize($serialized)
    {
        list($this->id) = unserialize($serialized);
    }

    public function getRoles()
    {
        $name = explode('\\', static::classname());
        $name = strtoupper(end($name));
        return array('ROLE_' . $name);
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {

    }

    public function eraseCredentials()
    {

    }
}
