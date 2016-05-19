<?php 
interface PdfGeneratorInterface
{  

    public function beforeActivate();  
    public function cron();  
    public function afterSurveyComplete();  
   
}