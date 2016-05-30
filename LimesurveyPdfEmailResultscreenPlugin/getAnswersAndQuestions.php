<?php
require_once 'getAnswersAndQuestionsInterface.php';



 
    class getAnswersAndQuestions implements getAnswersAndQuestionsInterface {


        public function getResponse($surveyid)
        {

             /*
            * Using limesurvey pdf class as example to get the data
            */

            $iSurveyID = (int)$surveyid;

             if (isset($_SESSION['survey_'.$iSurveyID]['sid']))
            {
                $iSurveyID = $_SESSION['survey_'.$iSurveyID]['sid'];
            }

            // Set the language for dispay
            if (isset($_SESSION['survey_'.$iSurveyID]['s_lang']))
            {
                $sLanguage = $_SESSION['survey_'.$iSurveyID]['s_lang'];
            }
            elseif(Survey::model()->findByPk($iSurveyID))// survey exist
            {
                $sLanguage = Survey::model()->findByPk($iSurveyID)->language;
            }
            else
            {
                $sLanguage = Yii::app()->getConfig("defaultlang");
            }

            $sSRID = $_SESSION['survey_'.$iSurveyID]['srid'];

            $aFullResponseTable = getFullResponseTable($iSurveyID, $sSRID, $sLanguage);

            $redefined = $this->replaceKeysWithCode($aFullResponseTable);

            return $redefined;

        } 


        private function replaceKeysWithCode($responsetable)
        {

            $newresponse = [];

            $bykeyresponse = [];

            $last = ['id' => null, 'varname' => null];

            foreach ($responsetable as $k=>$v){

                if(strpos($k, 'X') !== false){
                    //when X is a question name

                    if(strpos($k, 'qid') !== false){

                        //is question with subquestion(s)
                        $info = $this->parseIdandVarname($k);

                        $qcode = $this->getQuestionCodeById($info['id']);

                        $last = $info;

                    }else{

                        //is just a question or subquestion
                        
                        $info = $this->parseIdandVarname($k);

                        $qcode = $this->getQuestionCodeById($info['id']);

                        if($info['id'] === $last['id']){

                            $newresponse[$qcode][$qcode.'_'.$info['varname']] = $v;

                        }else{

                            $newresponse[$qcode] = $v;

                            $lastid = $info;

                        }

                    }

                    $bykeyresponse[$qcode] = $v;
                    
                }else{

                    continue;

                }

            }

            $jsonarray = [];

            foreach($newresponse as $k => $v){

                $jsonarray[$k] = json_encode($v);

            }

            return ['nested' => $newresponse, 'bykey' => $bykeyresponse, 'nestedjson' => $jsonarray];


        }


        private function getQuestionCodeById($id)
        {

            $config = Yii::app()->getComponents(false);
            
            $prefix = $config['db']->tablePrefix;

            $query = Yii::app()->db->createCommand()
            ->select('title')
            ->from($prefix.'questions')
            ->where('qid=:id', array(':id'=>$id))
            ->queryRow();

            return $query['title'];

        }


        private function parseIdandVarname ($string) 
        {

            $temp = explode('X', $string);

            $lastpiece = $temp[2];

            //questionid are numerical first characters of $temp[2] because questioncodes cannot start with numbers;
            $i = 0;
            $isnumerical = true;
            $id = '';
            $varname = '';
            while($isnumerical === true){

                if( isset($lastpiece[$i]) && is_numeric($lastpiece[$i]) ){

                    $id .= $lastpiece[$i];

                    $i++;

                }else{

                    $varname = '';

                    if(isset($lastpiece[$i])){

                        $varname = substr($lastpiece, $i);
                    }
                    
                    $isnumerical = false;

                }

            }

            return ['id' => (int) $id, 'varname' => $varname];

        }

       
    }