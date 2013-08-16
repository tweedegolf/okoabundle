<?php

namespace Tg\OkoaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Serializable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Tg\OkoaBundle\Behavior\Persistable;
use Tg\OkoaBundle\Behavior\DynamicDiscriminator;

/**
 * @ORM\Entity(repositoryClass="Tg\OkoaBundle\Entity\Repository\UserRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @DynamicDiscriminator
 */
class User extends Persistable implements UserInterface, EquatableInterface, Serializable
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
     * The plain password to be hashed and stored in the database.
     * @var string
     */
    protected $plainPassword;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    protected $salt;

    /**
     * Updates the salt to a new value and removes the old password
     * The old password is removed because it is useless without the salt
     */
    public function refreshSalt()
    {
        $this->setSalt(hash('sha256', uniqid(time(), true)));
        $this->setPassword(null);
    }

    public function serialize()
    {
        return serialize([
            $this->id,
        ]);
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
        return $this->username;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        if (is_string($plainPassword) && strlen(trim($plainPassword)) > 0) {
            $this->refreshSalt();
        }
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function eraseCredentials()
    {
        $this->setPlainPassword(false);
    }

    public function isEqualTo(UserInterface $user)
    {
        return $this->getId() === $user->getId();
    }
}
