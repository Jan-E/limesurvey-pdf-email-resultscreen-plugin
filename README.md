# limesurvey-plugin-pdfcreator ((very) alpha)

A flexible limesurvey pdfcreator

todo: find solution for comment question
uppdate readme
finish example
get questions in resultset

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

http://phantomjs.org/download.html provides [download binaries](http://phantomjs.org/download.html). Find a way to determine which one you need. Now create a folder named 'phantomjs' in your rootfolder (sibling to the application-folder), and put in the folder named bin which you unpacked from the downloaded binary (you can put in all the other stuff but the bin-folder is the required one).


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

The recommended usage is to create one markerquestion at the end of the survey. The markerquestion should look like this:

```{'showinresult=true| createpdf=true|resulttemplate=resultpagehandler.html| pdftemplate=pdfhandler.html|variables=q1,q2,q3,q4,q5,q6,q7,q8,q9,q10,q11 | parsenested=true | baseurl=sitebaseurl'}´´´


Parameters explained:

- showinresult: Mandatory. This will be added to the resultpage when set to true.
- createpdf: Mandatory. This will create a pdf and will print a downloadlink on the resultpage.
- pdftemplate: Mandatory. Set empty (pdftemplate=) when not needed. Must be placed in 
- resulttemplate: Mandatory. Set empty (resulttemplate=) when not needed
- variables. Mandatory. Comma separated variable names (question codes).

- parsenested.: Optional (recommended). When set to true, you don't have pass all subquestion variables. For example: You can pass q1 as variable and q1_SQ01 gets parsed as a json object.
- baseurl. Optional. baseurl=sitebaseurl can be used in a template as: {!-sitebaseurl-!} (you specify the name of the variable as the value). This can be used to create a link to css or js. Start with '/'.

NOTE: every string with 'http' in it will be parsed without quotes.


If you create one markerquestion at the end you can set javascript variables and use that variable to do things.

IMPORTANT: limesurvey tries to parse strings enclosed in curly brackets when there are no spaces directly after the opening and before the closing curly bracket in the result page. This wil affect your javascript. The workaround is to always have a space after the opening and before the closing bracket. So: var myObject = { key: value } (note the spaces);

You can set different templates for the resultpage and for the pdfpage. This is because you may need to tweak your html and css to make your pdf look nice. Another reason is that limesurvey (2.5) has JQuery and Bootstrap allready loaded. Now you can only load these libraries in your pdf template only.



You can also configure the pdf. This is also done by a markerquestion. This question has to contain the string 'pdfconfig'.

Example:

```{'footerheight=2cm|footercontent={ { pageNum } } / { { totalPages } }|orientation=landscape|border=2cm|footercontenttag=h1|footercontentid=footerid'}´´´

Explanation: It's just as explained in https://github.com/kriansa/h2p, but the only difference is you have to pass footercontent and footerheight and headercontent and headerheight because it is a nested array. You can also pass footercontenttag and footercontentid to style. The text will be wrapped in a tag you provide with the id you provide. Mind the spaces between the brackets. 


#### Example



### Templates

Templates should be in the folder : plugins/PdfGenerator/templates

As stated in the previous section, passed variables replace that same variable name between '{!-' and '-!}'.

For instance:

var question1 = {!-question1-!};
var question2 = {!-question2-!};



##### Reusing templates

