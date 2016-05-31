<?php 
interface ResultMailerInterface
{  

    public function sendMail($attachmentpath, $filename, $emailsettings, $settings, $dynamicemailsettings, $data);
   
}