<?php
namespace ImHere\Services\Mail;

interface MailInterface
{
  public function setTo($email);
  public function setSubject($subject);
  public function setBody($body);
  public function send();
}
