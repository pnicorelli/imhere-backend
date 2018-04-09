<?php

namespace ImHere\Services\Mail;

class GmailSmtpWrapper implements MailInterface
{
  private $to;
  private $subject;
  private $body;

  public function GmailSmtpWrapper()
  {

  }

  public function setTo($email){
      $this->to = $email;
  }

  public function setSubject($subject){
      $this->subject = $subject;
  }

  public function setBody($body){
      $this->body = $body;
  }

  public function send(){
    $username = getenv('gmail.smtp.username');
    $password = getenv('gmail.smtp.password');
    if( !$username  || !$password ){
      throw new \Exception("GmailSmtpWrapper needs credentials", 1);
    }

    $transport = (new \Swift_SmtpTransport('smtp.gmail.com', 465, "ssl"))
      ->setUsername($username)
      ->setPassword($password);

    $mailer = new \Swift_Mailer($transport);
    $message = (new \Swift_Message($this->subject))
        ->setFrom(array($username => '[ImHere]'))
        ->setTo($this->to)
        ->setBody($this->body);
    $message->setContentType('text/html');

    return $mailer->send($message);
  }

}
