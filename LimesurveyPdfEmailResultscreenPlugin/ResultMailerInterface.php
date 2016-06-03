<?php 
namespace PdfEmailResultscreen\Interfaces;

interface ResultMailerInterface
{  

    public function setSubject($subject);

    public function setFromEmail($fromEmail);

    public function setFromEmailName($fromEmailName);

    public function setBodyFormat($bodyFormat);

    public function setBody($body);

    public function setToEmail(array $toEmail);

    public function setBcc(array $bcc);

    public function setDebug($debug);

    public function setDebugEmail($debugEmail);

    public function setAttachment($srcPath, $srcName, $attachmentName = false);

    public function sendMail();
   
}