<?php 
interface  LimesurveyPdfEmailResultscreenPluginInterface
{  

    public function beforeActivate();  
    public function cron();  
    public function afterSurveyComplete();  
   
}