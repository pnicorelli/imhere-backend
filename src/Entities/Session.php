<?php

namespace ImHere\Entities;
use Doctrine\Mapping as ORM;

/**
 * @Entity
 * @Table(name="sessions")
 */
class Session
{
    /**
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="User")
     */
    private $user;

    /**
     *
     * @Column(name="token", type="string", length=50, nullable=false)
     */
    private $token;


    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = $this->generateToken();
    }

    public function getToken(){
        return $this->token;
    }

    private function generateToken(){
      return bin2hex(random_bytes(32));
    }
}
