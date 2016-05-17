<?php
require __DIR__. '/../../vendor/autoload.php';

use H2P\Converter\PhantomJS;
use H2P\TempFile;
//use SurveyDynamic;

    class PdfGenerator extends \ls\pluginmanager\PluginBase {

        protected $storage = 'DbStorage';
        static protected $description = 'pdf generator';
        static protected $name = 'pdfGenerator';

        protected $settings = array(
            'PdfGenerator_app_subfolder' => array(
                'type' => 'text',
                'label' => 'If your app is in a subfolder: for example: Your app is on http://www.example.com/limesurveyapp/, you can put it in here. Start with a slash, no trailing slash. Note: Do not name your subfolder phantomjs',
                'default' => '/',
            ),
            'PdfGenerator_phantomjs_Path' => array(
                'type' => 'text',
                'label' => 'Path to phantomjs. Only change when you installed phantomjs on your box, probably to /usr/local/bin/phantomjs. Do not prepend the previously mentioned app subfolder',
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
            'Load_Demo'  =>  array(
                'type'=>'checkbox',
                'label'=>'Check to load demo. deactivate and reactivate this plugin to load.',
            ),

                  
        );

        public function __construct(PluginManager $manager=null, $id=null) {
     
            parent::__construct($manager, $id);
            $this->subscribe('afterSurveyComplete');
            $this->subscribe('cron');
            $this->subscribe('beforeActivate');
            $this->settings = $this->getPluginSettings(true);
            
        }


        /*public function init() {

            $this->subscribe('afterSurveyComplete');
            $this->subscribe('cron');
            $this->settings = $this->getPluginSettings(true);
     
        }*/

        //implement methods

        public function beforeActivate()
        {

            //creates demo app

            $settings = [];

            foreach($this->settings as $k => $setting){

                $settings[$k] = $setting['current'];

            }

             Yii::app()->loadHelper('admin/import');

            $sFullFilePath = $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/plugins/PdfGenerator/demo/pdfgenerator_demo.lss';

            $aImportResults = importSurveyFile($sFullFilePath, true);



            if (isset($aImportResults['error'])){

                //return array('status' => 'Error: '.$aImportResults['error']);
                //CVarDumper::dump(['status' => 'Error: '.$aImportResults['error'] ]);

            }else{
                
                //return (int)$aImportResults['newsid'];
                //CVarDumper::dump(['newsid' => 'newsid: '.$aImportResults['newsid'] ]);
                
            }

            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/styles-public/custom')) {

                mkdir($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/styles-public/custom', 0777, true);
            }


            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/styles-public/custom/demo.css')) {

                copy($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/plugins/PdfGenerator/demo/css/demo.css', $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/styles-public/custom/demo.css');

            }

            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/scripts/custom')) {

                mkdir($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/scripts/custom', 0777, true);
            }


            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/scripts/custom/chartfactory.js')) {

                copy($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/plugins/PdfGenerator/demo/chartfactory/chartfactory.js', $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/scripts/custom/chartfactory.js');
                
            }  

        }


        public function cron()
        {

            $settings = [];

            foreach($this->settings as $k => $setting){

                $settings[$k] = $setting['current'];

            }

            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].$settings['PdfGenerator_Download_Folder'])) {

                mkdir($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].$settings['PdfGenerator_Download_Folder'], 0777, true);

            }

            $files = scandir($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].$settings['PdfGenerator_Download_Folder']);

            $nowtime = microtime(true) * 1000;

            foreach ($files as $file){

                if( $file[0] !== '.' && intval(explode('.', $file)[0]) < $nowtime - (1000 * intval($settings['PdfGenerator_Delete_Download_After']))  ){

                    //todo deletes too soon may be a problem with creating micro timestamp

                    unlink($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].$settings['PdfGenerator_Download_Folder'].'/'.$file);

                }

            }

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

            $pmanager   = $this->pluginManager->getAPI();
            $response   = $pmanager->getResponse($surveyId, $responseId);


            $validationerrors = $this->validateMarker($response);


            if(count($validationerrors) === 0){


                $workload = $this->createWorkload($response, $settings);

                require __DIR__. '/getAnswersAndQuestions.php';

                $aaq = new getAnswersAndQuestions();

                $data = $aaq->getResponse($surveyId);
             
                $microtime = (string)(number_format((microtime(true) * 1000),0, '.', ''));
                $pdfname = $microtime . '.pdf';
                $downloadpath = $settings['PdfGenerator_app_subfolder'].$settings['PdfGenerator_Download_Folder'];

                $c = $this->parseTemplates($workload, $data, $settings);

                $pdfall = '';

                foreach($c['pdf'] as $pv){

                    $pdfall .= $pv;

                }

                //$pdfall = '';

                $resp = $event->getContent($this);

                if(strlen($pdfall) > 0){

                    //first get config
                    
                    $configpdf = $this->getPdfConfig($response);

                    try{

                        //check if is relative path
                        if (strpos($settings['PdfGenerator_phantomjs_Path'], '/phantomjs') === 0){

                            $path = $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].$settings['PdfGenerator_phantomjs_Path'];

                        }else{

                            $path = $settings['PdfGenerator_phantomjs_Path'];

                        }
       
                        $params = array_merge(['search_paths' => $path ], $configpdf);
         
                        $converter = new PhantomJS($params);
                   
                        $input = new TempFile($pdfall, 'html');

                        $converter->convert($input, $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/download/'.$pdfname);

                        $link = "http://$_SERVER[HTTP_HOST]/$downloadpath/$pdfname";

                        $resp->addContent("<p>You can download your results <a href='$link'>here</a> </p>");


                    }catch (Exception $e){

                        if($settings['Debug'] !== null){

                            CVarDumper::dump(['error' => $e, 'message' => $e->getMessage()]);

                        }

                        $resp->addContent("An error occurred creating a pdf.");

                    }

                }

                if (count($c['parseerrors']) > 0){

                    if($settings['Debug'] !== null){

                        foreach($c['parseerrors'] as $err){
                                
                            $er = $err['error'];
                            $tra = $err['trace'];
                            $templ = $err['template'];

                            $resp->addContent("<h4>Parse-error</h4><p>Error: $er</p><p>Trace: $tra</p><p>Template: $templ</p>");

                        }

                    }

                }else{

                    $resp->addContent("An error occurred your pdf may not be correct.");

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


        private function parseTemplates($workload, $data, $settings)
        {

            $baseurl = "http://$_SERVER[HTTP_HOST]".$settings['PdfGenerator_app_subfolder'].'/';

            foreach ($workload as $k => $v){

                if(isset($v) && isset($v['showinresult']) && isset($v['createpdf']) && $v['showinresult'] === 'false' && $v['createpdf']=== 'false'){

                    continue;

                }else{

                    if(isset($v['parsenested']) && trim($v['parsenested']) === 'true'){

                        $variables = $data['nested'];

                    }else{

                        $variables = $data['bykey'];

                    }

                    $variables['baseurl'] = $baseurl;

                    if (isset($v['showinresult']) && $v['showinresult'] === 'true'){

                        $reshtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/plugins/PdfGenerator/templates/'.$v['resulttemplate']);

                        $reshtml = html_entity_decode($reshtml);

                        $reshtml = $this->replaceHelper($variables, $reshtml);

                        $res[] = $reshtml;


                    }

                    if (isset($v) && isset($v['createpdf']) && $v['createpdf'] === 'true'){

                        $pdfhtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_app_subfolder'].'/plugins/PdfGenerator/templates/'.$v['pdftemplate']);

                        $pdfhtml = html_entity_decode($pdfhtml);

                        $pdfhtml = $this->replaceHelper($variables, $pdfhtml);

                        $pdf[] = $pdfhtml;

                    }

                }

            }

            $parseerrors = [];

            return ['pdf'=> $pdf, 'res'=> $res, 'parseerrors' => $parseerrors];

        }

        private function foolExpressionManager($string)
        {


            return str_replace(['{', '}'], ['{ ', ' }'], $string);

        }

        private function replaceHelper($variables, $html)
        {

            $searcharr = [];

            $replarr = [];

            foreach($variables as  $vark => $varv){

                
           
                $varv = $varv;

                $vark = trim($vark);

                $searcharr[] = "{!-$vark-!}";

                if(!is_array($varv) && trim($varv) === ''){

                    $rvar = "''";

                }else{

                    

                    if(!is_array($varv) && strpos($varv, 'http') !== false){
                        //links no quotes
                        $rvar = trim($varv, '"');
                        $rvar = trim($varv, "'");

                    }else if(is_array($varv)){

                        $rvar = json_encode($varv);

                    }else{

                        $rvar = "'".$varv."'";

                    }
                    
                }

                $replarr[] = $rvar;

            }

            $replarr = array_unique($replarr);

            $replaced = str_replace($searcharr, $replarr, $html);

            $replaced = $this->foolExpressionManager($replaced);
            
            return $replaced;

        }

        private function getPdfConfig($response){

            $config = [];

            foreach ($response as $k => $v){

                if(strrpos(trim($k), 'pdfconfig') !== false){

                    $v= preg_replace('/\s+/', '', $v);
                    $v = preg_replace('~\x{00a0}~','',$v);

                    $varstemp = array_map('trim', explode('|', $v));

                    $vars = [];

                    foreach($varstemp as $v){

                        $t = explode('=', $v);
                        $vars[$t[0]] = $t[1];

                    }

                    if(isset($vars['headerheight'])){

                        if(!isset($config['header'])){

                            $config['header'] = [];
                            $config['header']['height'] = $vars['headerheight'];

                        }else{

                            $config['header']['height'] = $vars['headerheight'];

                        }

                    }

                    if(isset($vars['headercontent'])){

                        if(!isset($config['header'])){

                            $config['header'] = [];
                            $config['header']['content'] = $vars['headercontent'];

                        }else{

                            $config['header']['content'] = $vars['headercontent'];

                        }

                    }

                    if(isset($vars['footerheight'])){

                        if(!isset($config['footer'])){

                            $config['footer'] = [];
                            $config['footer']['height'] = $vars['footerheight'];

                        }else{

                            $config['footer']['height'] = $vars['footerheight'];

                        }

                    }

                    if(isset($vars['footercontent'])){

                        if(!isset($config['footer'])){

                            $config['footer'] = [];
                            $config['footer']['content'] = $vars['footercontent'];

                        }else{

                            $config['footer']['content'] = $vars['footercontent'];

                        }

                    }

                     foreach($vars as $k=>$v){


                        if($k !== 'headerheight' && $k !== 'headercontent' && $k !== 'footerheight' && $k !== 'footercontent' &&
                             $k !== 'headercontenttag' && $k !== 'headercontentstyle' && $k !== 'footercontenttag' && $k !== 'footercontentstyle'){

                            $config[$k] = $v;

                        }

                    }

                    if(isset($vars['headercontent']) && isset($vars['headercontenttag'])){

                        $tag = $vars['headercontenttag'];

                        if(isset($vars['headercontentstyle'])){

                            $style = $vars['headercontentstyle'];
                            

                            $config['header']['content'] = "<$tag style='$style'>". $vars['headercontent']."</$tag>";

                        }else{

                            $config['header']['content']= "<$tag>". $vars['headercontent']."</$tag>";

                        }


                    }

                    if(isset($vars['footercontent']) && isset($vars['footercontenttag'])){

                        $tag = $vars['footercontenttag'];

                        if(isset($vars['footercontentstyle'])){

                            $style = $vars['footercontentstyle'];
                            

                            $config['footer']['content'] = "<$tag style='$style'>". $vars['footercontent']."</$tag>";

                        }else{

                            $config['footer']['content'] = "<$tag>". $vars['footercontent']."</$tag>";

                        }


                    }

                    break;

                }
       

            }

            return $config;

        }


        private function createWorkload($response, $settings)
        {

            $workload = [];

            $css = [];
            $js = [];

            $baseurl = [];

            foreach ($response as $k => $v){

                if(strrpos(trim($k), 'pdfmarker') !== false){

                    $t = [];

                    $v= preg_replace('/\s+/', '', $v);
                    $v = preg_replace('~\x{00a0}~','',$v);

                    $v = stripslashes($v);

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
                       
                            continue;

                        } 

                    }

                    $workload[] = $t;

                }

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






        private function validateMarker($response)
        {

            $errors = [];

            $mandatory = ['showinresult', 'createpdf', 'resulttemplate', 'pdftemplate', 'variables'];

            foreach ($response as $k => $val){

                if(isset($val['response'])){

                    $v = $val['response'];

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

                }else{

                    continue;

                }     

            }

            return $errors;

        }
       
    }
