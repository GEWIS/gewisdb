<?php

namespace User\Mapper;

use User\Model\User;
use Doctrine\ORM\EntityManager;

class UserMapper
{

    /**
     * @var EntityManager
     */
    protected $em;


    /**
     * @param EntityManager
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return User[]
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * @param string $login
     * @return User
     */
    public function findByLogin($login)
    {
        return $this->getRepository()->findBy(['login' => $login]);
    }

    /**
     * @param User $user
     */
    public function persist(User $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(User::class);
    }
}
