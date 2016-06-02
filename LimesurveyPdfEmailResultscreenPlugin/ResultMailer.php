<?php
namespace PdfEmailResultscreen\Mailer;

require_once __DIR__. '/../../vendor/autoload.php';
require_once 'ResultMailerInterface.php';

use PdfEmailResultscreen\Interfaces as Interfaces;
use Yii;
use \Swift_Mailer;
use \Swift_SmtpTransport;
use \Swift_Message;
use \Swift_Attachment;
use \Swift_Validate;
use CVarDumper;


/*
* Class to send email with Swiftmailer
*
*/

class ResultMailer implements Interfaces\ResultMailerInterface{


    private $username, $password, $hostname, $port, $tsptype, $subject, $fromEmail, $fromEmailName, $bodyFormat, $body, $toEmail, $bcc, $attachment, $debug, $debugEmail;

    public function __construct()
    {

        $this->setCredentials();
        $this->setTransport();

    }

    private function setCredentials()
    {

        $this->username = Yii::app()->getConfig('emailsmtpuser');
        $this->password = Yii::app()->getConfig('emailsmtppassword');

    }

    private function setTransport()
    {

        //todo smtp only right now
        $host = Yii::app()->getConfig('emailsmtphost');
        
        $temp = (trim($host) === '') ? [] : array_map('trim', explode(':', $host ));
        $this->hostname = $temp[0];
        $this->port = intval($temp[1]);
        $this->tsptype = Yii::app()->getConfig('emailsmtpssl');

    }

    //public function setSubject(string $subject)//php7
    public function setSubject($subject)
    {

        $this->subject = $subject;

    }

    //public function setFromEmail(string $fromEmail)//php7
    public function setFromEmail($fromEmail)
    {

        $this->fromEmail = $fromEmail;

    }

    //public function setFromEmailName(string $fromEmailName)//php7
    public function setFromEmailName($fromEmailName)
    {

        $this->fromEmailName = $fromEmailName;

    }

    //public function setBodyFormat(string $bodyFormat)//php7
    public function setBodyFormat($bodyFormat)
    {

        $this->bodyFormat = $bodyFormat;

    }

    //public function setBody(string $body)//php7
    public function setBody($body)
    {

        $this->body = $body;

    }

    public function setToEmail(array $toEmail)
    {

        $this->toEmail = $toEmail;

    }

    public function setBcc(array $bcc)
    {

        $this->bcc = $bcc;

    }

    //public function setDebug(bool $debug)//php7
    public function setDebug($debug)
    {

        $this->debug = $debug;

    }

    //public function setDebugEmail(string $debugEmail)//php7
    public function setDebugEmail($debugEmail)
    {

        $this->debugEmail = [$debugEmail];

    }

    //public function setAttachment(string $srcPath, string $srcName, $attachmentName = false)//php7
    public function setAttachment($srcPath, $srcName, $attachmentName = false)
    {

        //enable multiple attachments
        if(!isset($this->attachment) ){

            $this->attachment = [];

        }

        $this->attachment[] = ['srcpath' => $srcPath, 'srcname' => $srcName, 'attachmentname' => $attachmentName];

    }



    public function sendMail()
    {


        $transporter = Swift_SmtpTransport::newInstance($this->hostname, $this->port, 'ssl')//$this->tsptype)
        ->setUsername($this->username)
        ->setPassword($this->password);

        $mailer = Swift_Mailer::newInstance($transporter);

        $message = Swift_Message::newInstance($transporter)

        ->setSubject($this->subject)

        ->setFrom(array($this->fromEmail => $this->fromEmailName))

        ->setBody($this->body, $this->bodyFormat)

        ;

        $toems = [];

        $invalidems = [];

         

        if($this->debug === true){

            $r = $this->getInvalidEmails($this->debugEmail);

            $toems =  $r['valid'];

            $invalidems = $r['invalid'];

            CVarDumper::dump($toems);

        }else{

            CVarDumper::dump(['180'=>$this->toEmail]);

            $r = $this->getInvalidEmails($this->toEmail);

            $toems = $r['valid'];

            CVarDumper::dump($toems);

            $invalidems = $r['invalid'];

        }

        if(count($toems) > 0){

            $message->setTo(array_unique($toems));

        } 

        $tobcems = [];

        $invalidbcems = [];

        if(isset($this->bcc) && count($this->bcc) !== 0){

            $re = $this->getInvalidEmails($this->bcc);

            $tobcems = $re['valid'];

            $invalidbcems = $re['invalid'];

            $message->setBcc(array_unique($tobcems));

            if($this->debug !== true && count($invalidbcems) !== 0){

                error_log(json_encode(['invalid bcc email(s)' => $invalidbcems]));

                $invalidbcems = [];

            }

        }

        if(isset($this->attachment)){

            foreach($this->attachment as $at){

                if( isset($at['attachmentname'] ) &&  $at['attachmentname']  !== ''){

                    if(strpos($at['attachmentname'] , '.pdf') !== false){

                        $message->attach(Swift_Attachment::fromPath($at['srcpath'])->setFilename($at['attachmentname']) );

                    }else{

                        $message->attach(Swift_Attachment::fromPath($at['srcpath'])->setFilename($at['attachmentname'] .'.pdf') );

                    }

                }else{

                    $message->attach(Swift_Attachment::fromPath($at['srcpath'])->setFilename($at['srcname']) );

                }

            }

        }

        $errors = [];

        $bccerrors = [];
        
        if(!$mailer->send($message, $failures)){

            foreach($failures as $val){

                if (in_array($val, $toemail)){

                    $errors[] = $val;

                }

                if (in_array($val, $bcems)){

                    //return email error for bcc when in debug mode;
                    if($this->debug === true){

                        $bccerrors[] = $val;

                    }

                }

            }

        }

        if(count($errors)>0 || count($bccerrors)>0 || count($invalidems)>0 || count($invalidbcems)>0){

            return ['mailvaliderrors'=> $invalidems, 'mailbccvaliderrors'=> $invalidbcems, 'mailerrors' => $errors, 'mailbccerrors' => $bccerrors];

        }else{

            return 'success';

        }

    }

    private function getInvalidEmails($array)
    {

        $valid = [];
        $invalid = [];

        foreach($array as $em){

            if(Swift_Validate::email($em)){

                $valid[] = $em;

            }else{

                $invalid[] = $em;

            }

        }

        return ['valid' => $valid, 'invalid' => $invalid];

    }

}