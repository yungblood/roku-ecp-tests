#!/usr/bin/php
<?php
###################################################################################
# Sample ECP Test + Logging
###################################################################################
# To make this script executable, type: chmod a+x sample-test.php
###################################################################################
#
# Config:
#   $roku_dev, $channel, and $launchparms can be set in test script as done below,
#   Or they can be overridden on the command line:
#   sample-test.php 10.16.181.8 dev "?contentId=sampleId&mediaType=episode"
#
###################################################################################
#
# Script:
#   Each line needs 'expect', 'action', and 'parms'.
#   'expect' searches within the console line.
#   'action' is the function name to call, or 'none'.
#   'parms'  is an array of values to be passed to the function in 'action'.
#
###################################################################################
#
# Notes:
#   Common ECP commands are already defined in test-functions.php.
#   Any other commands that you need should be defined by your script before
#   require(__DIR__ . '/test-functions.php');
#
#   The 2 $script lines in this sample test are required to ensure log closes when
#   user presses "Home".
#
#   $script is processed in the sequence listed.
#   If an expect is never found, script will wait indeffinately, or until user
#   presses ctrl-c.
#
#   If 'action' = 'testString', but the 'parms' value isn't also found in the 
#   same console line, the script will exit as a failed test.
#
#   If 'action' = 'testNotString', and the 'parms' value is also found in the
#   same console line, the script will exit as a failed test.
#
###################################################################################

    $roku_dev = '10.16.181.8';
    $channel = 'dev';
    $launchparms = '?contentId=l5ANMH9wM7kxwV1qr4u1xn88XOhYMlZX&mediaType=episode';
    
    $script = [
        [ 'expect' => 'AppExitComplete', 'action' => 'none', 'parms' => [] ],
        [ 'expect' => '________',        'action' => 'none', 'parms' => [] ]
    ];
    require(__DIR__ . '/test-functions.php');
?>