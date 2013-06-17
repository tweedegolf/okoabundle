<?php

namespace Tg\OkoaBundle\Entity\Manager;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Tg\OkoaBundle\Entity\User;

/**
 * Updates user passwords in the correct fields
 */
class UserManager implements EventSubscriber
{
    protected $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function getEncoder(User $user)
    {
        return $this->encoderFactory->getEncoder($user);
    }

    public function updateUser(User $user)
    {
        $plainPassword = $user->getPlainPassword();
        if (is_string($plainPassword) && strlen(trim($plainPassword)) > 0) {
            $encoder = $this->getEncoder($user);
            $user->setPassword($encoder->encodePassword($plainPassword, $user->getSalt()));
            $user->eraseCredentials();
            return true;
        } else {
            return false;
        }
    }

    public function preUpdate(PreUpdateEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            if ($this->updateUser($user)) {
                $event->setNewValue('password', $user->getPassword());
            }
        }
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $user = $event->getEntity();
        if ($user instanceof User) {
            $this->updateUser($user);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [Events::prePersist, Events::preUpdate];
    }
}
