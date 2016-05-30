<?php
require_once __DIR__. '/../../vendor/autoload.php';
require_once 'TwigParser.php';


class ResultMailer {



    public function sendMail($attachmentpath, $filename, $emailsettings, $settings, $dynamicemailsettings, $data)
    {

        

        $tmplfolders = array_map('trim', explode('|', $emailsettings['emailtemplatefolders'] ));

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

        $body = $this->createBody($emailsettings['emailtemplate'], $settings, $data, $tmplfolders);


        $message = Swift_Message::newInstance($transporter)

        ->setSubject($emailsettings['emailsubject'])

        ->setFrom(array($emailsettings['fromemail'] => $emailsettings['fromemailname']))

        ->setBody($body, $emailsettings['emailtemplatetype'])

        ;

        $ems = [];

        if($settings['debug'] === '1'){

            $message->setTo(array($emailsettings['debugemail']));


        }else{

            $ems = array_map('trim', explode(',', $dynamicemailsettings['toemail'] ));

            $message->setTo(array_unique($ems));

        }

        $bcems = [];

        if($emailsettings['bcc'] !== ''){

            $bcems = array_map('trim', explode(',', $emailsettings['bcc'] ));

            $message->setBcc(array_unique($bcems));

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

        $errors = [];

        $bccerrors = [];
        
        
        if(!$mailer->send($message, $failures)){

            foreach($failures as $val){

                if (in_array($val, $ems)){

                    //return error message;
                    $errors[] = $val;

                }

                if (in_array($val, $bcems)){

                    //return email error for bcc when in debug mode;
                    if($settings['debug'] === '1'){

                        $bccerrors[] = $val;

                    }

                }

            }

       }

        if(count($errors)>0 || count($bccerrors)>0){

            return ['errors' => $errors, 'bccerrors' => $bccerrors];

        }else{


            return 'success';
        }        


    }


    private function createBody($tmplpath, $settings, $data, $tmplfolders)
    {

        $emailtwigparser = new TwigParser();

        return  $emailtwigparser->parse($settings, $tmplpath, $data, $tmplfolders);

    }

}