<?php
namespace PdfEmailResultscreen\Interfaces;

interface ParserInterface
{  

    public static function parse($settings, $tmplname, $data, $tmplfolders);

    public static function foolExpressionManager($string);
   
}