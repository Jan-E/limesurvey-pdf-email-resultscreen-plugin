<?php
namespace PdfEmailResultscreen\Parser;

require_once __DIR__. '/../../vendor/autoload.php';
require_once __DIR__. '/ParserInterface.php';


use PdfEmailResultscreen\Interfaces as Interfaces;
use Twig_Loader_Filesystem;
use Twig_Environment;


class Parser implements Interfaces\ParserInterface {


    public static function parse($settings, $tmplname, $data, $tmplfolders)
    {

        $baseurl = "http://$_SERVER[HTTP_HOST]".$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/';

        $tmplbasefolder = $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/templates';

        $folders = [];

        $folders[] = $tmplbasefolder;

        foreach($tmplfolders as $f){

            if($f[0] !== '/'){

                $f = '/'.$f;
            }

            $folders[] = $tmplbasefolder.$f;

        }

        $loader = new Twig_Loader_Filesystem($folders);

        $envoptions = ['cache' => $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/compilationcache'];

        if($settings['debug'] === '1'){

            $envoptions['debug'] = true;

        }

        $twig = new Twig_Environment($loader, $envoptions);

        $template = $twig->loadTemplate($tmplname);

        $html = $template->render(['datanested' => $data['nested'], 'databykey' => $data['bykey'], 'nestedjson' => $data['nestedjson'], 'baseurl' => $baseurl ]);

        $html = self::foolExpressionManager($html);

        return $html;

    }


    public static function foolExpressionManager($string)
    {

        return str_replace(['{', '}'], ['{ ', ' }'], $string);

    }

}