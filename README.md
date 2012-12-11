webim-php
=========

php version of webim.

##Implementation
Most deployments of php are LAMP. Apache is well known for its not suitable of long polling.
So this version use common polling to retrieve friends' status and messages.

To reduce the impact of polling and reduce mobile flow, friends' status is considered when polling.
If no friends are online, the polling interval will be much longer(15s) than the normal polling interval(3s).

To support mobile device, add this to all page:
    <meta name="viewport" content="width=device-width, inital-scale=1">

##Snapshot
###Chrome snapshot on PC
![Chrome snapshot on PC](https://raw.github.com/zenkj/webim-php/blob/master/snapshot/chrome.png)
###Android snapshot on Mobile Phone
![Android snapshot on Mobile Phone](https://raw.github.com/zenkj/webim-php/blob/master/snapshot/android.png)

##Issue
In some mobile browser(e.g. UCWeb), browser timer seems not work. One refresh button is added
to refresh new messages manually.
