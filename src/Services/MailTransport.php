<?php
namespace ImHere\Services;
use Pimple\Container as Container;
use Pimple\ServiceProviderInterface as ServiceProviderInterface;

class MailTransport implements ServiceProviderInterface
{
    public function register(Container $container)
    {
      $container['mail.transport'] = function(){
        $driver = getenv('mail.transport');

        switch ($driver) {
          //Hey, here we can implements SMTP / sendmail / human postman....
          case 'gmail.smtp':
            $service = new Mail\GmailSmtpWrapper();
            break;
          default:
            throw new \Exception('mail.transport need proper configuration', 1);
        }

        if( ! ($service instanceof Mail\MailInterface) ){
          throw new \Exception('mail.transport should return a MailInterface instance', 1);
        }
        return $service;
      };
    }

}
