# OS2Web Logging Drupal module [![Build Status](https://travis-ci.org/OS2web/os2web_logging.svg?branch=master)](https://travis-ci.org/OS2web/os2web_logging)

## Module purpose

The aim of this module is to provide logging of node access and NemID webform fields.

## How does it work

When node or NemID webform field is access this is logged by the module.

Logs are stored in two places: database + files.

After logs are enabled and configured, they are shown here: `/admin/reports/os2web-logging-access-logs`

File logs are saved in directory: `../logs/`

It is **required** that this directory exists and is writable.

## Additional settings
Settings are available under `admin/config/content/os2web-borgerdk`
* **Node types access to keep log of** - Select node type to keep logs of.
* **Webform fields access to keep log of** - Select webform fields type to keep logs of.
* **Log anonymous user actions** - If anonymous user actions are being logged
* **Store database logs for this period** - Database logs will be stored for the selected number of days, after that they will be automatically deleted (cleanup is done daily).
* **Store log files for this period** - Log file will be stored for the selected number of days, after that they will be automatically deleted
* **Store log files directory** - Logs will be saved in this path.

## Install

Module is available to download via composer.
```
composer require os2web/os2web_logging
drush en os2web_logging
```

## Update
Updating process for OS2Web Logging is similar to usual Drupal 8 module.
Use Composer's built-in command for listing packages that have updates available:

```
composer outdated os2web/os2web_logging
```

## Automated testing and code quality
See [OS2Web testing and CI information](https://github.com/OS2Web/docs#testing-and-ci)

## Contribution

Project is opened for new features and os course bugfixes.
If you have any suggestion or you found a bug in project, you are very welcome
to create an issue in github repository issue tracker.
For issue description there is expected that you will provide clear and
sufficient information about your feature request or bug report.

### Code review policy
See [OS2Web code review policy](https://github.com/OS2Web/docs#code-review)

### Git name convention
See [OS2Web git name convention](https://github.com/OS2Web/docs#git-guideline)
