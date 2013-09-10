<?php

namespace Tg\OkoaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Serializable;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Tg\OkoaBundle\Behavior\DynamicDiscriminator;
use Tg\OkoaBundle\Behavior\Persistable;

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
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $password;

    /**
     * The plain password to be hashed and stored in the database.
     * @var string
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $salt;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Updates the salt to a new value and removes the old password
     * The old password is removed because it is useless without the salt
     */
    public function refreshSalt()
    {
        $this->setSalt(hash('sha256', uniqid(time(), true)));
        $this->setPassword(null);
    }

    /**
     * Serialize user to string.
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->id,
        ]);
    }


    /**
     * Recreate the object from its serialized state.
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        list($this->id) = unserialize($serialized);
    }

    /**
     * Retrieve the roles for the user.
     * @return Role[]
     */
    public function getRoles()
    {
        $name = explode('\\', static::classname());
        $name = strtoupper(end($name));
        return array('ROLE_' . $name);
    }

    /**
     * Retrieve the password (hashed).
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the hashed password.
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Retrieve the salt used for generating the password hash.
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set the salt for generating the password hash.
     * Note that changing this makes it impossible to validate the current password.
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * Retrieve the username of the user.
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Change the username.
     * @param $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the password to a new string.
     * This will automatically be encoded when saving this object.
     * @param string $plainPassword
     * @return void
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        if (is_string($plainPassword) && strlen(trim($plainPassword)) > 0) {
            $this->refreshSalt();
        }
    }

    /**
     * Retrieve the plaintext password if it is still available.
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Remove the plaintext password from the object.
     * @return void
     */
    public function eraseCredentials()
    {
        $this->setPlainPassword(null);
    }

    /**
     * Check if two users are the same.
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        return $this->getId() === $user->getId();
    }
}
