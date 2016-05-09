# limesurvey-plugin-pdfcreator (beta)

A flexible limesurvey pdfcreator

# Overview

This is a limesurvey plugin to create a downloadable pdf after a respondent completes a survey and show this content in the completed page. 

Because as far as I know, there is no option for a plugin to add functionality to the backend (ie It's not possible to create buttons with surveyspecific options, except for coding into the core, which would be erased after each update), this plugin uses its configure page to configure. In the configuration page you have to enter your survey name, and below the variables you need in your charts. 
You also have to provide templates (html/javascript/css) and upload them to a folder.  In these templates wrap your variables in ##yourvariablename##.

### Important

This is **NOT** a simple plug & play plugin. To make it function, you need to do some work. The main reason for this is that this plugin uses [kriansa's h2p library](https://github.com/kriansa/h2p "H2p"), which in turn makes use of [PhantomJS](http://phantomjs.org/ "PhantomJS"). You can install PhantomJS on your server (see for instance for ubuntu [this](https://gist.github.com/julionc/7476620)). However, on shared hosting you most probably can't do that so you'll need to run a precompiled binary.

On the other hand, PhantomJS makes use of [Qt WebKit](https://wiki.qt.io/Qt_WebKit "Qt WebKit"), which makes it possible to really render pages using javascript, html and css, including fonts etc. This is something (as far as I know) pure php-pdf-creators don't do.

# Getting started

First make sure you have limesurvey 2.05+ installed or 2.06+ for cronjob support.

### Install PhantomJS

Install PhantomJS on your server or developmentbox (option 1) or get the binary and place it in your app (option 2, the shared hosting way).

#### Option 1: Install on your server or local machine

Google how to and make sure you know the path to phantomjs.sh. For ubuntu see the previously mentioned [thread](https://gist.github.com/julionc/7476620). This path is what you have to input later on to make it work.


#### Option 2: Get binary and drop in app

http://phantomjs.org/download.html provides [download binaries](http://phantomjs.org/download.html). Find a way to determine which one you need. Now create a folder in your rootfolder (sibling to the application-folder), and put in the folders bin, include, lib, and share which you unzipped from the downloaded binary.


### Create a download folder

Create a download folder in the root folder (sibling to the application-folder). If you are using git an empty folder won't be pushed so create a dummy file in it to force it. Set permissions on the folder. Maybe start with 777 to test and restrict later.


### Install H2P

Do a composer require kriansa/h2p. (For OpenShift may need to to downgrade some dependencies because it runs on php 5.4. Which ones you can see in the console while deploying).
Now you should have a 'vendor' folder in your limesurvey rootfolder.


### Install limesurvey-plugin-pdfcreator

Drop the PdfGenerator folder in your plugins folder. 


### Activate pdfGenerator

Go to your pluginmanager page in limesurvey and activate pdfGenerator. If you decided to use another path for your download or PhantomJS folder you can hit configure and chance settings. If you installed PhantomJS on your machine you can change the path also in the configure screen (In this case don't forget to uncheck the 'You dropped in the PhantomJS precompiled library'-checkbox.  Also you can set after what time a pdf will be deleted. Default is 60 minutes.
Now you should be good to go!


### Activate cron (optional)

Everytime a pdf is generated, the plugin will check if files should be deleted because the time they will be stored (according to your configuratioon) ran out. This will only be done when a new pdf is created, so if your survey is not used very often these files will remain in the download folder. With a cron you can periodically check for files which should be deleted.
Just fire a php cli command php yourlimesurveydir/application/commands/console.php plugin cron --interval= < the same value as after which downloads are deleted > 
If you allready have a cron running you don't have to create another one. The plugin will be triggered by that other cron.



# Configuration

Because limesurvey does not allow to configure a plugin on the survey level, this will be done in the configuration page.

### Global config

Path to phantomjs: This is set to the second option (dropin phantomjs). If you followed the steps above you shouldn't have to change this (if you do change, mind the '/' at the start).

Download folder: If you followed the steps above you shouldn't have to change this (if you do change, mind the '/' at the start).

Delete generated pdf after amount of minutes: This will cleanup files after x minutes.

### Templates

Each template has a Templatesurveyname an templatename. This must be comma-seperated: surveyname, templatename. 
The following option gives you the variables you want to use in your template. These variable names are the same as your question code.

In your template now you have to refer to those variables as: ##variablename##. 

For instance:

You have a survey named 'mysurvey', a template named 'mytemplate.html' and two question with code 'question1' and code 'question2';

Now you enter:  mysurvey,mytemplate.html     in the 'Survey Name and template name'-box.

In the next 'variablenames'-box you enter:   question1,question2

Now, in your template you can reference them like this:

var question1 = ##question1##;
var question2 = ##question2##;

Beware: There must be a value because otherwise it will result in an error (Uncaught SyntaxError: Unexpected token ILLEGAL). 
Also beware that some values may be passed as string while you actually need an integer (so use var question1 = parseInt(##question1##(); in that case).




















# Quirks:

It's not always rendered the way you want so test and try to fix it, don't assume it will be perfect right away. Google for phantomJs and your problem. 

