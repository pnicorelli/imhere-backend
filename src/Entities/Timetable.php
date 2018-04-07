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

    public function checkIn( $datetime = 'now' ){
        $this->checkin = new \DateTime($datetime);
    }

    public function checkOut( $datetime = 'now' ){
        $this->checkout = new \DateTime($datetime);
    }

    public function getCheckIn(){
        return  $this->checkin->format('Y-m-d H:i:s');
    }

    public function getCheckOut(){
        return  (is_null($this->checkout)) ? null : $this->checkout->format('Y-m-d H:i:s');
    }

    public function totalTime(){
      $difference = 0;
      if(  !is_null($this->checkout) ){
        $difference = $this->checkout->diff( $this->checkin )->format('%h');
      }
      return $difference;
    }
}
