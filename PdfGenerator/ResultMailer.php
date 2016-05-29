<?php
require_once __DIR__. '/../../vendor/autoload.php';


class ResultMailer {



    public function sendMail($attachmentpath, $filename, $emailsettings, $settings, $dynamicemailsettings)
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

        $body = $this->createBody($emailsettings['emailtemplate'], $settings, $dynamicemailsettings['variables']);


        $message = Swift_Message::newInstance($transporter)

        // Give the message a subject
        ->setSubject($emailsettings['emailsubject'])

        // Set the From address with an associative array
        ->setFrom(array($emailsettings['fromemail'] => $emailsettings['fromemailname']))

        // Set the To addresses with an associative array
        //->setTo(array('receiver@domain.org', 'other@domain.org' => 'A name'))
        //->setTo(array('rienk.eisma@gmail.com'))

        // Give it a body

        ->setBody($body, $emailsettings['emailtemplatetype'])

        // And optionally an alternative body
        //->addPart('Deze email is automatisch gegenereerd.', 'text/html')
      

        // Optionally add any attachments

        ;

        if($settings['debug'] === '1'){

            $message->setTo(array($emailsettings['debugemail']));


        }else{

            $message->setTo($dynamicemailsettingsp['toemail']);

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


    private function createBody($tmplpath, $settings, $variables)
    {

        $bodyhtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/plugins/PdfGenerator/emailtemplates/'.$tmplpath);

        $bodyhtml = html_entity_decode($bodyhtml);

        return  $this->emailReplaceHelper($variables, $bodyhtml);

    }


    private function emailReplaceHelper($variables, $html)

    {

        $searcharr = [];

        $replarr = [];

        foreach($variables as  $vark => $varv){

            $varv = $varv;

            $vark = trim($vark);

            $searcharr[] = "{!-$vark-!}";

            if(!is_array($varv) && trim($varv) === ''){

                $rvar = '';

            }else{

                //email no quotes
                $rvar = trim($varv, '"');
                $rvar = trim($varv, "'");
                
            }

            $replarr[] = $rvar;

        }

        $replarr = array_unique($replarr);

        $replaced = str_replace($searcharr, $replarr, $html);
        
        return $replaced;

    }

}