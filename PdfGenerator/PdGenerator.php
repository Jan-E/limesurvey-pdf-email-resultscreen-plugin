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
          'template1surveyname' => array(
              'type' => 'text',
              'label' => "Survey Name and template name comma seperated: surveyname, templatename",
              'default' => '60',
          ),
          'template1variables' => array(
              'type' => 'text',
              'label' => "variablenames (the question code you entered for the question), seperate by comma",
              'default' => '60',
          ),
          'template2surveyname' => array(
              'type' => 'text',
              'label' => "Survey Name and template name comma seperated: surveyname, templatename",
              'default' => '60',
          ),
          'template2variables' => array(
              'type' => 'text',
              'label' => "variablenames (the question code you entered for the question), seperate by comma",
              'default' => '60',
          ),
          'template3surveyname' => array(
              'type' => 'text',
              'label' => "Survey Name and template name comma seperated: surveyname, templatename",
              'default' => '60',
          ),
          'template3variables' => array(
              'type' => 'text',
              'label' => "variablenames (the question code you entered for the question), seperate by comma",
              'default' => '60',
          ),
          'template4surveyname' => array(
              'type' => 'text',
              'label' => "Survey Name and template name comma seperated: surveyname, templatename",
              'default' => '60',
          ),
          'template4variables' => array(
              'type' => 'text',
              'label' => "variablenames (the question code you entered for the question), seperate by comma",
              'default' => '60',
          ),
          'template5surveyname' => array(
              'type' => 'text',
              'label' => "Survey Name and template name comma seperated: surveyname, templatename",
              'default' => '60',
          ),
          'template5variables' => array(
              'type' => 'text',
              'label' => "variablenames (the question code you entered for the question), seperate by comma",
              'default' => '60',
          ),
          'template6surveyname' => array(
              'type' => 'text',
              'label' => "Survey Name and template name comma seperated: surveyname, templatename",
              'default' => '60',
          ),
          'template6variables' => array(
              'type' => 'text',
              'label' => "variablenames (the question code you entered for the question), seperate by comma",
              'default' => '60',
          ),


           
        );


        public function init() {

          $this->subscribe('afterSurveyComplete');
          $this->subscribe('cron');
          $this->settings = $this->getPluginSettings(true);
     
        }


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

              unlink($_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_Download_Folder'].'/'.$file);

            }

          }

        }


        private function createCharts($templates, $data){

          $res = '';

          foreach ($templates as $k => $v){

            foreach($v as $key=>$val){

              $html = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/plugins/PdfGenerator/templates/'.$key);

              $html = html_entity_decode($html);

              foreach($val as  $var){

                $html = str_replace("##$var##", $data[$var], $html);

              }

              $res .= $html;

            }

          }

          return $res;

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

          //get surveyname not supplied by standard apifunctions
          $config = Yii::app()->getComponents(false);
          $prefix = $config['db']->tablePrefix;
           
          $q = Yii::app()->db->createCommand()
          ->select('surveyls_title')
          ->from($prefix.'surveys_languagesettings')
          ->where('surveyls_survey_id=:id', array(':id'=>$surveyId))
          ->queryRow();

          $surveyname = $q['surveyls_title'];
            
          //get surveyspecific templates

          $templates = [];

          $pushnext = false;
          $temp = [];
          $lastkey = null;

          foreach($settings as $k=>$v){

            if($pushnext === true){

              $temp[$lastkey] = array_map('trim', explode(',', $v));
              $templates[] = $temp;

              $temp = [];
              $lastkey = null;
              $pushnext = false;

            }else if(strrpos(trim(explode(',', $v)[0]), trim($surveyname)) !== false && trim($v) !== trim($surveyname)){

              $d = array_map('trim', explode(',', $v));

              $temp[$d[1]] = [];
              $lastkey = trim($d[1]);

              $pushnext = true;

            }else{

              $pushnext = false;
              $temp = [];
              $lastkey = null;

            }

          }  

          $microtime = (string)(number_format((microtime(true) * 1000),0, '.', ''));
          $pdfname = $microtime . '.pdf';
          $downloadpath = $settings['PdfGenerator_Download_Folder'];

          $c = $this->createCharts($templates, $response);

          try{
 
            $converter = new PhantomJS(['search_paths' => $_SERVER['DOCUMENT_ROOT'].$settings['PdfGenerator_phantomjs_Path']]);
           
            $input = new TempFile($c, 'html');

            $converter->convert($input, $_SERVER['DOCUMENT_ROOT'].'/download/'.$pdfname);

          }catch (Exception $e){

            CVarDumper::dump(['error' => $e]);
            $res = $event->getContent($this)
            ->addContent("An error occurred creating a pdf.");

          }

          $link = "http://$_SERVER[HTTP_HOST]/$downloadpath/$pdfname";
    
          $res = $event->getContent($this)
          ->addContent($c)
          ->addContent("<p>You can download your results <a href='$link'>here</a> </p>");

        }
       
    }
