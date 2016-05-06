# limesurvey-plugin-pdfcreator

A flexible limesurvey pdfcreator

# Overview:

This is a limesurvey plugin to create a downloadable pdf after a respondent completes a survey. 

Because as far as I know, there is no option for a plugin to add functionality to the backend (ie It's not possible to create buttons with options, except for coding into the core, which would be erased after each update), this plugin uses hidden marker questions to configure. These hidden markerquestions can also supply javascript (to create, for instance, d3.js charts).

### Important

This is **NOT** a simple plug & play plugin. To make it function, you need to do some work. The main reason for this is that this plugin uses [KpnLab's snappy library](https://github.com/KnpLabs/snappy "Snappy"), which in turn makes use of [wkhtmltopdf](http://wkhtmltopdf.org/ "wkhtmltopdf"). You can install wkhtmltopdf on your server (see for instance for ubuntu-server [this thread](http://askubuntu.com/questions/556667/how-to-install-wkhtmltopdf-0-12-1-on-ubuntu-server). However, on shared hosting you most probably can't do that so you'll need to run a precompiled binary.

On the other hand, wkhtmltopdf makes use of [Qt WebKit](https://wiki.qt.io/Qt_WebKit "Qt WebKit"), which makes it possible to really render pages using javascript, html and css. This is something (as far as I know) pure php-pdf-creators don't do.

# Getting started

First make sure you have limesurvey 2.05+ installed

### Install wkhtmltopdf

Install wkhtmltopdf on your server or developmentbox or get the binary and place it in your app (the shared hosting way).

#### Install on your server or local machine

Google how to and make sure you know the path to wkhtmltopdf.sh. For ubuntu (server/this was working fine on my ubuntu 14.04 lts box) see the previously mentioned [thread](http://askubuntu.com/questions/556667/how-to-install-wkhtmltopdf-0-12-1-on-ubuntu-server). This path is what you have to input later on to make it work.


#### Get binary and drop in app

http://wkhtmltopdf.org/ provides [downloadable precompiled binaries](http://wkhtmltopdf.org/downloads.html). Find a way to determine which one you need. For me the the generic stable linux version (wkhtmltox-0.12.3_linux-generic-amd64.tar.xz) works on OpenShift(RedHat). Now create a folder in your rootfolder (sibling to the application-folder), and put in the folders bin, include, lib, and share which you unzipped from the downloaded binary.


### Create a download folder

Create a download folder in the root folder (sibling to the application-folder). If you are using git an empty folder won't be pushed so create a dummy file to force it. Set permissions on the folder. Maybe start with 777 to test and restrict later.


### Install Snappy

Do a composer require knplabs/knp-snappy. (For OpenShift you'll need to to downgrade some dependencies because it runs on php 5.4. Which ones you can see in the console while deploying).
Now you should have a 'vendor' folder in your limesurvey rootfolder.


### Install limesurvey-plugin-pdfcreator

Drop the PdfGenerator folder in your plugins folder. 


### Activate pdfGenerator

Go to your pluginmanager page in limesurvey and activate pdfGenerator.
Now you should be good to go!


# Configuration

Because limesurvey does not allow to configure a plugin, this will be done with hidden marker questions

# Using marker questions














# Quirks:

Pages with relative links break the rendering. See [this](https://github.com/wkhtmltopdf/wkhtmltopdf/issues/2713). So not every webpage can be expected to render. For instance: Google does not work, but https://www.limesurvey.org/ and https://github.com/ do work. This is not a real problem because this plugin is intended to create you own content (without relative links offcourse);

