webim-php
=========

php version of webim.

##Implementation
Most deployments of php are LAMP. Apache is well known for its not suitable of long polling.
So this version use common polling to retrieve friends' status and messages.

To reduce the impact of polling, friends' status is considered. If no friends are online,
the polling interval will be double of the normal polling interval.

To support mobile device, add this to all page:
    <meta name="viewport" content="width=device-width, inital-scale=1">

##Issue
In some mobile browser(e.g. UCWeb), browser timer seems not work. One refresh button is added
to refresh new messages manually.
