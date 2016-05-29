<?php
require_once __DIR__. '/../../vendor/autoload.php';
require_once 'TwigParser.php';


class ResultMailer {



    public function sendMail($attachmentpath, $filename, $emailsettings, $settings, $dynamicemailsettings, $tmplfolders)
    {

        $username = Yii::app()->getConfig('emailsmtpuser');
        $password = Yii::app()->getConfig('emailsmtppassword');

        $host = Yii::app()->getConfig('emailsmtphost');

        $temp = explode(':', $host);

        $hostname = $temp[0];
        $port = intval($temp[1]);

        $transporter = Swift_SmtpTransport::newInstance($hostname, $port, Yii::app()->getConfig('emailsmtpssl'))
        ->setUsername($username)
        ->setPassword($password);

        $mailer = Swift_Mailer::newInstance($transporter);

        $body = $this->createBody($emailsettings['emailtemplate'], $settings, $dynamicemailsettings['variables'], $tmplfolders);


        $message = Swift_Message::newInstance($transporter)

        ->setSubject($emailsettings['emailsubject'])

        ->setFrom(array($emailsettings['fromemail'] => $emailsettings['fromemailname']))

        ->setBody($body, $emailsettings['emailtemplatetype'])

        ;

        if($settings['debug'] === '1'){

            $message->setTo(array($emailsettings['debugemail']));


        }else{

            $message->setTo($dynamicemailsettings['toemail']);

        }

        if($emailsettings['attachpdf'] === '1'){

            if( isset($emailvariables['filename']) &&  $emailvariables['filename'] !== ''){

                if(strpos($emailvariables['filename'], '.pdf') !== false){

                    $message->attach(Swift_Attachment::fromPath($attachmentpath)->setFilename($emailvariables['filename']));

                }else{

                    $message->attach(Swift_Attachment::fromPath($attachmentpath)->setFilename( $emailvariables['filename'].'.pdf') );

                }

            }else{

                $message->attach(Swift_Attachment::fromPath($attachmentpath)->setFilename( $filename) );

            }

        }
        
        
        $result =  $mailer->send($message);

        return $result;


    }


    private function createBody($tmplpath, $settings, $variables, $tmplfolders)
    {

        $emailtwigparser = new TwigParser();

        return  $emailtwigparser->parse($settings, $tmplpath, $data, $tmplfolders);

    }

}