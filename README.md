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

First make sure you have limesurvey 2.05 or higher installed or 2.06 or higher for cronjob support.

### Install PhantomJS

Install PhantomJS on your server or developmentbox (option 1) or get the binary and place it in your app (option 2, the shared hosting way).

#### Option 1: Install on your server or local machine

Google how to and make sure you know the path to phantomjs(.sh). For ubuntu see the previously mentioned [thread](https://gist.github.com/julionc/7476620). This path is what you have to input later on to make it work.


#### Option 2: Get binary and drop in app

http://phantomjs.org/download.html provides [download binaries](http://phantomjs.org/download.html). Find a way to determine which one you need. Now create a folder named 'phantomjs' in your rootfolder (sibling to the application-folder), and put in the folder named bin which you unpacked from the downloaded binary (you can put in all the other stuff but the bin-folder is the required one).


### Create a download folder

Create a download folder in the root folder (sibling to the application-folder). If you are using git an empty folder won't be pushed so create a dummy file in it to force it. Set permissions on the folder. Maybe start with 777 to test and restrict later.


### Install H2P

Do a composer require kriansa/h2p:dev-master or put "kriansa/h2p": "dev-master" in the require path of your composer.json and run composer update or install. The dev-master version is very important!. (Note to self: For OpenShift you may need to to downgrade some dependencies because it runs on php 5.4. Which ones you can see in the console while deploying).
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

```
{'showinresult=true| createpdf=true|resulttemplate=resultpagehandler.html| pdftemplate=pdfhandler.html|variables=q1,q2,q3,q4,q5,q6,q7,q8,q9,q10,q11 | parsenested=true'}

```


Parameters explained:

- showinresult: Mandatory. This will be added to the resultpage when set to true.
- createpdf: Mandatory. This will create a pdf and will print a downloadlink on the resultpage.
- pdftemplate: Mandatory. Set empty (pdftemplate=) when not needed. Must be placed in 
- resulttemplate: Mandatory. Set empty (resulttemplate=) when not needed
- variables. Mandatory. Comma separated variable names (question codes).

- parsenested.: Optional (recommended). When set to true, you don't have pass all subquestion variables. For example: You can pass q1 as variable and q1_SQ01 gets parsed as a json object.


NOTE: every string with 'http' in it will be parsed without quotes.

One variable will always be available: baseurl. This is for your convenience because you can load css and javascript using this variable:
for example: src="{!-baseurl-!}js/somejavascript.js". 


If you create one markerquestion at the end you can set javascript variables and use that variable to do things.

IMPORTANT: limesurvey tries to parse strings enclosed in curly brackets when there are no spaces directly after the opening and before the closing curly bracket in the result page. This wil affect your javascript. The workaround is to always have a space after the opening and before the closing bracket. So: var myObject = { key: value } (note the spaces);

You can set different templates for the resultpage and for the pdfpage. This is because you may need to tweak your html and css to make your pdf look nice. Another reason is that limesurvey (2.5) has JQuery and Bootstrap allready loaded. Now you can only load these libraries in your pdf template only.



You can also configure the pdf. This is also done by a markerquestion. This question has to contain the string 'pdfconfig'.

Example:

```
{'footerheight=2cm|footercontent={ { pageNum } } / { { totalPages } }|orientation=landscape|border=2cm|footercontenttag=h1|footercontentstyle=color:red;background-color:blue;'}

```

Explanation: It's just as explained in https://github.com/kriansa/h2p, but the only difference is you have to pass footercontent and footerheight and headercontent and headerheight because it is a nested array. You can also pass inline styles to headercontenttag and footercontenttag as attributes headercontentstyle and footercontentstyle to style. The text will be wrapped in a tag you provide with the inline style you provide. Mind the spaces between the brackets to prevent parsing by expression manager.

### Templates

Templates should be in the folder : plugins/PdfGenerator/templates

These templates can also be placed in a subfolder, just pass it to your resulttemplate and pdftemplate parameters (pdftemplate=mysubfolder/mypdftemplate.html).

As stated in the previous section, passed variables replace that same variable name between '{!-' and '-!}'.

For instance:

```
var question1 = {!-question1-!};
var question2 = {!-question2-!};

```

Now you have your survey parameters available in your template. From here you can do your frontend magic. See Example below.

# Debugging

Always test your pdf template on the resultsreen first. If some external css or javascript is not found, phantomjs will propably fail without any meaningfull errors. In the resultscreen you can monitor those errors in your console. Set createpdf to false.

# Quirks:

-It's not always rendered the way you want so test and try to fix it, don't assume it will be perfect right away. Google for phantomJs and your problem.



# Example

There are many ways to make use of these variables. I will give an example. There may be better solutions (like using javacript classes etc).

In the example folder you will find the files you need. There is also a limesurvey demo survey which works with this example. You have to fill out question 7 (about watergymnastics) to see a barchart. In the css and js files the path where to drop them are in the first line.


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
       
        console.log('mouseenter trggeredd');
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

        console.log('mouseout trggeredd');
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


After that you can create a markerfile which loads and uses this javascript file:

Markerfile

``` {'showinresult=true| createpdf=true |resulttemplate=demo/resultscreen.html | pdftemplate=demo/pdf.html|variables=q1,q2,q3,q4,q5,q6,q7,q8,q9,q10,q11,pdf |parsenested=true'}```

Just to prove the pdf config works create a markerquestion 'pdfconfig':

``` {'footerheight=2cm|footercontent={ { pageNum } } / { { totalPages } }|orientation=landscape|border=2cm|footercontenttag=h1|footercontentstyle=color:red;background-color:blue;'}```

Templates:

demo/resultscreen.html (does not need bootstrap and jquery because this is allready loaded):

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

        chartfactory.createBarChart(piedata, 'reusable1', q7title);

    }else{

        appendNoData([{id: 'reusable1', title: q7title}]);

    }

    chartfactory.createBarChart(bardata, 'reusable1', q7title);

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


demo/pdf.html (the same but you can use body, html and head because it's a standalone webpage. Loading bootstrap and jquery because it's not in the resultpage):


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
        <link rel='stylesheet' href='{!-baseurl-!}styles-public/custom/demo.css'>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css'>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
        <script src="{!-baseurl-!}scripts/custom/chartfactory.js"></script>
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

            chartfactory.createBarChart(piedata, 'reusable1', q7title);

        }else{

            appendNoData([{id: 'reusable1', title: q7title}]);

        }

        chartfactory.createBarChart(bardata, 'reusable1', q7title);

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







