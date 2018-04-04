<?php

namespace ImHere\Entities;
use Doctrine\Mapping as ORM;

/**
 * @Entity
 * @Table(name="users")
 */
class User
{
    /**
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     *
     * @Column(name="email", type="string", length=50, unique=true)
     */
    private $email;

    /**
     *
     * @Column(name="username", type="string", length=50, nullable=false)
     */
    private $username;

    /**
     *
     * @Column(name="domain", type="string", length=50, nullable=false)
     */
    private $domain;


    public function getId()
    {
        return $this->id;
    }


    public function getEmail()
    {
        return $this->email;
    }


    public function setEmail($email)
    {
        list($this->username, $this->domain) = explode('@', $email);
        $this->email = $email;
        return $this;
    }
}
