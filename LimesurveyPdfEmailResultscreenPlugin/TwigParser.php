<?php
require_once __DIR__. '/../../vendor/autoload.php';


class TwigParser {


    public function parse($settings, $tmplname, $data, $tmplfolders)
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

        $twig = new Twig_Environment($loader, array(
            'cache' => $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/compilationcache',
        ));

       /* $lexer = new Twig_Lexer($twig, array(
            'tag_comment'   => array('{#', '#}'),
            'tag_block'     => array('{%', '%}'),
            'tag_variable'  => array('{!-', '-!}'),
            'interpolation' => array('#{', '}'),
        ));

        $twig->setLexer($lexer);*/

        $template = $twig->loadTemplate($tmplname);

        $html = $template->render(['datanested' => $data['nested'], 'databykey' => $data['bykey'], 'nestedjson' => $data['nestedjson'], 'baseurl' => $baseurl ]);

        $html = $this->foolExpressionManager($html);

        return $html;

    }


    private function foolExpressionManager($string)
    {

        return str_replace(['{', '}'], ['{ ', ' }'], $string);

    }

}