Event Monitor Behat Extension Experiment
========================================

The Event monitor Behat extension collects javascript events
Its not done yet. It is try to tame javasctipts into Behat-Mink-Selenium
technology.

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

~~~~ {.sourceCode .yaml}
# behat.yml
default:
  # ...
  extensions:
    EDP\EventMonitorExtension\Extension: ~
~~~~

Source
------

[Github](https://github.com/exu/event-monitor-behat-extension)

Bonus
-----

If you enable debug mode your behat will whrow some informations about his workflow.

![Super console outpu with debug mode enabled](http://i2.minus.com/ieTnzdFZzfhAA.png)



Copyright
---------

Copyright (c) 2012 dacsoftware.pl. See **LICENSE** for details.

Contributors
------------

-   Jacek Wysocki [(exu)](http://github.com/exu)
