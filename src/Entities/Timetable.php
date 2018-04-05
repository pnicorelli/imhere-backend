<?php

namespace ImHere\Entities;
use Doctrine\Mapping as ORM;

/**
 * @Entity
 * @Table(name="timetable")
 */
class Timetable
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
     * @Column(name="checkin", type="datetime")
     */
    private $checkin;

    /**
     *
     * @Column(name="checkout", type="datetime", nullable=TRUE)
     */
    private $checkout;


    public function __construct()
    {

    }

    public function setUser(User $user){
      $this->user = $user;
    }

    public function checkIn(){
        $now = new \DateTime('now');
        // $this->checkInAlreadyMade($now);
        $this->checkin = $now;
    }

    public function checkOut(){
        $this->checkout = new \DateTime('now');
    }

    public function getCheckIn(){
        return  $this->checkin->format('Y-m-d H:i:s');
    }

    public function getCheckOut(){
        return  $this->checkout->format('Y-m-d H:i:s');
    }

}
