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


  private function createCharts($workload)
  {

    $res = [];

    $pdf = [];

    $resultpage = [];

    foreach ($workload as $k => $v){

      if($v['showinresult'] === 'false' && $v['showinpdf'] === 'false'){

        continue;

      }else{

        if ($v['showinresult'] === 'true'){

          $reshtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/plugins/PdfGenerator/templates/'.$v['resulttemplate']);

          $reshtml = html_entity_decode($reshtml);

          foreach($v['variables'] as  $vark => $varv){

            $vark = trim($vark);

            $reshtml = str_replace("{!-$vark-!}", trim($varv), $reshtml);

          }

          $res[] = $reshtml;

        }

        if ($v['showinpdf'] === 'true'){

          $pdfhtml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/plugins/PdfGenerator/templates/'.$v['pdftemplate']);

          $pdfhtml = html_entity_decode($pdfhtml);

          foreach($v['variables'] as  $vark => $varv){

            $pdfhtml = str_replace("{!-$vark-!}", $varv, $pdfhtml);

          }

          $pdf[] = $pdfhtml;

        }

      }

    }

    return ['pdf'=> $pdf, 'res'=> $res];

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

    $workload = [];

    foreach ($response as $k => $v){

      if(strrpos(trim($k), 'pdfmarker') !== false){

        $t = [];

        $temp = array_map('trim', explode('|', $v));

        foreach($temp as $val){

          $p = $temp = array_map('trim', explode('=', $val));

          if($p[0] !== 'variables'){

            $t[$p[0]] = $p[1];

          }else{

            $vars = array_map('trim', explode(',', $p[1]));

            $varray = [];

            foreach($vars as $varv){

              $varray[$varv] = $response[$varv];
            }       

            $t[$p[0]] = $varray;

          }   

        }

        $workload[] = $t;

      }

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

      CVarDumper::dump(['error' => $e]);
      $res = $event->getContent($this)
      ->addContent("An error occurred creating a pdf.");

    }

    foreach($c['res'] as $attach){

      $resp->addContent($attach);

    }
  
   
  }
   
}
