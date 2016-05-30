# Limesurvey-Pdf-Email-Resultscreen-Plugin (bèta)

A flexible limesurvey pdf, email and resultscreen creator

# Overview

This is a limesurvey plugin to create a downloadable pdf, send this pdf as an attachment with an email and show survey results after a respondent completes a survey on the completed page.

Dependencies: Composer, Phantomjs, h2p, swiftmailer, twig

This plugin allows you to provide templates (html/javascript/css), parse them as twig templates, apply javascript inside it, and create a pdf. In these templates you have to wrap your variables in {{yourvariablename}} (double handlebars see the twig documentation for more info). The pdf will be generated using PhantomJS which allows you to take full advantage of javascript and css! Think d3.js!!

### Important

This is **NOT** a simple plug & play plugin. To make it function, you need to do some work. The main reason for this is that this plugin uses [kriansa's h2p library](https://github.com/kriansa/h2p "H2p"), which in turn makes use of [PhantomJS](http://phantomjs.org/ "PhantomJS"). You can install PhantomJS on your server (see for instance for ubuntu [this](https://gist.github.com/julionc/7476620)). However, on shared hosting you most probably can't do that so you'll need to run a precompiled binary (which is recommended by phantomjs anyway).

On the other hand, PhantomJS makes use of [Qt WebKit](https://wiki.qt.io/Qt_WebKit "Qt WebKit"), which makes it possible to really render pages using javascript, html and css, including fonts etc. This is something (as far as I know) pure php-pdf-creators don't do.

# Getting started

First make sure you have limesurvey 2.05 or higher installed ~~or 2.06 or higher for cronjob support~~. I found out the hard way that limesurvey versionnumber 2.50+, buildnumber 160512 has a bug in it so avoid that one (and probable the 2.50+ versions before that one). In buildnumber 160526 this was fixed. To be safe... just install the newest one.

### Install/drop in PhantomJS

Install PhantomJS on your server or developmentbox (option 1) or get the binary and place it in your app (option 2, the shared hosting way).

#### Option 1: Install on your server or local machine(not recommended)

Google how to and make sure you know the path to phantomjs(.sh). For ubuntu see the previously mentioned [thread](https://gist.github.com/julionc/7476620). This path is what you have to input later on to make it work.


#### Option 2: Get binary and drop in app(recommended)

http://phantomjs.org/download.html provides [binaries](http://phantomjs.org/download.html). Find a way to determine which one you need. Now create a folder named 'phantomjs' in your rootfolder (sibling to the application-folder), and put in the folder named bin which you unpacked from the downloaded binary (you can put in all the other stuff but the bin-folder is the required one).


### Create a download folder

Create a download folder in the root folder (sibling to the application-folder). If you are using git an empty folder won't be pushed so create a dummy file in it to force it. Set permissions on the folder. Maybe start with 777 to test and restrict later.


### Install H2P

Do (from your apps rootfolder) a 

```
(sudo) composer require kriansa/h2p:dev-master 

```

or put "kriansa/h2p": "dev-master" in the require path of your composer.json and run composer update or install. The dev-master version is very important!. (Note to self: For OpenShift you may need to to downgrade some dependencies because it runs on php 5.4. Which ones you can see in the console while deploying).
Now you should have a 'vendor' folder in your limesurvey rootfolder.

### Install Swiftmailer

Do (from your apps rootfolder) a 

```
(sudo) composer require swiftmailer/swiftmailer @stable

```

(Note to self: For OpenShift you may need to to downgrade some dependencies because it runs on php 5.4. Which ones you can see in the console while deploying).
Now you should have swiftmailer in the 'vendor' folder in your limesurvey rootfolder.

### Install Twig


This is for my own reference see installing twig: http://twig.sensiolabs.org/doc/installation.html
Do (from your apps rootfolder) a 

```
(sudo) composer require twig/twig:~1.0

```

optional but recommended for twig:

```
cd vendor/twig/twig/ext/twig
(sudo) phpize
./configure
make
make install

```

and enable it in your php.ini

```
extension=twig.so 

```


### Install Limesurvey-Pdf-Email-Resultscreen-Plugin

Drop the LimesurveyPdfEmailResultscreenPlugin folder in your plugins folder. 


### Activate Limesurvey-Pdf-Email-Resultscreen-Plugin

Go to your pluginmanager page in limesurvey and activate Limesurvey-Pdf-Email-Resultscreen-Plugin. If you decided to use another path for your PhantomJS folder you can hit configure and change settings. If you installed PhantomJS on your machine you can change the path also in the configure screen. ~~Also you can set after what time a pdf will be deleted. Default is 60 minutes~~ (this is turned off right now).
Now you should be good to go!


### ~~Activate cron (optional)~~

~~Everytime a pdf is generated, the plugin will check if files should be deleted because the time they will be stored (according to your configuratioon) ran out. This will only be done when a new pdf is created, so if your survey is not used very often these files will remain in the download folder. With a cron you can periodically check for files which should be deleted.
Just fire a php cli command php yourlimesurveydir/application/commands/console.php plugin cron --interval= < the same value as after which downloads are deleted > 
If you allready have a cron running you don't have to create another one. The plugin will be triggered by that other cron.~~



# Configuration

### Global config

App subfolder: If the url to your app is a subfolder (www.example.com/subfolder), you can set your subfolder here.

Path to phantomjs: This is set to the second option (dropin phantomjs). If you followed the steps above you shouldn't have to change this (if you do change, mind the '/' at the start).

disabled right now: Delete generated pdf after amount of minutes.


### Survey config

Survey configuration can be done in: surveypage-> click Survey properties -> click General settings & texts -> browse down to Plugins and click it (You can let it slide to the left to have a full screen (it's not the smallest configuration page you've ever seen :)).

Just read the help text below every setting and it should be ok.


### Survey markerquestions

The markerquestions are used to pass variables and settings from your survey to this plugin. 

#### Email markerquestion

The emailmarkerquestion is used to pass one or more email adresses from your survey to the emailer. The  email markerquestion should be named emailmarker (or at least have 'emailmarker' in it, so emailmarker2 is also ok).

The recommended usage is to create one email markerquestion at the end of the survey. (type equation type and hide it in production ofcourse). The email markerquestion should look like this:

```
{'toemail=email1@example.com, email2@example.com'}

```

You can send to multiple email adresses. Just comma seperate them. Variables passed from the variable markerquestions can be used in the email template. In your survey settings page you can set Bcc's.


#### Override Survey config markerquestion

To override the survey configuration dynamically (because you want to set options dynamically, for instance only when a respondent has checked a checkbox with 'create a pdf' or prevent creating a pdf when the respondent hasn't answered any questions or something), you can create a markerquestion called 'overridesettings'. This equation type question should output a string. For example:

```
{'debug=true|createpdf=false|sendemail=true'}

```

#### List of overridable settings

| Attributes                        | Values                      |  Example                                                                                |
| -------------                     |:-------------:              |:-------------:                                                                          |  
| debug                             | true/false                  |  debug=true                                                                             |
| exludequestions                   | questioncode(s)&            |  q1&q2&q3                                                                               |
|                                   |                             |                                                                                         |
| createpdf                         | true/false                  |  createpdf=false                                                                        |
| showdownloadpdftext               | true/false                  |  showdownloadpdftext=true                                                               |
| downloadpdftext                   | string                      |  downloadpdftext=[p class='someclass']You can download your pdf [link]here[/link][/p]***|
| pdftemplate                       | string                      |  pdftemplate=mypdf.html.twig                                                            |
| pdftemplatefolders                | path(s)                     |  pdftemplatefolders=demo/pdf&demo/pdf/headers**                                         |
| pdfdownloadfolder                 | path/string                 |  pdfdownloadfolder=downloadfolder/myproject                                             |
| pdfconfig                         | string                      |  pdfconfig=border=1cm & orientation=landscape                                           |
|                                   |                             |                                                                                         |
| pdfheader                         | true/false                  |  pdfheader=true                                                                         |
| headercontent                     | string                      |  headercontent=my new text                                                              |
| headercontenttag                  | string                      |  headercontenttag=h1                                                                    |
| headercontentstyle                | string                      |  headercontentstyle=color:blue;text-align:center;                                       |
| headerheight                      | string                      |  headerheight=7mm                                                                       |
|                                   |                             |                                                                                         |
| pdffooter                         | true/false                  |  pdffooter=false                                                                        |
| footercontent                     | string                      |  footercontent=page { { pageNum } } of { { totalPages } }  pages  ****                  |
| footercontentstyle                | string                      |  footercontentstyle=color:blue;text-align:center;                                       |
| footerheight                      | string                      |  footerheight=1cm                                                                       |
|                                   |                             |                                                                                         |
| showinresult                      | true/false                  |  showinresult=true                                                                      |
| resulttemplate                    | string                      |  resulttemplate=myresult.html.twig                                                      |
| resulttemplatefolders             | path(s)&                    |  templatefolders=demo/result&demo/result/headers**                                      |
|                                   |                             |                                                                                         |
| sendemail                         | true/false                  |  sendemail=true                                                                         |
| fromemail                         | email                       |  fromemail=admin@example.com                                                            |
| fromemailname                     | name                        |  fromemailname=limesurvey admin                                                         |
| bcc                               | email                       |  bcc=admin@example.com                                                                  |
| attachpdf                         | true/false                  |  attachpdf=true                                                                         |
| attachmentname                    | pdf name/string             |  attachmentname=yourresult.pdf                                                          |
| emailsubject                      | string                      |  emailsubject=Your result                                                               |
| emailtemplate                     | string                      |  emailtemplate=emailtemplate.html.twig                                                  |
| emailtemplatefolders              | path(s)&                    |  emailtemplatefolders=demo/email&demo/email/headers**                                   |
| emailtemplatetype                 | 'text/html'/'text/plain'    |  emailtemplatetype=text/html                                                            |
| emailsuccessmessage               | string                      |  emailsuccessmessage=Your email has been sent                                           |
| emailerrormessage                 | string                      |  emailerrormessage=An error occured sending your email                                  |
| emailvalidationerrormessage       | string                      |  emailvalidationerrormessage=Email validation error:                                    |



** Every folder must be present. Twig will search those folders for templates. Also folders for included templates must be present. Create unique names for your templates.

*** The part between [link] and [/link] wil be parsed as a clickable link to the pdf. Html tags must be between brackets ([ instead of <]).

**** Note the spaces between { and { and between } and }.


### Templates

Templates should be in the folder : plugins/LimesurveyPdfEmailResultscreenPlugin/templates

These templates can also be placed in a subfolder. You must provide the subfolders you want twig to search. Also the subfolders for included templates must be provided in your configuration.

#### Variables in templates

This plugin serves your survey variables in tree ways: 'datanested', ~~'databykey'~~, and 'nestedjson'.

- datanested: This is a nested array and can be used in twig loops etc.
- ~~databykey: With this you can get single variable (much like expression manager does).~~
- nestedjson: This is very convenient to put a parent question code with all it's children in one javascript variable.
- baseurl: This is very convenient to load javascript or css files from your site: {{baseurl}}js/myjs.js

As stated before, passed variables replace that same variable name between '{{' and '}}'. See the twig documentation for loops etc.

For instance:

```
var question1 = {{nestedjson.question1| raw}}; //object
console.log(question1);

<h1>Hi {{datanested.q5.q5_SQ002[2]}}</h1>

```

If you check debug in your survey settings this data will be dumped on your screen. The quotes in the json are escaped as they should.

To make it parse as json in your template you have to pass the raw flag to you placeholder: {{jsonvariable | raw}}


Now you have your survey parameters available in your template. From here you can do your frontend magic. See Example below.

# Debugging

Always test your pdf template on the resultsreen first. If some external css or javascript is not found, phantomjs will probably fail without any meaningfull errors. In the resultscreen you can monitor those errors in your console. Set createpdf to false.
After you made sure the external stylesheets an javascript libraries are loaded you can set createpdf to true and tweak your pdf layout. This tweaking (at least for me) is necessary. See quirks below.

# Quirks:

- PDF's are not always rendered the way you want so test and try to fix it, don't assume it will be perfect right away. Google for phantomJs and your problem. It is rendered quite big because an A4 format has a quite small width so it will be rendered like a smartphone or tablet which may be too big. I just set fonts to smaller values etc, but maybe tweaking the viewport or something may do the trick. Also the phantomjs zoomFactor property does not seem to work. I don't know why.
- On linux hosting (probably most of you host on linux), phantomjs states: 'The system must have GLIBCXX_3.4.9 and GLIBC_2.7'. This is probably enabled by hosting provider but I don't really know. If it's not enabled your fonts won't work as expected. I don't know whether loading these fonts in your css will solve this problem, maybe it does.
- While debugging, if you have hidden javascript/css in your survey, this javascript/css will be dumped on the resultscreen. This can influence other elements on the resultscreen. You can add questioncodes to the 'Excluded questions' field in the configuration. These questioncodes will not be dumped and can't be passed to a template.



# Example

### Load example

If you don't want to copy everything to the required folders etc you have to activate this plugin, check the 'load demo' checkbox in the plugin configuration screen and then de-activate and re-activate the plugin (sorry about that workaround :)).

Now the demo survey (LimesurveyPdfEmailResultscreenPluginDemo) should be in your list of surveys, you only have to activate it.

NOTE: It may not work because of webserver permissions. Go to your limesurvey config file and set debug to 1. It will show you permissions errors (after disable and re-enable this plugin with the 'load demo' checkbox checked.

Now put this in your LimesurveyPdfEmailResultscreenPluginDemo config (this can't be preloaded):

- check 'Debug'
- check 'Create pdf'
- check 'Show download pdf text'
- put in the 'Download pdf text'-textbox: [p]You can download your pdf [[here]][/p]
- set 'Pdf template' to 'demo/pdf.html.twig' 
- set 'Pdf template folders' to 'demo' 
- keep 'Download folder' as '/download' (don't change)
- put in 'border=1cm | orientation=portrait' in 'Pdf configuration'
- check 'Pdf header'
- put in 'Pdf header content' the text: 'Your result'
- keep 'Pdf header content tag' as 'p'
- put in 'Pdf header content style' the following: 'color:blue;font-weight:900;'
- keep 'Pdf header height' as '1cm'
- check 'Pdf footer'
- put in 'Pdf footer content' the following: '{{pageNum}} / {{totalPages}}'
- keep 'Pdf footer content tag' as 'p'
- put in 'Pdf footer content style' the following: 'color:red;text-align:center;'
- keep 'Pdf footer height' as '1cm'
- check 'Show in result'
- set 'Result template' to resultscreen.html.twig
- set 'Result template folders' to 'demo' 
- do not check 'Send email'

activate survey and execute


After activating, fill out question 7 about watergymnastics and after submit you should see a resultscreen with dumped data and a barchart and a link to download a pdf. The barchart will not be styled the way it should. This is because there is a question called 'processingpopup', with javascript and css in it. This question creates a 'processing' popup after submitting. To fix this, you can go to the surve settings page and add 'processingpopup' to the 'Excluded questions' field. Save, and execute the survey again. Now the styling of the barchart is ok.


### Example explained

There are many ways to make use of these variables. This is just an example. There may be better solutions (like using javacript classes etc).

If you choose not to autoload these files you can find them in the demo folder. There is also a limesurvey demo survey which works with this example. You have to fill out question 7 (about watergymnastics) to see a barchart. In the css and js files the path where to drop them are in the first line.


##### Example: reusing scripts

I'd like to reuse some scripts. This can be done by creating a factory and calling it later by passing in configuration and data parameters. Suppose I create a template like this (let's call it chartfactory.js and put it in your yoursite/scripts/custom):

```
var chartfactory = {};

chartfactory.createBarChart = function(dataset, domelementid, title){

  /*
  NEEDS JQUERY
  */

  /*
  dataset should look like this:
    var dataset = [
      { label: 'label1', value: 10 }, 
      { label: 'label2', value: 20 }, 
      { label: 'label3', value: 30 }
    ];

    */

   var element = ('#'+domelementid);
  $(element)
  .append('<h6>'+title+'</h6>');

  $(element)
  .append('<div id="hoverbox'+domelementid+'"><p id="hoverboxkey'+domelementid+'"><strong>Important Label Heading</strong></p><p><span id="hoverboxvalue'+domelementid+'">100</span>%</p></div>')
  .find('svg')
  .first()
  .css({

    'display': 'block',
    'margin-left': 'auto',
    'margin-right': 'auto'    

  });

  $(element).find('#hoverbox'+domelementid)
  .css({

    'position': 'absolute',
    'width': '200px',
    'height': 'auto',
    'padding': '10px',
    'background-color': 'white',
    '-webkit-border-radius': '10px',
    '-moz-border-radius': '10px',
    'border-radius': '10px',
    '-webkit-box-shadow': '4px 4px 10px rgba(0, 0, 0, 0.4)',
    '-mox-box-shadow': '4px 4px 4px 10px rgba(0, 0, 0, 0.4)',
    'box-shadow': '4px 4px 10px rbga(0, 0, 0, 0.4) pointer-events: none',
    'display': 'none'

  }).find('p')
  .css({

    'margin': '0',
    'font-family': 'sans-serif',
    'font-size': '16px',
    'line-height': '20px'

  })

  
  var total = 0;

    for(var i = 0; i<dataset.length; i++ ){

      total += dataset[i].value;

    }

  var data = dataset;

  var margin = {top: 20, right: 20, bottom: 30, left: 40},
      width = parseInt(d3.select('#'+domelementid).style('width')),
      height = width - margin.top - margin.bottom;

  var x = d3.scale.ordinal()
      .rangeRoundBands([0, width], .1);

  var y = d3.scale.linear()
      .range([height, 0]);

  var xAxis = d3.svg.axis()
      .scale(x)
      .orient("bottom");

  var yAxis = d3.svg.axis()
      .scale(y)
      .orient("left")
      .ticks(5);

  var svg = d3.select("#"+domelementid).append("svg")
      .attr("width", width + margin.left + margin.right)
      .attr("height", height + margin.top + margin.bottom)
    .append("g")
      .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  

    x.domain(data.map(function(d) { return d.label; }));
    y.domain([0, d3.max(data, function(d) { return d.value; })]);

    svg.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    svg.append("g")
        .attr("class", "y axis")
        .call(yAxis)
      .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", ".71em")
        .style("text-anchor", "end")
        .text("Value");

    svg.selectAll(".bar")
        .data(data)
      .enter().append("rect")
        .attr("class", "bar")
        .attr("x", function(d) { return x(d.label); })
        .attr("width", x.rangeBand())
        .attr("y", function(d) { return y(d.value); })
        .attr("height", function(d) { return height - y(d.value); })

         .on("mouseenter", function (d) {

        var offset = $('#'+domelementid).offset();
  
        d3.select("#hoverbox"+domelementid)
          
          .style("left",  (d3.event.pageX - offset.left + 20)+'px' ) 
          .style("top", (d3.event.pageY - offset.top)+"px")   
          .style("display", "block")
          .style("z-index", "9999")
          .attr("pointer-events", "none")
          .select('#hoverboxkey'+domelementid)
          .text(function(){
            
              d3.select('#hoverboxvalue'+domelementid)
              .text(function(){
              
                return (d.value/total)*100;
              });

            return d.label;
          });
          
      })
      .on("mouseout", function () { 

        d3.select("#hoverbox"+domelementid)
          .style("display", "none")
          .attr("pointer-events", "none");
    });
 

  function type(d) {
    d.value = +d.value;
    return d;
  }



}

```



Just to prove overriding settings works create a markerquestion 'overridesettings':

``` {'headercontentstyle=color:blue;text-align:center; | downloadpdftext= [p]This a overriden downloadtext. You can download the pdf [link]here[/link][/p]'}```

Templates:

demo/resultscreen.html.twig (does not need bootstrap and jquery because this is allready loaded):

``` 
<div>
    <style type="text/css" scoped>

        /*could be in external css, just to show it can also be here*/

        #reusable1{
            width: 100%;
            height:auto;
        }
      
        .bar {
          fill: steelblue;
        }

        .bar:hover {
          fill: brown;
        }

        .axis {
          font: 10px sans-serif;
        }

        .axis path,
        .axis line {
          fill: none;
          stroke: #000;
          shape-rendering: crispEdges;
        }

        .x.axis path {
          display: none;
        }
    </style>
    <link rel='stylesheet' href='{!-baseurl-!}styles-public/custom/demo.css'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
    <script src="{!-baseurl-!}scripts/custom/chartfactory.js"></script>
    <h1>Results</h1>  
    <div class='row'>
        <div class='col-md-4'>
            <div id='reusable1'>            
            </div>
        </div>
        <div class='col-md-4'>
         could be another chart     
        </div>
        <div class='col-md-4'>
         could be another chart        
        </div>
    </div>
    <script>

    var q1 = {{nestedjson.q1 |raw}};
    var q2 = {{nestedjson.q2 |raw}};
    var q3 = {{nestedjson.q3 |raw}};
    var q4 = {{nestedjson.q4 |raw}};
    var q5 = {{nestedjson.q5 |raw}};
    var q6 = {{nestedjson.q6 |raw}};
    var q7 = {{nestedjson.q7 |raw}};
    var q8 = {{nestedjson.q8 |raw}};
    var q9 = {{nestedjson.q9 |raw}};
    var q10 = {{nestedjson.q10 |raw}};
    var q11 = {{nestedjson.q11 |raw}};

    var bardata = [];
    var q7title = '';

    for (var key in q7) {

        if(q7[key][2] === ''){

           q7[key][2] = 0;
        }

        if(q7title.length === 0){

            q7title = q7[key][0];

        }

        bardata.push({ label: q7[key][1].substring(1, q7[key][1].length -1) , value: parseInt(q7[key][2]) });

    }

    if(hasNotNull(bardata)){

        chartfactory.createBarChart(bardata, 'reusable1', q7title);

    }else{

        appendNoData([{id: 'reusable1', title: q7title}]);

    }

    function appendNoData(input){

        input.forEach(function(element){

            $('#'+element.id).append('<h6>'+element.title+'</h6><p>No data</p>');

        })

    }

    function hasNotNull(input){

        var isnotnull = false;

        input.forEach(function(element){

            if (element.value > 0){

                isnotnull = true;

            }

        })

        return isnotnull;

    }

    </script>
</div>

```


demo/pdf.html.twig (the same but you can use body, html and head because it's a standalone webpage. Loading bootstrap and jquery because it's not in the resultpage):


```

<html>
    <head>
        <style type="text/css" scoped>

        /*could be in external css, just to show it can also be here*/

        #reusable1{
            width: 100%;
            height:auto;
        }
      
        .bar {
          fill: steelblue;
        }

        .bar:hover {
          fill: brown;
        }

        .axis {
          font: 10px sans-serif;
        }

        .axis path,
        .axis line {
          fill: none;
          stroke: #000;
          shape-rendering: crispEdges;
        }

        .x.axis path {
          display: none;
        }
        </style>
        <link rel='stylesheet' href='{{baseurl}}styles-public/custom/demo.css'>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
        <script src="{{baseurl}}scripts/custom/chartfactory.js"></script>
    </head>
    <body>
        <h1>Results</h1>    
        <div class='row'>
            <div class='col-md-4'>
                <div id='reusable1'>         
                </div>
            </div>
            <div class='col-md-4'>
            could be another chart    
            </div>
            <div class='col-md-4'>
            could be another chart     
            </div>
        </div>
        <script>

        var q1 = {{nestedjson.q1 |raw}};
        var q2 = {{nestedjson.q2 |raw}};
        var q3 = {{nestedjson.q3 |raw}};
        var q4 = {{nestedjson.q4 |raw}};
        var q5 = {{nestedjson.q5 |raw}};
        var q6 = {{nestedjson.q6 |raw}};
        var q7 = {{nestedjson.q7 |raw}};
        var q8 = {{nestedjson.q8 |raw}};
        var q9 = {{nestedjson.q9 |raw}};
        var q10 = {{nestedjson.q10 |raw}};
        var q11 = {{nestedjson.q11 |raw}};

        var bardata = [];
        var q7title = '';

        for (var key in q7) {

            if(q7[key][2] === ''){

               q7[key][2] = 0;
            }

            if(q7title.length === 0){

                q7title = q7[key][0];

            }

            bardata.push({ label: q7[key][1].substring(1, q7[key][1].length -1) , value: parseInt(q7[key][2]) });

        }

        if(hasNotNull(bardata)){

            chartfactory.createBarChart(bardata, 'reusable1', q7title);

        }else{

            appendNoData([{id: 'reusable1', title: q7title}]);

        }

        function appendNoData(input){

            input.forEach(function(element){

                $('#'+element.id).append('<h6>'+element.title+'</h6><p>No data</p>');

            })

        }

        function hasNotNull(input){

            var isnotnull = false;

            input.forEach(function(element){

                if (element.value > 0){

                    isnotnull = true;

                }

            })

            return isnotnull;

        }

        </script>
    </body>
</html>

```



### Send an Email

To send an email you have to create an email marker question:

Question 'emailmarker' (equation type)

``` {'toemail={email}, another@example.com'}```

replace another@example.com with your own email. The plugin only sends to unique emails, so you won't send an email twice to the same email-adress (however, for now it wont send to exactly the same email adress, so it will send to both myexample@gmail.com and my.example@gmail.com).

In the demo survey there is a question named 'email'. I pass this variable to the 'toemail'-property using expression manager: (toemail={email}).

The variables you passed in the variable markerquestion can be used in your email template (remember not to use javascript in emails).

emailtemplates/standardmessage.html:

```
<div class="jumbotron" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;padding-top: 30px;padding-bottom: 30px;margin-bottom: 30px;color: inherit;background-color: #eee;padding-right: 60px;padding-left: 60px;border-radius: 6px;">
<h1 style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;margin: .67em 0;font-size: 63px;font-family: inherit;font-weight: 500;line-height: 1.1;color: inherit;margin-top: 20px;margin-bottom: 10px;">You completed your survey</h1>
<p>You provided this email: {{email}}</p>
</div>

```

I just pass in inline css because it's an email. (I used http://templates.mailchimp.com/resources/inline-css/ to inlinify it);

Now I have to enable sending an email in the plugin settings screen:

- check 'Send email'
- check 'Attach pdf'

Keep the rest as is.

Now an email should be send on survey complete. You must make sure that in your Configuration->global settings->tab Email settings the email settings is SMTP.
To use it with gmail (to test) you can set it to SMTP, SMTP host: smtp.gmail.com:465, username and pw of your gmail, SMTP encryption: SSL. After that you have to change your gmail to [Allowing less secure apps to access your account](https://support.google.com/accounts/answer/6010255).

Now answer the question about watergymnastics and provide your email in the email question. An email with a pdf attached should be sent.