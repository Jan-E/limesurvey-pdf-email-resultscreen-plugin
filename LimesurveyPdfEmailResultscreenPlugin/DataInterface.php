<?php
namespace PdfEmailResultscreen\Interfaces;

interface DataInterface
{  

    public static function getResponse($surveyid, $excludedquestions);  
   
}