You can also create reusable scripts. This can be done by creating a function and calling it later by passing in configuration and data parameters. Suppose I create a template like this (let's call it chartfactory.js and put it in your yoursite/scripts/custom):

```
var chartfactory = {};
  chartfactory.createPie = function(dataset, domelementid){
    //dataset should look like this
    /*var dataset = [
        { label: 'text', value: 15 }, 
        { label: 'text2', value: 32 },
        { label: 'text3', value: 38 },
        { label: 'text4', value: 51 }
      ];*/

      //config should look like this

      //var config = {width: int, height: int};
      var radius = Math.min(config.width, config.height) / 2;

      var color = d3.scale.category20b();

      var svg = d3.select('#'+domelementid)
        .append('svg')
        .attr('width', config.width)
        .attr('height', config.height)
        .append('g')
        .attr('transform', 'translate(' + (config.width / 2) + 
          ',' + (config.height / 2) + ')');

      var arc = d3.svg.arc()
        .outerRadius(config.radius);

      var pie = d3.layout.pie()
        .value(function(d) { return d.value; })
        .sort(null);

      var path = svg.selectAll('path')
        .data(pie(dataset))
        .enter()
        .append('path')
        .attr('d', arc)
        .attr('fill', function(d, i) { 
          return color(d.data.label);
        });

  }

```


After that you can create a markerfile which loads and uses this javascript file:

Markerfile

``` {'showinresult=true| showinpdf=true |resulttemplate=demo/resultscreen.html | pdftemplate=demo/pdf.html|variables=q1,q2,q3,q4,q5,q6,q7,q8,q9,q10,q11,pdf |parseasobject=true|baseurl=baseurl'}```


Templates:

demo/resultscreen.html:

``` 
<div>
    <link rel='stylesheet' href='{!-baseurl-!}/styles-public/custom/demo.css'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
    <script src="{!-baseurl-!}/scripts/custom/chartfactory.js"></script>
    <h1>Created as reusable</h1>
    
    <div class='row'>
        <div class='col-md-4'>
            <div id='reusable1' class='piechart'>
                
            </div>
        </div>
        <div class='col-md-4'>
            
        </div>
        <div class='col-md-4'>
            
        </div>
    </div>
    <script>

    var q1 = {!-q1-!};
    var q2 = {!-q2-!};
    var q3 = {!-q3-!};
    var q4 = {!-q4-!};
    var q5 = {!-q5-!};
    var q6 = {!-q6-!};
    var q7 = {!-q7-!};
    var q8 = {!-q8-!};
    var q9 = {!-q9-!};
    var q10 = {!-q10-!};
    var q11 = {!-q11-!};

    var piedata = [];
    

    for (var key in q7) {

        if(q7[key] === ''){

            q7[key] = 0;
        }

        piedata.push({ label: key, value: parseInt(q7[key]) });

    }

    chartfactory.createPie(piedata, 'reusable1');

    </script>
</div>

```


demo/pdf.html (the same but loading bootstrap because it's not in the resultpage):


```
<div>
    <link rel='stylesheet' href='{!-baseurl-!}/styles-public/custom/demo.css'>
    <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
    <script src="{!-baseurl-!}/scripts/custom/chartfactory.js"></script>
    <h1>Created as reusable</h1>
    
    <div class='row'>
        <div class='col-md-4'>
            <div id='reusable1' class='piechart'>
                
            </div>
        </div>
        <div class='col-md-4'>
            
        </div>
        <div class='col-md-4'>
            
        </div>
    </div>
    <script>

    var q1 = {!-q1-!};
    var q2 = {!-q2-!};
    var q3 = {!-q3-!};
    var q4 = {!-q4-!};
    var q5 = {!-q5-!};
    var q6 = {!-q6-!};
    var q7 = {!-q7-!};
    var q8 = {!-q8-!};
    var q9 = {!-q9-!};
    var q10 = {!-q10-!};
    var q11 = {!-q11-!};

    var piedata = [];
    

    for (var key in q7) {

        if(q7[key] === ''){

            q7[key] = 0;
        }

        piedata.push({ label: key, value: parseInt(q7[key]) });

    }

    chartfactory.createPie(piedata, 'reusable1');

    </script>
</div>```

```



# Quirks:

-It's not always rendered the way you want so test and try to fix it, don't assume it will be perfect right away. Google for phantomJs and your problem.

-Appended html in the response page seems to be appended in a table. However, it seems you can use bootstrap nevertheless.




