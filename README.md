# Web Server Log
[![Code Climate](https://codeclimate.com/github/warlock39/webserverlog/badges/gpa.svg)](https://codeclimate.com/github/warlock39/webserverlog)

Implementation of Simple REST API that provides unified access to multiple log files on the server

Requirenments
=====
1. REST API that will allow the following:
    - retrieve the last 10/25/50/100 lines from all log files, sorted by date/time;
    - filter results by date and time;
    - filter results using simple search (an exact match).
2. Result pagination
3. Filtering results with a regular expression (i.e. user of API can provide a regex, that should be used to filter results).
4. Support for multiple filter occurencies (i.e. same filter can be applied multiple times).
5. User authentication against local *nix user database on the web-server.
    
Preconditions:

- Log files (multiple) are located in some directory on the web-server.
- Log format - Common Log Format (CLF), see http://httpd.apache.org/docs/2.4/mod/mod_log_config.html#formats


Idea & Algorithm
=======
Main idea is to separate logic into the two parts and make it asynchronous:
1. REST API that just reads logs from MySQL db. Db acts as cache layer
2. Console script that reads actual logs and stores them in DB


Technologies and implementation
===
Project is implemented using PHP language and Symfony 2.7 Framework. Main dependencies list is listed below:

- Doctrine ORM
- FOSRestBundle
- kassner/log-parser
- Symfony Serializer component

Other parts is implemented by project contributor


## REST API

Key point of REST API is Filtering mechanism.
Filters config should be specified as a service parameters in `config.yml,` example configuration:

```
    app.webserverlog_controller:
        filters:
            - datetime
            - text
            - { queryParam: datetimeBetween, fieldName: datetime, operator: between }
            - { queryParam: textRegex, fieldName: text, operator: regex }
            - { queryParam: textLike, fieldName: text, operator: like }
            - { queryParam: since, fieldName: datetime, operator: gt }
            - { queryParam: until, fieldName: datetime, operator: lt }
```

Filter is something that allows to filter results by field value using different operators.
You can specify only db table field name as a filter in config, in this case queryParam property will be equal to
field name, and operator by default is eq.
Currently next operators is supported (but easily can be extended):

- eq
- gt
- lt
- regex
- like
- between

Another major part of REST API is ability to use same filter multiple times per API-request. This feature works
automatically and not requires additional configuration. If you need to use this feature you just make HTTP
query-parameter as array like this:
 `?textRegex[]=[0-9]{3}&textRegex[]=(css|js)&datetime=2016-01-01 20:08:21`.  
Expression before will generate next SQL-part: 
`WHERE datetime=:dt AND (REGEXP(text, :user_regexp1) = 1 OR REGEXP(text, :user_regexp2) = 1)`

## LogsCollector

Asynchronous backend part is LogsCollector. It collects each log file within configured log directory on the server.
When there are non-log files exist in directory, they just skipped.
When file has `*.log` extension but not matches CLF-format, it also skipped. Log file considered non-CLF format when N
log lines is not match format regular expression. 

Collector has 3 dependency:

- persistence layer (doctrine EntityManager)
- LogReader
- LogParser

Doctrine is used to cache log lines in MySQL DB. Reading process is in batch: when X lines has been read, after that
data will be flushed. 

LogReader is custom component that allows:

- read file backwards
- read really huge log files, thanks to PHP Generators

LogReader reads log file from the end to begin or from the end until specified datetime. Last case is most frequently used.

LogReader uses PHP Generators and throws exceptions when it needs. Under certain conditions throwing an exception from
a generator produces a segfault. We have such conditions and we have segfault. 
It is PHP bug https://bugs.php.net/bug.php?id=71133 . So it is recommended not to store non-log files in log directory,
 in other case you will have segfault. 

LogParser is external part (kassner/log-parser) that parses each log line that has been read by LogReader
 and check for specified format. 



Installation
===

- `$ chmod 777 app/cache`
- `$ chmod 777 app/logs`
- edit `app/config/parameters.yml`

Usage and examples
===

Currently project is implemented as single symfony bundle, that is not suitable to use as separate component. 
So current implementation acts commonly as demonstration of how to solve problem specified in Requirements section and 
not indeed to use in production as is.

To start log collecting, run following console command:
`app/console logs-collector [logDir] [--keepMax=1 day]`
There are 1 optional argument and 1 option:

- logDir, self descriptive, absolute filesystem path (ex. `/var/log/access_log`), by default `app/logs`
- keepMax=1 day; max date range when collector should collect log entries. In other words it is cache expire time

When there are no log entries anymore, collector exits. So you should run this command periodically, by unix cron for example

### And several REST API examples:
Get logs that contains _localhost_ or _example_:

```
GET /logs?textLike[]=localhost&textLike[]=example
```

Get logs between two dates:
```
GET /logs?datetimeBetween[]=2016-01-31 12:00:00,2016-02-21 11:00:00
```

Get logs between two date ranges:
```
GET /logs?datetimeBetween[]=2016-01-01 12:00:00,2016-01-01 13:00:00&datetimeBetween[]=2016-01-02 15:00:00,2016-01-02 16:00:00
```

Get logs by exact field value match:
```
GET /logs?text=log_entry_full_text
```

Limit log results:
```
GET /logs?limit=50&offset=150
```

Get logs by even by several regular expression:
```
Get /logs?textRegex[]=[0-9]{3}&textRegex[]=jq[a-z]+js
```

TODO
===

1. Add logging
2. Add more verbose messages
3. Add authentication
4. Combine and unify filters configuration between custom yml config and FOSRestBundle ParamFetcher annotations
5. Consider log entry timezone 
6. Increase test coverage
7. Test Filters implementation for several entities
8. Ability to use annotations to create filters
9. Support for several collectors (workers) per directory
10. API-request, that supports access to records that older than max keeping entry

Prerequisites
===

- project based on symfony installer 
- symfony is not production configured
- no http-caching
- doctrine is not configured
- log reader is not format dependent
- symfony configuration probably does not follow best practices
- additional namespaces in bundle is the result of idea of separate component architecture, but not implemented yet
