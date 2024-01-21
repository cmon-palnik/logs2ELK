# Apache logs parser to Elasticsearch (ELK)
The Symfony7/PHP8 app to monitor Apache instances. Retrieves traffic, error stream and sys stats and sends it **securely** to ELK. May be configured to do the thing **real time** or use eg. cron or logrotate to schedule periodical activity.

There are built-in Elastic-Kibana containers, dev container and an example Wordpress stack to show the process of installation & configuration.

Forked and refactored from [devouted/logParser](https://github.com/devouted/logParser) 

## Types of indexes
* INDEX and WPINDEX – traffic for non-WordPress and WordPress applications
* ERRORS and WPERRORS – respective error streams
* APPSYS – connection statistics
**NOTE:** There are separate parsers for WP to retrieve more details (eg. error class)

## Getting started
There are several methods of running the application. To bring up an example just run:
```bash
$ ./dev-compose-example [up --build]
```
The default argument is *up*, you may pass anything you want, eg.
```bash
$ ./dev-compose-example down
```
There are also two other quick-start commands: `$ ./dev-compose` for dev container & ELK and `$ ./dev-compose-no-elk` for bare dev container (eg. to use external ELK connection)

## Example
Once all containers are launched, go to http://localhost:8080. There's a WP instance with error generator injected. Clicking through the site you'll make some traffic and errors you may see live @ http://localhost:5601 (Kibana).

## Manual use and CLI args
You may always use `$ ./bin/console` or `php bin/console` in the app folder **OR** after the installation, `$ /usr/local/bin/logs2elk` command is available which shortens to `$ logs2elk` as the folder is in $PATH.
```bash
$ logs2elk logs2elk:<command> <indexType> <environmentType> <applicationName>
```
The commands are:
```bash
logs2elk:parse              Parse log (from stdin) and index it in Elk
logs2elk:report             Generate reports
logs2elk:sysstat            Parse and index current sysstat
```
so to parse a specific xxx.log use:
```bash
cat xxx.log | logs2elk logs2elk:parse <args>
```
* See [.env](https://github.com/cmon-palnik/logs2ELK/blob/main/.env) and [ConfigLoader](https://github.com/cmon-palnik/logs2ELK/blob/main/src/ConfigLoader.php) to find variables & their processing to set the default parameters. 
* The allowed environment types may be found in [Env](https://github.com/cmon-palnik/logs2ELK/blob/main/src/Environment/Type/Env.php) 
* There is an argument autocompletion implemented, but first you have to install the completion script once. Run bin/console completion --help for the installation instructions for your shell.

## Some important details
* Apache logs MUST be reconfigured to be JSONs. See [log.conf](https://github.com/cmon-palnik/logs2ELK/blob/main/docker/example/log.conf) and [vhost.conf](https://github.com/cmon-palnik/logs2ELK/blob/main/docker/example/vhost.conf) for details.
* Analyse [example Dockerfile](https://github.com/cmon-palnik/logs2ELK/blob/main/docker/example/Dockerfile) to get to know how to install Logs2ELK in your own environment
* There is an app log and error listener 4 further dev & debugging

## @TODO
* set of basic tests
* align time zones
* parsing Monolog
* default view in Kibana
* a script to scale the environment (and set appropriate parameters for Logs2ELK)
* move parsers into separate classes 
* change the method of passing parameters for parsers
* recognize error classes in a listener

## License
[MIT](https://github.com/cmon-palnik/logs2ELK/blob/main/LICENSE)
