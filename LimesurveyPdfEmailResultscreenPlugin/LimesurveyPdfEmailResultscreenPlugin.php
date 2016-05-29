<?php
require_once __DIR__. '/../../vendor/autoload.php';
require_once 'LimesurveyPdfEmailResultscreenPluginInterface.php';
require_once 'TwigParser.php';

use H2P\Converter\PhantomJS;
use H2P\TempFile;


    class LimesurveyPdfEmailResultscreenPlugin extends \ls\pluginmanager\PluginBase implements  LimesurveyPdfEmailResultscreenPluginInterface{

        protected $storage = 'DbStorage';
        static protected $description = 'Limesurvey-Pdf-Email-Resultscreen-Plugin';
        static protected $name = 'LimesurveyPdfEmailResultscreenPlugin';

        protected $settings = array(
            
            'LimesurveyPdfEmailResultscreenPlugin_app_subfolder' => array(
                'type' => 'text',
                'label' => 'If your app is in a subfolder: for example: Your app is on http://www.example.com/limesurveyapp/, you can put it in here. Start with a slash, no trailing slash. Note: Do not name your subfolder phantomjs',
                'default' => '/',
            ),
           
            'LimesurveyPdfEmailResultscreenPlugin_phantomjs_Path' => array(
                'type' => 'text',
                'label' => 'Path to phantomjs. Only change when you installed phantomjs on your box, probably to /usr/local/bin/phantomjs. Do not prepend the previously mentioned app subfolder',
                'default' => '/phantomjs/bin/phantomjs',
            ),
            
            'LimesurveyPdfEmailResultscreenPlugin_Delete_Download_After' => array(
                'type' => 'text',
                'label' => 'Delete generated pdf after amount of minutes',
                'default' => '60',
            ),
            'Load_Demo'  =>  array(
                'type'=>'checkbox',
                'label'=>'Check to load demo. deactivate and reactivate this plugin to load.',
            ),

                  
        );

        private $parsedsettings = [];

        public function __construct(PluginManager $manager=null, $id=null) {
     
            parent::__construct($manager, $id);
            $this->subscribe('afterSurveyComplete');
            //$this->subscribe('cron');
            $this->subscribe('beforeActivate');
            $this->subscribe('beforeSurveySettings');
            $this->subscribe('newSurveySettings');
            $this->settings = $this->getPluginSettings(true);

            foreach($this->settings as $k => $setting){

                $this->parsedsettings[$k] = $setting['current'];

            }


            
        }


        /*public function init() {

            $this->subscribe('afterSurveyComplete');
            $this->subscribe('cron');
            $this->settings = $this->getPluginSettings(true);
     
        }*/

        //implement methods

        /**
         * This event is fired by the administration panel to gather extra settings
         * available for a survey.
         * The plugin should return setting meta data.
         * @param PluginEvent $event
         * JUST AN EXAMPLE
         */
       /* public function beforeSurveySettings()
        {
            $event = $this->getEvent();
            $event->set("surveysettings.{$this->id}", array(
                'name' => get_class($this),
                'settings' => array(
                    'message' => array(
                        'type' => 'string',
                        'label' => 'Message to show to users:',
                        'current' => $this->get('message', 'Survey', $event->get('survey'))
                    )
                )
             ));
        }*/

        public function beforeSurveySettings()
        {
            $event = $this->getEvent();

            $downloadpdftext  = $this->get('downloadpdftext', 'Survey', $event->get('survey'));

            if (!isset( $downloadpdftext ) || $downloadpdftext === '' ){

                $downloadpdftext = 'You can download your pdf {!-here-!}';

            }

            $emailtemplate  = $this->get('emailtemplate', 'Survey', $event->get('survey'));

            if (!isset( $emtmpl ) || $emtmpl === '' ){

                $emailtemplate = 'standardmessage.html';

            }

            $fromem  = $this->get('fromemail', 'Survey', $event->get('survey'));

            if (!isset( $fromem ) || $fromem === '' ){

                $fromem = 'noreply@example.com';

            }

            $fromemname  = $this->get('fromemailname', 'Survey', $event->get('survey'));

            if (!isset( $fromemname ) || $fromemname === '' ){

                $fromemname = 'Survey admin';

            }

            $attname  = $this->get('attachmentname', 'Survey', $event->get('survey'));

            if (!isset( $attname ) || $attname === '' ){

                $attname = 'YourResults.pdf';

            }

            $emsubj  = $this->get('emailsubject', 'Survey', $event->get('survey'));

            if (!isset( $emsubj ) || $emsubj === '' ){

                $emsubj = 'Your survey';

            }

            
            $emsucmsg  = $this->get('emailsuccessmessage', 'Survey', $event->get('survey'));

            if (!isset( $emsucmsg ) || $emsucmsg === '' ){

                $emsucmsg = 'Your email has been sent';

            }

            $emerrmsg  = $this->get('emailerrormessage', 'Survey', $event->get('survey'));

            if (!isset( $emerrmsg ) || $emerrmsg === '' ){

                $emerrmsg = 'Oops.. an error occurred while sending your email';

            }

            $debugemail  = $this->get('debugemail', 'Survey', $event->get('survey'));

            if (!isset( $debugemail ) || $debugemail === '' ){

                $debugemail = 'noreply@example.com';

            }

            $downloadfolder  = $this->get('PdfGenerator_Download_Folder', 'Survey', $event->get('survey'));

            if (!isset( $downloadfolder ) || $downloadfolder === '' ){

                $downloadfolder = '/download';

            }

            $pdftemplate  = $this->get('pdftemplate', 'Survey', $event->get('survey'));

            if (!isset( $pdftemplate ) || $pdftemplate === '' ){

                $pdftemplate = 'template.html';

            }

            $resulttemplate  = $this->get('resulttemplate', 'Survey', $event->get('survey'));

            if (!isset( $resulttemplate ) || $resulttemplate === '' ){

                $resulttemplate = 'template.html';

            }

            $pdfconfig  = $this->get('pdfconfig', 'Survey', $event->get('survey'));

            if (!isset( $pdfconfig ) || $pdfconfig === '' ){

                $pdfconfig = 'border=0cm | orientation=portrait';

            }

            $pdfheader  = $this->get('pdfheader', 'Survey', $event->get('survey'));

            if (!isset( $pdfheader ) || $pdfheader === '' ){

                $pdfheader = '1';

            }

            $headercontent  = $this->get('headercontent', 'Survey', $event->get('survey'));

            if (!isset( $headercontent ) || $headercontent === '' ){

                $headercontent = 'Your survey';

            }

            $headercontenttag  = $this->get('headercontenttag', 'Survey', $event->get('survey'));

            if (!isset( $headercontenttag ) || $headercontenttag === '' ){

                $headercontenttag = 'p';

            }

            $headercontentstyle  = $this->get('headercontentstyle', 'Survey', $event->get('survey'));

            if (!isset( $headercontentstyle ) || $headercontentstyle === '' ){

                $headercontent = '';

            }

            $headerheight  = $this->get('headerheight', 'Survey', $event->get('survey'));

            if (!isset( $headerheight ) || $headerheight === '' ){

                $headerheight = '1cm';

            }


            $pdffooter  = $this->get('pdffooter', 'Survey', $event->get('survey'));

            if (!isset( $pdffooter ) || $pdffooter === '' ){

                $pdffooter = '1';

            }

            $footercontent  = $this->get('footercontent', 'Survey', $event->get('survey'));

            if (!isset( $footercontent ) || $footercontent === '' ){

                $footercontent = 'Your survey';

            }

            $footercontenttag  = $this->get('footercontenttag', 'Survey', $event->get('survey'));

            if (!isset( $footercontenttag ) || $footercontenttag === '' ){

                $footercontenttag = 'p';

            }

            $footercontentstyle  = $this->get('footercontentstyle', 'Survey', $event->get('survey'));

            if (!isset( $footercontentstyle ) || $footercontentstyle === '' ){

                $footercontentstyle = '';

            }

            $footerheight  = $this->get('footerheight', 'Survey', $event->get('survey'));

            if (!isset( $footerheight ) || $footerheight === '' ){

                $footerheight = '1cm';

            }


           

            $event->set("surveysettings.{$this->id}", array(
                'name' => get_class($this),
                'settings' => array(

                    'dummyplugin' => array(
                        'type' => 'checkbox',
                        'label' => 'PLUGIN SETTINGS',
                        'default' => false,
                        'readOnly' => true,
                        'help'=> '<h2>Plugin setttings</h2>
                                    <p>Below are pdf, resultscreen and email settings.</p>
                                    <p>First some settings regarding the whole plugin can be set.</p>',
                    ),
                    'debug' => array(
                        'type' => 'checkbox',
                        'label' => "Debug",
                        'current' => $this->get('debug', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>This will dump your variables on the resultscreen and sent emails by default to your 'Debug email' adress (see below)</p>",
                    ),
                    'parsenested' => array(
                        'type' => 'checkbox',
                        'label' => "parse nested",
                        'current' => $this->get('parsenested', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>This will parse nested variables, so subquestions will become nested objects within the containing question (recommended)</p>",
                    ),
                    'dummypdf' => array(
                        'type' => 'checkbox',
                        'label' => 'PDF SETTINGS',
                        'default' => false,
                        'readOnly' => true,
                        'help'=> '<h3>Pdf setttings</h3>
                                    <p>Below are the pdf settings.</p>',
                                    
                    ),
                    'createpdf' => array(
                        'type' => 'checkbox',
                        'label' => "Create pdf",
                        'current' => $this->get('createpdf', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>Create pdf: This will create a pdf</p>",
                    ),
                    'showdownloadpdftext' => array(
                        'type' => 'checkbox',
                        'label' => "Show download pdf text",
                        'current' => $this->get('showdownloadpdftext', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>Show download pdf text: This will show a download pdf text in the resultscreen you provide below</p>",
                    ),
                    'downloadpdftext' => array(
                        'type' => 'text',
                        'label' => "Download pdf text",
                        'current' => $this->get('downloadpdftext', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>Create pdf: This is the download text you provide</p>
                                <p>Put the part of the text you want to be the clickable link between [[ and ]]. The whole text can be between html tags.</p>",
                    ),

                    'pdftemplate' => array(
                        'type' => 'string',
                        'label' => "Pdf template",
                        'current' => $pdftemplate,
                        'default' => 'template.html',
                        'help'=> "<p>Templates should be in the folder : plugins/PdfGenerator/templates</p>
                                 <p>These templates can also be placed in a subfolder(mysubfolder/mypdftemplate.html).</p>",
                    ),
                    'PdfGenerator_Download_Folder' => array(
                        'type' => 'string',
                        'label' => "Download folder",
                        'current' => $downloadfolder,
                        'default' => '/download',
                        'help'=> "<p>Download folder (only change when you don't want it to be in your root/download folder).</p>
                                 <p>Will be prepended with your app's subfolder (see your plugin manager)</p>",
                    ), 
                    'pdfconfig' => array(
                        'type' => 'text',
                        'label' => "Pdf configuration",
                        'current' => $pdfconfig,
                        'default' => '1cm',
                        'help'=> "<p>Pdf config</p>
                                 <p>name=value, delimited by a  '|'. So border=1cm | orientation=landscape</p>",
                    ),

                    'dummypdfheaderfooter' => array(
                        'type' => 'checkbox',
                        'label' => 'PDF HEADERS/FOOTERS',
                        'default' => false,
                        'readOnly' => true,
                        'help'=> '<h3>Pdf headers/footers</h3>
                                    <p>Below you can configure your pdf headers and footers.</p>
                                    <p>You can insert {{pageNum}} and  {{totalPages}}, for instance: {{pageNum}} / {{totalPages}}</p>
                                    <p><b>IMPORTANT</b>{{pageNum}} and {{totalPages}}, can not have spaces in it!</p>',
                                    
                    ),

                    'pdfheader' => array(
                        'type' => 'checkbox',
                        'label' => "Pdf header",
                        'current' => $pdfheader,
                        'default' => '1',
                        'help'=> "<p>Pdf header</p>
                                 <p>Check to create header</p>",
                    ),

                    'headercontent' => array(
                        'type' => 'string',
                        'label' => "Pdf header content",
                        'current' => $headercontent,
                        'default' => 'Your survey',
                        'help'=> "<p>Pdf header content</p>
                                 <p>The text you want in your header</p>",
                    ), 
                     
                    'headercontenttag' => array(
                        'type' => 'string',
                        'label' => "Pdf header content tag",
                        'current' => $headercontenttag,
                        'default' => 'p',
                        'help'=> "<p>Pdf header content tag</p>
                                 <p>The html tag your headercontent will be wrapped in.</p>",
                    ),

                    'headercontentstyle' => array(
                        'type' => 'text',
                        'label' => "Pdf header content style",
                        'current' => $headercontentstyle,
                        'default' => 'p',
                        'help'=> "<p>Pdf header content style</p>
                                 <p>The content will be wrapped in the previously mentioned tag and this style will be appended inline.</p>
                                 <p>This is because adding a class and apply external css does not work.</p>",
                    ),
                    'headerheight' => array(
                        'type' => 'string',
                        'label' => "Pdf header height",
                        'current' => $headerheight,
                        'default' => 'p',
                        'help'=> "<p>Pdf header height</p>
                                 <p>The header height: for example: 1cm or 5mm</p>",
                    ), 

                    'pdffooter' => array(
                        'type' => 'checkbox',
                        'label' => "Pdf footer",
                        'current' => $pdffooter,
                        'default' => '1',
                        'help'=> "<p>Pdf footer</p>
                                 <p>Check to create header</p>",
                    ),

                    'footercontent' => array(
                        'type' => 'string',
                        'label' => "Pdf footer content",
                        'current' => $footercontent,
                        'default' => '{ { pageNum } } / { { totalPages } }',
                        'help'=> "<p>Pdf footer content</p>
                                 <p>The text you want in your footer</p>",
                    ), 
                     
                    'footercontenttag' => array(
                        'type' => 'string',
                        'label' => "Pdf header content tag",
                        'current' => $footercontenttag,
                        'default' => 'p',
                        'help'=> "<p>Pdf footer content tag</p>
                                 <p>The html tag your footercontent will be wrapped in.</p>",
                    ),

                    'footercontentstyle' => array(
                        'type' => 'text',
                        'label' => "Pdf footer content style",
                        'current' => $footercontentstyle,
                        'default' => 'p',
                        'help'=> "<p>Pdf footer content style</p>
                                 <p>The content will be wrapped in the previously mentioned tag and this style will be appended inline.</p>
                                 <p>This is because adding a class and apply external css does not work.</p>",
                    ),
                    'footerheight' => array(
                        'type' => 'string',
                        'label' => "Pdf footer height",
                        'current' => $footerheight,
                        'default' => 'p',
                        'help'=> "<p>Pdf footer height</p>
                                 <p>The footer height: for example: 1cm or 5mm</p>",
                    ), 

                    'dummyresultscreen' => array(
                        'type' => 'checkbox',
                        'label' => 'RESULTSCREEN SETTINGS',
                        'default' => false,
                        'readOnly' => true,
                        'help'=> '<h3>Resultscreen setttings</h3>
                                    <p>Below are the resultscreen settings.</p>',
                                    
                    ),
                    'showinresult' => array(
                        'type' => 'checkbox',
                        'label' => "Show in result",
                        'current' => $this->get('showinresult', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>Show in result: This will be added to the resultpage when checked</p>
                                <p>You need to supply a template for this below.</p>",
                    ),

                    'resulttemplate' => array(
                        'type' => 'string',
                        'label' => "Result template",
                        'current' => $resulttemplate,
                        'default' => 'template.html',
                        'help'=> "<p>Templates should be in the folder : plugins/PdfGenerator/templates</p>
                                 <p>These templates can also be placed in a subfolder(mysubfolder/mypdftemplate.html).</p>",
                    ), 
                
                    'dummyemail' => array(
                        'type' => 'checkbox',
                        'label' => 'EMAIL SETTINGS',
                        'default' => false,
                        'readOnly' => true,
                        'help'=> '<h3>Email setttings</h3>
                                    <p>Below are the email settings.</p>
                                    <p><b>The email server credentials are taken from you global settings.</b></p>',
                    ),
                    'debugemail' => array(
                        'type' => 'email',
                        'label' => 'Debug email',
                        'current' => $debugemail,
                        'default' => 'noreply@example.com',
                        'help'=> '<p>Debug email. If you checked debug, the email will always be sent to this email</p>',
                    ),
                    'sendemail' => array(
                        'type' => 'checkbox',
                        'label' => "Send email",
                        'current' => $this->get('sendemail', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>Send email: Check to send and email (this does <b>NOT</b> prevent emails send by limesurveys' native email service</p>",
                    ),
                    'fromemail' => array(
                        'type' => 'email',
                        'label' => "from email",
                        'current' => $fromem,
                        'default' => 'noreply@example.com',
                        'help'=> "<p>from email: The email you are sending from</p>",
                    ),
                    'fromemailname' => array(
                        'type' => 'string',
                        'label' => "from email name",
                        'current' => $fromemname,
                        'default' => 'Survey admin',
                        'help'=> "<p>from email name: The alias name of the email addres you are mailing from</p>",
                    ),
                    'attachpdf' => array(
                        'type' => 'checkbox',
                        'label' => "Attach pdf",
                        'current' => $this->get('attachpdf', 'Survey', $event->get('survey')),
                        'default' => false,
                        'help'=> "<p>Attach pdf: Check to attach the generated pdf file to the email (this does <b>NOT</b> prevent emails send by limesurveys' native email service</p>",
                    ),
                    'attachmentname' => array(
                        'type' => 'string',
                        'label' => "attachment name",
                        'current' => $attname,
                        'default' => 'YourResults.pdf',
                        'help'=> "<p>attachmentname: The name of the pdf to attach. <br>If you need a dynamic name create a 'attachmentname=mypdfname.pdf' in your 'emailmarker' markerquestion</p>",
                    ),
                    'emailsubject' => array(
                        'type' => 'string',
                        'label' => "Email subject",
                        'current' => $emsubj,
                        'default' => 'Your survey',
                        'help'=> '<p>emailsubject: Subject text of the email</p>'
                    ),
                    'emailtemplate' => array(
                        'type' => 'string',
                        'label' => "Email template",
                        'current' => $emailtemplate,
                        'default' => 'standardmessage.html',
                        'help'=> "<p>Email template: Name of the email template in the PdfGenerator/emailtemplates folder (or subfoldername/emailtemplate.html. <br> Variables should be between {!- and -!}. Pass variables in your markerquestion named 'emailmarker' as 'variables=q1,q2'</p>",
                    ),
                    'emailtemplatetype' => array(
                        'type' => 'select',
                        'label' => "Email template type",
                        'options'=>array(
                            'text/html'=>'text/html',
                            'text/plain'=>'text/plain',
                        ),
                        'current' => $emailtemplate,
                        'default' => 'html',
                        'help'=> '<p>Email template type: Type of email template, html or plain text. Plain/text not tested yet.</p><br>',
                    ),
                    'emailsuccessmessage' => array(
                        'type' => 'string',
                        'label' => "Email success message",
                        'current' => $emsucmsg,
                        'default' => 'Your email has been sent',
                        'help'=> '<p>Email success message: Shown at the result page (only when "Send email" is checked)</p>',
                    ),
                    'emailerrormessage' => array(
                        'type' => 'string',
                        'label' => "Email error message",
                        'current' => $emerrmsg,
                        'default' => 'Oops.. an error occurred while sending your email',
                        'help'=> '<p>Email error message: Shown at the result page (only when "Send email" is checked)</p>',
                    ),

                )

            ));

        }
    
        public function newSurveySettings()
        { 
            //weird hook, no documentation. Example does not work, have to typecast to array. Otherwise an invalid argument for foreach warning is issued. Values seem to be persisted but can't find it in the DB
            $event = $this->getEvent();

            foreach ((array)$event->get('settings') as $name => $value){

                $this->set($name, $value, 'Survey', $event->get('survey'));
            
            }

        }

        public function beforeActivate()
        {

            //creates demo app

            $settings = $this->parsedsettings;

            $config = Yii::app()->getComponents(false);
            
            $prefix = $config['db']->tablePrefix;

            $title = 'LimesurveyPdfEmailResultscreenPluginDemo';

            $query = Yii::app()->db->createCommand()
            ->select('surveyls_title')
            ->from($prefix.'surveys_languagesettings')
            ->where('surveyls_title=:title', array(':title'=>$title))
            ->queryRow();

           if ($query['surveyls_title'] !== $title){

                $pmanager   = $this->pluginManager->getAPI();

                Yii::app()->loadHelper('admin/import');

                $sFullFilePath = $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/demo/LimesurveyPdfEmailResultscreenPluginDemo.lss';

                $aImportResults = importSurveyFile($sFullFilePath, true);



                if (isset($aImportResults['error'])){

                    $err = $aImportResults['error'];
                    $pmanager->setFlash("<p>Error: $err</p>");

                }else{

                    $errors = 0;
                    
                    if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/styles-public/custom')) {

                        try{

                            mkdir($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/styles-public/custom', 0777, true);


                        }catch(Exception $e){

                            $errors++;

                            $pmanager->setFlash("<p>Error creating directory:". $e->getMessage()."</p>");

                        }

                    }


                    if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/styles-public/custom/demo.css')) {

                        try{

                            copy($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/demo/css/demo.css', $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/styles-public/custom/demo.css');

                        }catch(Exception $e){

                            $errors++;

                            $pmanager->setFlash("<p>Error copying demo.css file:". $e->getMessage()."</p>");

                        }

                    }

                    if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/scripts/custom')) {

                        try{

                            mkdir($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/scripts/custom', 0777, true);

                        }catch(Exception $e){

                            $errors++;

                            $pmanager->setFlash("<p>Error creating directory:". $e->getMessage()."</p>");

                        }

                    }


                    if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/scripts/custom/chartfactory.js')) {

                        try{

                            copy($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/demo/chartfactory/chartfactory.js', $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/scripts/custom/chartfactory.js');

                         }catch(Exception $e){

                            $errors++;

                            $pmanager->setFlash("<p>Error copying chartfactory.js file:". $e->getMessage()."</p>");

                        }
                        
                    } 

                    if ($errors === 0){

                         $pmanager->setFlash("<p>Created demo survey named 'LimesurveyPdfEmailResultscreenPluginDemo' and created its required javascript and css files.</p>");

                    }
                    
                }


            }


        }


        public function cron()
        {

            $settings = [];

            foreach($this->settings as $k => $setting){

                $settings[$k] = $setting['current'];

            }

            if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].$settings['PdfGenerator_Download_Folder'])) {

                mkdir($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].$settings['PdfGenerator_Download_Folder'], 0777, true);

            }

            $files = scandir($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].$settings['PdfGenerator_Download_Folder']);

            $nowtime = microtime(true) * 1000;

            foreach ($files as $file){

                if( $file[0] !== '.' && intval(explode('.', $file)[0]) < $nowtime - (1000 * intval($settings['LimesurveyPdfEmailResultscreenPlugin_Delete_Download_After']))  ){

                    unlink($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].$settings['PdfGenerator_Download_Folder'].'/'.$file);

                }

            }

        }

      

        public function afterSurveyComplete()
        {

            $settings = $this->parsedsettings;
             
            //TODO find a way to make this work
            //scandir and cleanup files
            //$this->cron();

            $event      = $this->getEvent();
            $surveyId   = $event->get('surveyId');
            $responseId = $event->get('responseId');

            $pmanager   = $this->pluginManager->getAPI();
            $response   = $pmanager->getResponse($surveyId, $responseId);

           
            /*
            this gets and sets survey specific config
           */
        
            
            $settings['debug']                          = $this->get('debug', 'Survey', $surveyId);
            $settings['parsenested']                    = $this->get('parsenested', 'Survey', $surveyId);
            $settings['createpdf']                      = $this->get('createpdf', 'Survey', $surveyId); 
            $settings['pdftemplate']                    = $this->get('pdftemplate', 'Survey', $surveyId); 
            $settings['showinresult']                   = $this->get('showinresult', 'Survey', $surveyId); 
            $settings['resulttemplate']                 = $this->get('resulttemplate', 'Survey', $surveyId); 

            $emailsettings = [];

            $emailsettings['debugemail']            = $this->get('debugemail', 'Survey', $surveyId);
            $emailsettings['fromemail']             = $this->get('fromemail', 'Survey', $surveyId);
            $emailsettings['fromemailname']         = $this->get('fromemailname', 'Survey', $surveyId);
            $emailsettings['sendemail']             = $this->get('sendemail', 'Survey', $surveyId);
            $emailsettings['attachpdf']             = $this->get('attachpdf', 'Survey', $surveyId);
            $emailsettings['attachmentname']        = $this->get('attachmentname', 'Survey', $surveyId);
            $emailsettings['emailsubject']          = $this->get('emailsubject', 'Survey', $surveyId);
            $emailsettings['emailtemplate']         = $this->get('emailtemplate', 'Survey', $surveyId);
            $emailsettings['emailtemplatetype']     = $this->get('emailtemplatetype', 'Survey', $surveyId);
            $emailsettings['emailsuccessmessage']   = $this->get('emailsuccessmessage', 'Survey', $surveyId);
            $emailsettings['emailerrormessage']     = $this->get('emailerrormessage', 'Survey', $surveyId);

             

            $pdfsettings = [];

            $pdfsettings['createpdf']                       = $this->get('createpdf', 'Survey', $surveyId);
            $pdfsettings['showdownloadpdftext']             = $this->get('showdownloadpdftext', 'Survey', $surveyId);
            $pdfsettings['downloadpdftext']                 = $this->get('downloadpdftext', 'Survey', $surveyId);
            $pdfsettings['pdftemplate']                     = $this->get('pdftemplate', 'Survey', $surveyId);
            $pdfsettings['pdfdownloadfolder']               = $this->get('PdfGenerator_Download_Folder', 'Survey', $surveyId);
            $pdfsettings['pdfconfig']                       = $this->get('pdfconfig', 'Survey', $surveyId);
            $pdfsettings['pdfheader']                       = $this->get('pdfheader', 'Survey', $surveyId);
            $pdfsettings['headercontent']                   = $this->get('headercontent', 'Survey', $surveyId);
            $pdfsettings['headercontenttag']                = $this->get('headercontenttag', 'Survey', $surveyId);
            $pdfsettings['headercontentstyle']              = $this->get('headercontentstyle', 'Survey', $surveyId);
            $pdfsettings['headerheight']                    = $this->get('headerheight', 'Survey', $surveyId);

            $pdfsettings['pdffooter']                       = $this->get('pdffooter', 'Survey', $surveyId);
            $pdfsettings['footercontent']                   = $this->get('footercontent', 'Survey', $surveyId);
            $pdfsettings['footercontenttag']                = $this->get('footercontenttag', 'Survey', $surveyId);
            $pdfsettings['footercontentstyle']              = $this->get('footercontentstyle', 'Survey', $surveyId);
            $pdfsettings['footerheight']                    = $this->get('footerheight', 'Survey', $surveyId);

             /*
            check for overrides
           */

            $overrides = $this->checkOverrides($response);

             /*
            Set overrides
           */

            $settingskeys = ['debug', 'parsenested', 'createpdf', 'pdftemplate', 'showinresult', 'resulttemplate'];
            $emailsettingskeys = ['debugemail', 'fromemail', 'fromemailname', 'sendemail', 'attachpdf', 'attachmentname', 'emailsubject', 'emailtemplate', 'emailtemplatetype', 'emailsuccessmessage', 'emailerrormessage'];
            $pdfsettingskeys = [ 'showdownloadpdftext', 'downloadpdftext', 'pdfdownloadfolder', 'pdfconfig', 'pdfheader', 'headercontent', 'headercontenttag', 'headercontentstyle', 'headerheight', 'pdffooter', 'footercontent', 'footercontenttag', 'footercontentstyle', 'footerheight'];


            foreach($overrides['overridesettings'] as $k => $v) {

                if(in_array($k, $settingskeys)){

                    $settings[$k] = $v;

                }

                if(in_array($k, $emailsettingskeys)){

                    $emailsettings[$k] = $v;

                }

                if(in_array($k, $pdfsettingskeys)){

                    $pdfsettings[$k] = $v;

                }

            }

            $pdfsettings['footercontent'] = str_replace('{ { ', "{{", $pdfsettings['footercontent']);
            $pdfsettings['footercontent'] = str_replace(' } }', "}}", $pdfsettings['footercontent']);

            $pdfsettings['headercontent'] = str_replace('{ { ', "{{", $pdfsettings['headercontent']);
            $pdfsettings['headercontent'] = str_replace(' } }', "}}", $pdfsettings['headercontent']);



            
            $validationerrors = [];

            if(count($validationerrors) === 0){

                $parsedworkload = $this->createWorkload($response, $settings);
                $workload = $parsedworkload['workload'];

                $dynamicemailsettings = $parsedworkload['emailsettings'];

                require __DIR__. '/getAnswersAndQuestions.php';

                $aaq = new getAnswersAndQuestions();

                $data = $aaq->getResponse($surveyId);

                
             
                $microtime = (string)(number_format((microtime(true) * 1000),0, '.', ''));
                $pdfname = $microtime . '.pdf';
                $downloadpath = $settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].$pdfsettings['pdfdownloadfolder'];

                /*
                * Hook in twig here
                *
                */

                if (!file_exists($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/compilationcache')) {

                    mkdir($_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].'/plugins/LimesurveyPdfEmailResultscreenPlugin/compilationcache', 0777, true);

                }


                $c = $this->parseTwig($workload, $data, $settings);

                $pdfall = '';

                $c['parseerrors'] = [];


                foreach($c['pdf'] as $pv){

                    $pdfall .= $pv;

                }
      
                $resp = $event->getContent($this);

                if(strlen($pdfall) > 0){

                    $configpdf = $this->getPdfConfig($pdfsettings);

                    if($settings['debug'] === '1'){

                        echo '<h1>Pdf config</h1>';

                        CVarDumper::dump($configpdf);

                        echo '<br><br>';

                    }



                    try{

                        //check if is relative path
                        if (strpos($settings['LimesurveyPdfEmailResultscreenPlugin_phantomjs_Path'], '/phantomjs') === 0){

                            $path = $_SERVER['DOCUMENT_ROOT'].$settings['LimesurveyPdfEmailResultscreenPlugin_app_subfolder'].$settings['LimesurveyPdfEmailResultscreenPlugin_phantomjs_Path'];

                        }else{

                            $path = $settings['LimesurveyPdfEmailResultscreenPlugin_phantomjs_Path'];

                        }
       
                        $params = array_merge(['search_paths' => $path ], $configpdf);

                        $converter = new PhantomJS($params);
                   
                        $input = new TempFile($pdfall, 'html');

                        $converter->convert($input, $_SERVER['DOCUMENT_ROOT'].$downloadpath.'/'.$pdfname);

                        $link = "http://$_SERVER[HTTP_HOST]/$downloadpath/$pdfname";

                        if($pdfsettings['showdownloadpdftext'] === '1'){

                            $dltxt = str_replace('[link]', "<a href='$link'>", $pdfsettings['downloadpdftext']);
                            $dltxt = str_replace('[/link]', "</a>", $dltxt);
                            $dltxt = str_replace('[', "<", $dltxt);
                            $dltxt = str_replace(']', ">", $dltxt);

                            $resp->addContent($dltxt);

                        }

                        if($emailsettings['sendemail'] === '1'){

                            require __DIR__. '/ResultMailer.php';

                            $mailer = new ResultMailer();

                            $mailresult = $mailer->sendMail($link, $pdfname, $emailsettings, $settings, $dynamicemailsettings, $tmplfolders);

                            if($mailresult === 1){

                                $resp->addContent($emailsettings['emailsuccessmessage']);

                            }else{

                                $resp->addContent($emailsettings['emailerrormessage']);

                            }

                        }


                    }catch (Exception $e){

                        if($settings['debug'] === '1'){

                            CVarDumper::dump(['error' => $e, 'message' => $e->getMessage()]);

                        }

                        $resp->addContent("An error occurred creating a pdf.");

                    }

                }

                if (count($c['parseerrors']) > 0){

                    if($settings['debug'] === '1'){

                        foreach($c['parseerrors'] as $err){
                                
                            $er = $err['error'];
                            $tra = $err['trace'];
                            $templ = $err['template'];

                            $resp->addContent("<h4>Parse-error</h4><p>Error: $er</p><p>Trace: $tra</p><p>Template: $templ</p>");

                        }

                    }else{

                        $resp->addContent("An error occurred your pdf may not be correct.");

                    }

                }

                if($settings['debug'] === '1'){

                    echo '<h1>Data</h1>';

                    CVarDumper::dump($data);

                    echo '<br><br>';

                }


                CVarDumper::dump(['reslength' => count($c['res'])]);
                foreach($c['res'] as $attach){

                    $resp->addContent($attach);

                }

            }else{

                $resp = $event->getContent($this);

                if($settings['debug'] === '1'){

                    foreach($validationerrors as $error){

                        $errortitle = $error['error'];
                        $errortext = $error['msg'];
                        $tmpl = $error['template'];

                        $resp->addContent("<h4>$errortitle</h4><p>$errortext</p><p>template: $tmpl</p>");

                    }

                }else{

                    $resp->addContent("<h4>An error occured...</p>");

                }

            }
           
        }


        private function parseTwig($workload, $data, $settings)
        {

            $pdf = [];
            $res = [];
            $parseerrors = [];

            foreach ($workload as $k => $v){

                if(isset($v) && isset($settings['showinresult']) && isset($settings['createpdf']) && $settings['showinresult'] !== '1' && $settings['createpdf']!== '1'){

                    continue;

                }else{


                    if (isset($settings['showinresult']) && $settings['showinresult'] === '1'){

                        $restwigparser = new TwigParser();

                        $res[] = $restwigparser->parse($settings, $settings['resulttemplate'], $data, $tmplfolders);

                    }

                    if (isset($v) && isset($settings['createpdf']) && $settings['createpdf'] === '1'){

                        $pdftwigparser = new TwigParser();

                        $pdf[] = $pdftwigparser->parse($settings, $settings['pdftemplate'], $data, $tmplfolders);

                    }

                }

            }

            return ['pdf'=> $pdf, 'res'=> $res, 'parseerrors' => $parseerrors];

        }


        private function getPdfConfig($pdfsettings){

            $config = [];

            $listsettings = $pdfsettings['pdfconfig'];

            $listsettings   = preg_replace('/\s+/', '', $listsettings);
            $listsettings   = preg_replace('~\x{00a0}~','',$listsettings);

            $varstemp = array_map('trim', explode('|', $listsettings));

            foreach($varstemp as $v){

                $t = explode('=', $v);
                $config[$t[0]] = $t[1];

            }

            if(isset($pdfsettings['pdfheader']) && $pdfsettings['pdfheader'] === '1'){

                $config['header'] = [];
                $config['header']['height'] = $pdfsettings['headerheight'];
                $config['header']['content'] = $pdfsettings['headercontent'];

                if(isset($pdfsettings['headercontenttag']) && $pdfsettings['headercontenttag'] !== ''){

                    $tag = $pdfsettings['headercontenttag'];

                    if(isset($pdfsettings['headercontentstyle']) && $pdfsettings['headercontentstyle'] !== ''){

                        $style = $pdfsettings['headercontentstyle'];

                        $config['header']['content'] = "<$tag style='$style'>". $pdfsettings['headercontent']."</$tag>";

                    }else{

                        $config['header']['content'] = "<$tag>". $pdfsettings['headercontent']."</$tag>";

                    }

                }else{

                    $config['header']['content'] = $pdfsettings['headercontent'];

                }

            }



            if(isset($pdfsettings['pdffooter']) && $pdfsettings['pdffooter'] === '1'){

                $config['footer'] = [];
                $config['footer']['height'] = $pdfsettings['footerheight'];
                $config['footer']['content'] = $pdfsettings['footercontent'];

                if(isset($pdfsettings['footercontenttag']) && $pdfsettings['footercontenttag'] !== ''){

                    $tag = $pdfsettings['footercontenttag'];

                    if(isset($pdfsettings['footercontentstyle']) && $pdfsettings['footercontentstyle'] !== ''){

                        $style = $pdfsettings['footercontentstyle'];

                        $config['footer']['content'] = "<$tag style='$style'>". $pdfsettings['footercontent']."</$tag>";

                    }else{

                        $config['footer']['content'] = "<$tag>". $pdfsettings['footercontent']."</$tag>";

                    }

                }else{

                    $config['footer']['content'] = $pdfsettings['footercontent'];

                }

            }

            return $config;

        }


        private function createWorkload($response)
        {

            $workload = [];

            $emailsettings = [];

            $css = [];
            $js = [];

            $baseurl = [];

            foreach ($response as $k => $v){

                if(strrpos(trim($k), 'variablemarker') !== false){

                    $t = [];

                    $v= preg_replace('/\s+/', '', $v);
                    $v = preg_replace('~\x{00a0}~','',$v);

                    $v = stripslashes($v);

                    $temp = array_map('trim', explode('|', $v));

                    foreach($temp as $val){

                        $p = array_map('trim', explode('=', $val));

                        if($p[0] === 'variables'){

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

                        }else{
                       
                            continue;

                        } 

                    }

                    $workload[] = $t;

                }else if(strrpos(trim($k), 'emailmarker') !== false){

                    //$v= preg_replace('/\s+/', '', $v);
                    //$v = preg_replace('~\x{00a0}~','',$v);

                    $v = stripslashes($v);

                    $temp = array_map('trim', explode('|', $v));

                    foreach($temp as $k => $v){

                        $p = array_map('trim', explode('=', $v));

                        if(trim($p[0]) === 'toemail'){

                            $toemails = array_map('trim', explode(',', $p[1]));

                            $emailsettings[trim($p[0])] = [];

                            foreach($toemails as $em){

                                $emailsettings[trim($p[0])][] = $em;

                            }

                        }else if(trim($p[0]) === 'variables'){

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

                            $emailsettings[trim($p[0])] = $varray; 


                        }else{

                            continue;

                        }

                    }

                }

            }

            return ['workload'=> $workload, 'emailsettings' => $emailsettings]; 

        }

        private function checkOverrides($response)
        {

            $configkeys = ['debug', 'parsenested', 'createpdf', 'pdftemplate', 'showinresult', 'resulttemplate', 'fromemail', 'fromemailname', 'sendemail', 'attachpdf', 'attachmentname', 'emailsubject', 'emailtemplate', 'emailtemplatetype', 'emailsuccessmessage', 'emailerrormessage', 'showdownloadpdftext', 'downloadpdftext', 'pdfdownloadfolder', 'pdfconfig', 'pdfheader', 'headercontent', 'headercontenttag', 'headercontentstyle', 'headerheight', 'pdffooter', 'footercontent', 'footercontenttag', 'footercontentstyle', 'footerheight'];
 
            $overridesettings = [];

            foreach ($response as $k => $v){

                if(strrpos(trim($k), 'overridesettings') !== false){

                    //first check for pdfconfig
                    $temp =  explode('|', $v);

                    foreach($temp as $key => $val){

                        $tt = explode('=', $val);
                        

                        if(trim($tt[1]) === 'true'){

                            $tt[1] = '1';

                        }

                        if(trim($tt[0]) === 'pdfconfig'){

                            $tt[1]  = str_replace('&', '|', $tt[1]);

                        }

                        $overridesettings[trim($tt[0])] = $tt[1];

                    }

                }

            }


            return ['overridesettings' => $overridesettings];

        }
       
    }
