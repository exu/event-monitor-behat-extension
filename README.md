Event Monitor Behat Extension
=============================

The Event monitor Behat extension collects javascript events

Installation
------------

This extension requires:

-   Behat 2.4+
-   Mink 1.4+ with Selenium Driver
-   Selenium server (grid)

### Through Composer

1.  Set dependencies in your **composer.json**:

~~~~ {.sourceCode .js}
{
    "require": {
        ...
        "exu/event-monitor-behat-extension": "*"
    }
}
~~~~

2.  Install/update your vendors:

~~~~ {.sourceCode .bash}
$ curl http://getcomposer.org/installer | php
$ php composer.phar install
~~~~

Configuration
-------------

Activate extension in your **behat.yml** and define events which you
want to monitor:

~~~~ {.sourceCode .yaml}
# behat.yml
default:
  # ...
  extensions:
    EDP\EventMonitorExtension\Extension:
~~~~

### Settings

Set which events you want to monitor

~~~~ {.sourceCode .yaml}
# behat.yml
default:
  # ...
  extensions:
    EDP\JiraExtension\Extension:
      clicks: true
      keypresses: true
      focus: true
      blur: true
~~~~

Properies above are mapped to equivalnet javascript events

Source
------

[Github](https://github.com/exu/event-monitor-behat-extension)

Copyright
---------

Copyright (c) 2012 dacsoftware.pl. See **LICENSE** for details.

Contributors
------------

-   Jacek Wysocki [(exu)](http://github.com/exu)
