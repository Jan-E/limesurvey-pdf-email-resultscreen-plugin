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

        public function __construct(PluginManager $manager=null, $id=null) {

            
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


        private function parseTemplates($workload, $response)
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

                        if(isset($v['parseasobject']) && trim($v['parseasobject']) === 'true'){
    
                            $variables = $this->parseAsObjects($v['variables'], $response);

                        }else{


                            $variables = $v['variables'];

                        }

                        foreach($variables as  $vark => $varv){

                            $vark = trim($vark);

                            $searcharr[] = "{!-$vark-!}";

                            if(trim($varv) === ''){

                                $rvar = "''";

                            }else{

                                $varv = trim($varv);

                                if($varv[0] === '{' && $varv[strlen($varv)-1] === '}'){

                                    $rvar = $varv;

                                }else if(strpos($varv, 'http') !== false){
                                    //links no quotes
                                    $rvar = $varv;

                                }else{

                                    $rvar = "'".$varv."'";

                                }
                                
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

        private function parseAsObjects($variables, $response)
        {

            $temp = [];

            foreach ($response as $k => $v){

                if(strpos($k, '_')!== false){

                    $kv = explode('_', $k);

                    if(array_key_exists($kv[0], $variables)){

                        if(!isset($temp[$kv[0]])){


                            $temp[$kv[0]] = [];

                            if (count($kv) > 2) {
                                //must be 3
                                if(!isset($temp[$kv[0]][$kv[1]])){

                                    $temp[$kv[0]][$kv[1]] = [];

                                    $temp[$kv[0]][$kv[1]][$kv[2]] = $v;

                                }else{

                                    $temp[$kv[0]][$kv[1]][$kv[2]] = $v;


                                }


                            }else{

                               $temp[$kv[0]][$kv[1]] = $v; 

                            }
                            

                        }else{

                            if (count($kv) > 2) {
                                //must be 3
                                if(!isset($temp[$kv[0]][$kv[1]])){

                                    $temp[$kv[0]][$kv[1]] = [];

                                    $temp[$kv[0]][$kv[1]][$kv[2]] = $v;

                                }else{

                                    $temp[$kv[0]][$kv[1]][$kv[2]] = $v;


                                }


                            }else{

                               $temp[$kv[0]][$kv[1]] = $v; 
                               
                            }

                        }

                    }

                }

            }

            foreach($temp as $k=>$v){

                $string = '';

                foreach($v as $key => $val){

                  
                    if(is_array($val)){

                        foreach($val as $ke => $va){

                            $parsedval = "{ $ke : '$va' }";

                        }

                    }else{

                        $parsedval = "'$val'";
                    }

                    $string .= " $key : $parsedval ,";

                }

                $string = rtrim($string, ",");


                $variables[$k] = "{ $string }";

            }

            return $variables;

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

            $length = 10;

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

        private function validateMarker($response)
        {

            $errors = [];

            $mandatory = ['showinresult', 'showinpdf', 'resulttemplate', 'pdftemplate', 'variables'];

            foreach ($response as $k => $v){

                $keys = [];

                if(strrpos(trim($k), 'pdfmarker') !== false){

                    $t = [];

                    $temp = array_map('trim', explode('|', $v));

                    foreach($temp as $val){

                        $p = array_map('trim', explode('=', $val));

                        $keys[$p[0]] = $p[1];


                    }

              

                    //mandatory keys
                    if(isset($keys['showinresult']) && isset($keys['showinpdf'])){

                        if($keys['showinresult'] === 'false' && $keys['showinpdf'] === 'false'){

                        //no problem

                        }else{

                            //check keys 
                            $check = $this->checkKeysHelper($keys, ['resulttemplate', 'pdftemplate', 'variables'], $k);

                            if($check !== false){

                                $errors[] = $check;

                            }

                        }

                    }else{

                        $count = 0;

                        if(isset($keys['showinresult'])){

                            $showres = '';

                        }else{

                            $count++;

                            $showres = "missing variable 'showinresult'";

                        }

                        if(isset($keys['showinpdf'])){

                            $and = '';
                            $showpdf = '';

                        }else{

                            $and = '';

                            if($count === 1){

                                $and = ' and ';
                            }

                            $showpdf = "missing variable 'showinpdf'";

                        }

                        $errors[] = ['error' => 'validation error', 'msg' => "markerquestion $k :  $showres$and$showpdf" ];

                    }

                }     

            }

            return $errors;

        }

        private function checkKeysHelper($input, $keys, $questionname)
        {

            $errortext = '';

            foreach($keys as $key){

                if(!isset($input[$key])){

                    $and = '';

                    if(strlen($errortext) > 0){

                        $and = ' and ';

                    }

                    $msg = "markerquestion $questionname : missing variable '$key'";
                    $errortext .= "$and$msg";

                }

            }

            if(strlen($errortext) > 0){

                return ['error' => 'validation error', 'msg' => $errortext ];

            }else{

                return false;

            }

        }

        private function createWorkload($response)
        {

            $workload = [];

            $css = [];
            $js = [];

            $baseurl = [];

            foreach ($response as $k => $v){

                if(strrpos(trim($k), 'pdfmarker') !== false){

                    $t = [];

                    $v = preg_replace('/\s+/', '', $v);
                    $v = preg_replace('~\x{00a0}~','',$v);

                    $temp = array_map('trim', explode('|', $v));

                    foreach($temp as $val){

                        $p = array_map('trim', explode('=', $val));

                        if($p[0] !== 'variables' && $p[0] !== 'externalcss' && $p[0] !== 'externaljs'  && $p[0] !== 'baseurl'){

                            $t[trim($p[0])] = trim($p[1]);

                        }else if($p[0] === 'variables'){

                            $vars = array_map('trim', explode(',', $p[1]));

                            $varray = [];

                            foreach($vars as $varv){

                                if (strlen($varv) > 0){

                                    if(isset($response[$varv])){

                                        $val = $response[$varv];

                                    }else{

                                        $val = '';

                                    }

                                    $varray[$varv] = $val;

                                }        
                            
                            }       

                            $t[$p[0]] = $varray;

                        }else if($p[0] === 'baseurl'){

                            $baseurl = ['name' => $p[1], 'url' => "http://$_SERVER[HTTP_HOST]"];

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

            if(count($baseurl) > 0){

                foreach($workload as $key=>$val){

                    if (isset($val['variables'])){

                        $workload[$key]['variables'][$baseurl['name']] = $baseurl['url'];

                    }

                }


            }

            return $workload;

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

            //validate inut

            $validationerrors = $this->validateMarker($response);

            if(count($validationerrors) === 0){

                $workload = $this->createWorkload($response);

             
                $microtime = (string)(number_format((microtime(true) * 1000),0, '.', ''));
                $pdfname = $microtime . '.pdf';
                $downloadpath = $settings['PdfGenerator_Download_Folder'];

                $c = $this->parseTemplates($workload, $response);

                $pdfall = '';

                foreach($c['pdf'] as $pv){

                    $pdfall .= $pv;

                }

                $resp = $event->getContent($this);

                if(strlen($pdfall) > 0){

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


            }else{

                $resp = $event->getContent($this);

                foreach($validationerrors as $error){

                    $errortitle = $error['error'];
                    $errortext = $error['msg'];
                    $tmpl = $error['template'];

                    $resp->addContent("<h4>$errortitle</h4><p>$errortext</p><p>template: $tmpl</p>");

                }

            }
           
        }
       
    }
