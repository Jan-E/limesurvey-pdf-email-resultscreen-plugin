<?php
require $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php';

use H2P\Converter\PhantomJS;
use H2P\TempFile;

    class PdfGenerator extends \ls\pluginmanager\PluginBase {

        protected $storage = 'DbStorage';
        static protected $description = 'pdf generator';
        static protected $name = 'pdfGenerator';

        protected $settings = array(
            'PdfGenerator_phantomjs_Path' => array(
                'type' => 'text',
                'label' => 'Path to phantomjs only change when you installed phantomjs on your box, probably to /usr/local/bin/phantomjs',
                'default' => '/phantomjs/bin/phantomjs',
            ),
            'PdfGenerator_Download_Folder' => array(
                'type' => 'text',
                'label' => "Download folder (only change when you don't want it to be in your root/download folder)",
                'default' => '/download',
            ), 
            'PdfGenerator_Delete_Download_After' => array(
                'type' => 'text',
                'label' => 'Delete generated pdf after amount of minutes',
                'default' => '60',
            ),
            'Debug'  =>  array(
                'type'=>'checkbox',
                'label'=>'Check to enable debug mode',
            ),
                  
        );

        public function __construct(PluginManager $manager, $id) {

            parent::__construct($manager, $id);
            $this->subscribe('afterSurveyComplete');
            $this->subscribe('cron');
            $this->settings = $this->getPluginSettings(true);
            
        }


        /*public function init() {

            $this->subscribe('afterSurveyComplete');
            $this->subscribe('cron');
            $this->settings = $this->getPluginSettings(true);
     
        }*/

        //implement methods

        public function cron()
        {

            $settings = [];

            foreach($this->settings as $k => $setting){

                $settings[$k] = $setting['current'];

            }

            $files = scandir($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_Download_Folder']);

            $nowtime = microtime(true) * 1000;

            foreach ($files as $file){

                if( $file[0] !== '.' && intval(explode('.', $file)[0]) < $nowtime - (1000 * intval($settings['PdfGenerator_Delete_Download_After']))  ){

                    //todo deletes too soon may be a problem with creating micro timestamp

                    unlink($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_Download_Folder'].'/'.$file);

                }

            }

        }


        private function createCharts($workload)
        {

            $res = [];

            $pdf = [];

            $resultpage = [];

            $toprepend = '';

            $parseerrors = [];


            foreach ($workload as $k => $v){

            
                if(isset($v['externals'])){

                    $burl = $link = "http://$_SERVER[HTTP_HOST]/";

                    //create externals html
                    $css = '';
                    $js = '';

                    foreach($v['externals']['css'] as $href){

                        $base = '';

                        if(strpos($href, 'http') === false){

                            $base = $burl;

                        }

                        $css .= "<link rel='stylesheet' type='text/css' href='$base$href'>";

                    }

                    foreach($v['externals']['js'] as $href){

                        $base = '';

                        if(strpos($href, 'http') === false){

                            $base = $burl;
                  
                        }

                        $js .= "<script src='$base$href'></script>";

                    }

                    $toprepend = "<div>$css$js<div>";


                }else if($v['showinresult'] === 'false' && $v['showinpdf'] === 'false'){

                    continue;

                }else{

                    if ($v['showinresult'] === 'true'){

                        $reshtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/plugins/PdfGenerator/templates/'.$v['resulttemplate']);

                        $reshtml = html_entity_decode($reshtml);

                        $searcharr = [];
                        $replarr = [];



                        foreach($v['variables'] as  $vark => $varv){

                            $vark = trim($vark);

                            $searcharr[] = "{!-$vark-!}";

                            if(trim($varv) === ''){

                                $rvar = "''";

                            }else{

                                $rvar = trim($varv);

                            }

                            $replarr[] = $rvar;

                        }


                        $reshtml = str_replace($searcharr, $replarr, $reshtml);

                        $perr = $this->parseErrorHelper($reshtml, $v['pdftemplate']);

                        if(count($perr) > 0){

                            $parseerrors[] = $perr;

                        }

                        $res[] = $reshtml;

                    }


                    if ($v['showinpdf'] === 'true'){

                        $pdfhtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/plugins/PdfGenerator/templates/'.$v['pdftemplate']);

                        $pdfhtml = html_entity_decode($pdfhtml);

                        $searcharr = [];
                        $replarr = [];

                        foreach($v['variables'] as  $vark => $varv){

                            $vark = trim($vark);

                            $searcharr[] = "{!-$vark-!}";

                            if(trim($varv) === ''){

                                $rvar = "''";

                            }else{

                                $rvar = trim($varv);

                            }

                            $replarr[] = $rvar;

                        }

                        $pdfhtml = str_replace($searcharr, $replarr, $pdfhtml);

                        $perr = $this->parseErrorHelper($pdfhtml, $v['pdftemplate']);

                        if(count($perr) > 0){

                            $parseerrors[] =  $perr;

                        }
                        
                        $pdf[] = $pdfhtml;

                    }

                }
    
            }

         

            if(count($pdf) > 0){

                if(strlen($toprepend) > 0){

                    array_unshift($pdf, $toprepend);

                }

            }


            if(count($res) > 0){

                if(strlen($toprepend) > 0){

                    array_unshift($res, $toprepend);
              
                }

            }

            return ['pdf'=> $pdf, 'res'=> $res, 'parseerrors' => $parseerrors];

        }

        private function parseErrorHelper($html, $template)
        {

            $err = [];

            $start = strpos($html, '{!-');
            $end = strpos($html, '-!}');

            $trace = '';

            if($start !== false){

                $trace = $this->createTraceHelper($html, $start);

                if($end === false){         

                    $err = ['error' => 'found opening tag for placeholder without closing tag', 'trace' => $trace, 'template' => $template];

                }else{

                    $err = ['error' => 'found tags for a variable which was not passed', 'trace' => $trace, 'template' => $template];
                }


            }else if($end !== false){

                $trace = $this->createTraceHelper($html, $end);

                $err = ['error' => 'found closing tag for placeholder without start tag', 'trace' => $trace, 'template' => $template];  
                            
            }

            return $err;

        }

        private function createTraceHelper($html, $pos)
        {

            $length = 200;

            if($pos > $length){

                $tracestart = $pos - $length;
            
            }else{

                $tracestart = 0;

            }

            if(strlen($html) > $pos + $length){

                $traceend = $pos + $length;

            }else{

                $traceend = strlen($html);

            }

            return '....'.substr($html, $tracestart, $traceend - $tracestart).'....';

        }

        public function afterSurveyComplete()
        {

            //set settings
            $settings = [];

            foreach($this->settings as $k => $setting){

                $settings[$k] = $setting['current'];

            }

            //scandir and cleanup files

            $this->cron();

            $event      = $this->getEvent();
            $surveyId   = $event->get('surveyId');
            $responseId = $event->get('responseId');
            $response   = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);

            if($settings['Debug'] !== null){

                CVarDumper::dump($response);

            }

            $workload = [];

            $css = [];
            $js = [];

            foreach ($response as $k => $v){

                if(strrpos(trim($k), 'pdfmarker') !== false){

                    $t = [];

                    $temp = array_map('trim', explode('|', $v));

                    foreach($temp as $val){

                        $p = $temp = array_map('trim', explode('=', $val));

                        if($p[0] !== 'variables' && $p[0] !== 'externalcss' && $p[0] !== 'externaljs'){

                            $t[$p[0]] = $p[1];

                        }else if($p[0] === 'variables'){

                            $vars = array_map('trim', explode(',', $p[1]));

                            $varray = [];

                            foreach($vars as $varv){

                                if (strlen($varv) > 0){

                                    $varray[$varv] = $response[$varv];

                                }        
                            
                            }       

                            $t[$p[0]] = $varray;

                        }else{
                        //externaljs and external css

                            if($p[0] === 'externalcss'){

                                $css = array_merge($css, array_map('trim', explode(',', $p[1])) );

                            }else{
               
                                $js =  array_merge($js, array_map('trim', explode(',', $p[1])) );

                            }

                        } 

                    }

                    $workload[] = $t;

                }

            }

            $js = array_unique($js);
            $css = array_unique($css);

            if(count($js) !== 0 || count($css) !== 0){

                array_unshift($workload, ['externals' => ['js' => $js, 'css' => $css]]  );
         
            }

         
            $microtime = (string)(number_format((microtime(true) * 1000),0, '.', ''));
            $pdfname = $microtime . '.pdf';
            $downloadpath = $settings['PdfGenerator_Download_Folder'];

            $c = $this->createCharts($workload);

            $pdfall = '';

            foreach($c['pdf'] as $pv){

                $pdfall .= $pv;

            }

            $resp = $event->getContent($this);

            try{
 
                $converter = new PhantomJS(['search_paths' => $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_phantomjs_Path']]);
           
                $input = new TempFile($pdfall, 'html');

                $converter->convert($input, $_SERVER['DOCUMENT_ROOT'].'/download/'.$pdfname);

                $link = "http://$_SERVER[HTTP_HOST]/$downloadpath/$pdfname";

                $resp->addContent("<p>You can download your results <a href='$link'>here</a> </p>");


            }catch (Exception $e){

                if($settings['Debug'] !== null){

                    CVarDumper::dump(['error' => $e, 'message' => $e->getMessage()]);

                }

                //$res = $event->getContent($this)
                $resp->addContent("An error occurred creating a pdf.");

            }

            if($settings['Debug'] !== null){

                foreach($c['parseerrors'] as $err){
                        
                    $er = $err['error'];
                    $tra = $err['trace'];
                    $templ = $err['template'];

                    $resp->addContent("<h4>Parse-error</h4><p>Error: $er</p><p>Trace: $tra</p><p>Template: $templ</p>");

                }

            }


            foreach($c['res'] as $attach){

                $resp->addContent($attach);

            }
           
        }
       
    }
