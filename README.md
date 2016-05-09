# limesurvey-plugin-pdfcreator ((very) alpha)

A flexible limesurvey pdfcreator

# Overview

This is a limesurvey plugin to create a downloadable pdf after a respondent completes a survey and show this content in the completed page. 

Because as far as I know, there is no option for a plugin to add functionality to the backend (ie It's not possible to create buttons with surveyspecific options, except for coding into the core, which would be erased after each update), this plugin uses markerquestions to configure. This way you can make use of conditional logic provided by limesurvey's expression manager. These markerquestions are of type 'equation type'.
You also have to provide templates (html/javascript/css) and upload them to a folder.  In these templates you have to wrap your variables in {!-yourvariablename-!} (handlebar-exclamation mark-hyphen, no double handlebars because I don't want to conflict with Angular templates (Angular uses double handlebars as placeholders)).

### Important

This is **NOT** a simple plug & play plugin. To make it function, you need to do some work. The main reason for this is that this plugin uses [kriansa's h2p library](https://github.com/kriansa/h2p "H2p"), which in turn makes use of [PhantomJS](http://phantomjs.org/ "PhantomJS"). You can install PhantomJS on your server (see for instance for ubuntu [this](https://gist.github.com/julionc/7476620)). However, on shared hosting you most probably can't do that so you'll need to run a precompiled binary.

On the other hand, PhantomJS makes use of [Qt WebKit](https://wiki.qt.io/Qt_WebKit "Qt WebKit"), which makes it possible to really render pages using javascript, html and css, including fonts etc. This is something (as far as I know) pure php-pdf-creators don't do.

# Getting started

First make sure you have limesurvey 2.05+ installed or 2.06+ for cronjob support.

### Install PhantomJS

Install PhantomJS on your server or developmentbox (option 1) or get the binary and place it in your app (option 2, the shared hosting way).

#### Option 1: Install on your server or local machine

Google how to and make sure you know the path to phantomjs(.sh). For ubuntu see the previously mentioned [thread](https://gist.github.com/julionc/7476620). This path is what you have to input later on to make it work.


#### Option 2: Get binary and drop in app

http://phantomjs.org/download.html provides [download binaries](http://phantomjs.org/download.html). Find a way to determine which one you need. Now create a folder named 'phantomjs' in your rootfolder (sibling to the application-folder), and put in the folders bin, include, lib, and share which you unpacked from the downloaded binary.


### Create a download folder

Create a download folder in the root folder (sibling to the application-folder). If you are using git an empty folder won't be pushed so create a dummy file in it to force it. Set permissions on the folder. Maybe start with 777 to test and restrict later.


### Install H2P

Do a composer require kriansa/h2p or put "kriansa/h2p": "dev-master" in the require path of your composer.json and run composer update or install. (For OpenShift you may need to to downgrade some dependencies because it runs on php 5.4. Which ones you can see in the console while deploying).
Now you should have a 'vendor' folder in your limesurvey rootfolder.


### Install limesurvey-plugin-pdfcreator

Drop the PdfGenerator folder in your plugins folder. 


### Activate pdfGenerator

Go to your pluginmanager page in limesurvey and activate pdfGenerator. If you decided to use another path for your download or PhantomJS folder you can hit configure and change settings. If you installed PhantomJS on your machine you can change the path also in the configure screen. Also you can set after what time a pdf will be deleted. Default is 60 minutes.
Now you should be good to go!


### Activate cron (optional)

Everytime a pdf is generated, the plugin will check if files should be deleted because the time they will be stored (according to your configuratioon) ran out. This will only be done when a new pdf is created, so if your survey is not used very often these files will remain in the download folder. With a cron you can periodically check for files which should be deleted.
Just fire a php cli command php yourlimesurveydir/application/commands/console.php plugin cron --interval= < the same value as after which downloads are deleted > 
If you allready have a cron running you don't have to create another one. The plugin will be triggered by that other cron.



# Configuration

Because limesurvey does not allow to configure a plugin on the survey level, this will be done using marker questions. The global configuration is managed in the plugins' configuration page.

### Global config

Path to phantomjs: This is set to the second option (dropin phantomjs). If you followed the steps above you shouldn't have to change this (if you do change, mind the '/' at the start).

Download folder: If you followed the steps above you shouldn't have to change this (if you do change, mind the '/' at the start).

Delete generated pdf after amount of minutes: This will cleanup files after x minutes.

Debug: If this is set to true, you'll see the response of your query dumped in the resultscreen. This is convenient if you want to know what values your survey returns. For example: does a 'no answer'-option generate an empty string or a number? 

### Survey specific configuration: markerquestions

#### By (simple) example

Note: I apologize for the example, just a lack of inspiration ...

##### Single Questions

Suppose you have three questions:

1: Question code: likesicecream,    Question: How much do you like icecream? 0 = not at all, 5 = yes very much, Question type: 5 point choice,  Mandatory: Off

2: Question code: likescheese,    Question: How much do you like cheese? 0 = not at all, 5 = yes very much, Question type: 5 point choice,   Mandatory: Off

3: Question code: likesveggies,    Question: How much do you like veggies? 0 = not at all, 5 = yes very much, Question type: 5 point choice,   Mandatory: Off

If you want to create output based on the answers to these questions (output can be a chart or just an overview with questions and answers), you'll have to create a hidden 'Equation' question type question after the above questions (you could group all this marker questions at the end of your survey or each marker question after the questions it is based on, the choice is yours, it just has to be after these questions).
IMPORANT: You have to prepend the name (code field) of this markerfile with 'pdfmarker'. I recommend to just name them 'pdfmarker1', 'pdfmarker2' etc.

Because in the questions I have set 'Mandatory' to 'Off', a 'No answer'-option is shown in the 5 point choice question. The 'No answer'-option corresponds to a value of 6. Suppose I don't want to show a chart if in one or all of the question the 'No answer'-option is selected. The I can create a markerquestion, I call it 'pdfmarker1' after those questions (In production: Always hide this question: yes), and put this code in the question field (not in html mode!):

``` {if ((!is_empty(likesicecream) and likesicecream != 6) and (!is_empty(likescheese) and likescheese != 6) and (!is_empty(likesveggies) and likesveggies != 6), 'showinresult=true| showinpdf=true|resulttemplate=d3simplepie.html| pdftemplate=d3simplepie.html|variables=likesicecream,likescheese,likesveggies' , 'showinresult=false|showinpdf=false')}```

This code checks if none of the answers is 6. If one of the answers is 6 (or is not set) the expression evaluates to a string: 

'showinresult=false|showinpdf=false' 

If none of the answers is 6 the expression evaluates to another string: 

'showinresult=true| showinpdf=true|resulttemplate=d3simplepie.html| pdftemplate=d3simplepie.html|variables=likesicecream,likescheese,likesveggies'

The showinresult and showinpdf keys are mandatory. If the key showinresult is set to true, the keys resulttemplate and variables are mandatory, and if the key showinpdf is set to true, the keys pdftemplate and variables are mandatory.

The reason why you can set different templates for the resultpage and the pdf (they can be set to the same just point to the same template) is that the same template might render differently in the pdf compared to the resultpage rendering. This way you can tweak templates to look the same.

The structure of the string you have to create is as follows:

The string has to be in quotes.
The string contains key-value pairs separated by a vertical bar ('key=value | otherkey=othervalue'). The variables property has a comma separated value ('variables=var1, var2, var3, var4').

The variable names correspond to question codes.

If you pass in a variable named 'myspecialvariable', the value of this variable will be parsed in your template where you put in '{!-myspecialvariable-!}'.

##### Array Questions

Suppose you create a Array (5 point choice) question. Let's call it 'foodpreferences' (this is the code), With question-text: 'State how much you like the following: 0 = not very much, 5 = I love it!'.

This question has 3 subquestion: 

1: Code: (default: SQ001, but let's change it to 'Icecream' to make it more descriptive),  Subquestion: Icecream

2: Code: (default: SQ002, but let's change it to 'Cheese' to make it more descriptive),  Subquestion: Cheese

2: Code: (default: SQ003, but let's change it to 'Veggies' to make it more descriptive),  Subquestion: Veggies

Again, create a hidden 'Equation' question type marker-question after the above array question.

subquestions are addressed like: questioncode<underscore>subquestioncode. So: foodpreferences_Icecream etc. in this example.


``` {if (likesicecream != '' and likescheese != '' and likesveggies != '', 'showinresult=true| showinpdf=true|resulttemplate=d3simplepiearrayquestion.html| pdftemplate=d3simplepiearrayquestionpdf.html|variables=foodpreferences_Icecream,foodpreferences_Cheese,foodpreferences_Veggies' , 'showinresult=false|showinpdf=false')}```


Why is this code different from the previous? Because I am working in debug mode and I noticed this array 5 point question returns an empty string for the no answer option and not '6' as in the previous example. I now decide to not show a chart when either of the subquestions has no answer. I could also decide to use them anyway and transform an empty string in javascript. For example: var myvar = {!-myvar-!}; if(myvar === ''){ myvar = 0 }else{myvar = parseInt(myvar)}; (Or use a ternary).

Anyway, I am passing the values of the subquestions in the string and I am passing the variable names. Also I pass template names. Now you can parse the variable values into you template. The values in the templates should be {!-foodpreferences_Icecream-!}, {!-foodpreferences_Cheese-!} and {!-foodpreferences_Veggies-!}.

### Templates

Templates should be in the folder : plugins/PdfGenerator/templates

As stated in the previous section, passed variables replace that same variable name between '{!-' and '-!}'.

For instance:

var question1 = {!-question1-!};
var question2 = {!-question2-!};

Beware: There must be a value because otherwise it will result in an error (Uncaught SyntaxError: Unexpected token }). 
Also beware that some values may be passed as string while you actually need an integer (so use var question1 = parseInt({!-question1-!}); in that case).

You shouldn't create a full webpage because multiple html, body and head tags shouldn't exist on the same page. I tested phantomJS and it seems to work fine without these tags. You can do the following:

```
<div>
<style type="text/css" scoped>
/*some css*/
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
<script>
/*some inline script*/
</script>
<section>
 <!--some html-->
</section>
<div>
```
!!! VERY IMPORTANT !!! If you are using multiple templates, beware of using the same id's and classes in those templates. This can mess it up. I suggest you append all your variables, id's and classes with a number.

I'm working on a way create a hidden equation question to just load external scripts once. Now these scripts can be called multiple times (they will get loaded from the cache but reloading is not elegant (it does seem to work fine however).

# Quirks:

-It's not always rendered the way you want so test and try to fix it, don't assume it will be perfect right away. Google for phantomJs and your problem.

-Appended html in the response page seems to be appended in a table. However, it seems you can use bootstrap nevertheless.

