<?php
namespace PdfEmailResultscreen\Interfaces;

interface  LimesurveyPdfEmailResultscreenPluginInterface
{  

    public function beforeActivate();  
    public function cron();  
    public function afterSurveyComplete();  
   
}