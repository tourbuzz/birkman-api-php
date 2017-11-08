# Birkman Slack Bot

Using Birkman XML API

Mostly we're using GetAssessmentReport to get report data, with this CURL:

curl -H "Content-type: text/xml" --data-binary @GetAssessmentReport.xml https://api.birkman.com/xml-3.0-report.php

It's wrapped up in a BirkmanAPI class.

# Tech Stack

A barebones PHP app that makes use of the [Silex](http://silex.sensiolabs.org/) web framework, which can easily be deployed to Heroku.

This application supports the [Getting Started with PHP on Heroku](https://devcenter.heroku.com/articles/getting-started-with-php) article - check it out.

## Local Development

```sh
$ composer install
$ export BIRKMAN_API_KEY=... SLACK_TOKEN=...
$ php72 -S 0.0.0.0:8888 -t web/
```

## Deploying

Install the [Heroku Toolbelt](https://toolbelt.heroku.com/).

```sh
$ heroku create
$ heroku config:set BIRKMAN_API_KEY=... SLACK_TOKEN=...
$ git push heroku master
$ heroku open
```

or

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

## Documentation

For more information about using PHP on Heroku, see these Dev Center articles:

- [Getting Started with PHP on Heroku](https://devcenter.heroku.com/articles/getting-started-with-php)
- [PHP on Heroku](https://devcenter.heroku.com/categories/php)
