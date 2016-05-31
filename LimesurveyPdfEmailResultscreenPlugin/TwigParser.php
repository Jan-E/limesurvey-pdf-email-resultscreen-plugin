<?php
require_once __DIR__. '/../../vendor/autoload.php';


class TwigParser {


    public function parse($settings, $tmplname, $data, $tmplfolders)
    {

        $baseurl = "http://$_SERVER[HTTP_HOST]".$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/';

        $tmplbasefolder = $baseurl.'plugins/LimesurveyPdfEmailResultscreenPlugin/templates';

        $folders = [];

        $folders[] = $tmplbasefolder;

        foreach($tmplfolders as $f){

            if($f[0] !== '/'){

                $f = '/'.$f;
            }

            $folders[] = $tmplbasefolder.$f;

        }

        $loader = new Twig_Loader_Filesystem($folders);

        $envoptions = ['cache' => $baseurl.'plugins/LimesurveyPdfEmailResultscreenPlugin/writable/compilationcache'];

        if($settings['debug'] === '1'){

            $envoptions['debug'] = true;

        }

        $twig = new Twig_Environment($loader, $envoptions);

        $template = $twig->loadTemplate($tmplname);

        $html = $template->render(['datanested' => $data['nested'], 'databykey' => $data['bykey'], 'nestedjson' => $data['nestedjson'], 'baseurl' => $baseurl ]);

        $html = $this->foolExpressionManager($html);

        return $html;

    }


    public function foolExpressionManager($string)
    {

        return str_replace(['{', '}'], ['{ ', ' }'], $string);

    }

}