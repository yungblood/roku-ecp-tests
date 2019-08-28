<?php
    function showHelp($scriptname) {
        die("\n".
            "Usage:\n".
            "  $scriptname <ip> [<channel> [<launch paramaters]]\n\n"
        );    
    }
    
    function configParms($_roku_dev = '', $_channel = 'dev', $_launchparms = '') {
        global $argv, $roku_dev, $channel, $launchparms, $scriptname, $logname;

        //Get scriptname for help screen, and logfile.
        $scriptname = basename($argv[0], '.php');
        $logname = $scriptname.date(".Ymd.His").".log";
        
        //Check if help was requested.
        if(in_array('--help', $argv)) showHelp($scriptname);
        
        //Make sure vars are setup.
        $roku_dev    = isset($argv[1]) ? $argv[1] : $_roku_dev;
        $channel     = isset($argv[2]) ? $argv[2] : $_channel;
        $launchparms = isset($argv[3]) ? $argv[3] : $_launchparms;
        if($roku_dev === '') showHelp($scriptname);
    }

    function exitError($msg) {
        global $logname;
        file_put_contents($logname, $msg, FILE_APPEND);
        die($msg);
    }
    
    function rokuCurl($method, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,  $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
    
//Roku ECP commands.
    function rokuApps() {
        global $roku_dev;
        return rokuCurl('GET', "http://$roku_dev:8060/query/apps");
    }
    function rokuActiveApp() {
        global $roku_dev;
        return rokuCurl('GET', "http://$roku_dev:8060/query/active-app");
    }
    function rokuKeydown($key) {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/keydown/$key");
    }
    function rokuKeyup($key) {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/keyup/$key");
    }
    function rokuKeypress($key) {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/keypress/$key");
    }
    function rokuLaunch($channel, $launchparms = '') {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/launch/$channel$launchparms");
    }
    function rokuInstall($channel, $launchparms = '') {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/install/$channel$launchparms");
    }
    function rokuDeviceInfo() {
        global $roku_dev;
        return rokuCurl('GET', "http://$roku_dev:8060/query/device-info");
    }
    function rokuAppIcon($channel) {
        global $roku_dev;
        return rokuCurl('GET', "http://$roku_dev:8060/query/icon/$channel");
    }
    function rokuInput($inputparms) {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/input/$inputparms");
    }
    function rokuSearch($searchparms) {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/search/browse$searchparms");
    }
    function rokuTvChannels() {
        global $roku_dev;
        return rokuCurl('GET', "http://$roku_dev:8060/query/tv-channels");
    }
    function rokuTvActiveChannel() {
        global $roku_dev;
        return rokuCurl('GET', "http://$roku_dev:8060/query/tv-active-channel");
    }
    function rokuTvLaunch($launchparms) {
        global $roku_dev;
        return rokuCurl('POST', "http://$roku_dev:8060/launch/tvinput.dtv$launchparms");
    }
    function testString($haystack, $needle) {
        global $testOk;
        if(strpos($haystack, $needle) === false) $testOk = 0;
        return $testOk;
    }
    function testNotString($haystack, $needle) {
        global $testOk;
        if(strpos($haystack, $needle) !== false) $testOk = 0;
        return $testOk;
    }
    
    function consoleClear(&$console) {
        do {
            $read   = array($console);
            $write  = NULL;
            $except = NULL;
            
            if(!is_resource($console)) return;
            $num_changed_streams = @stream_select($read, $write, $except, 1);
            if(feof($console)) return ;
            
            if($num_changed_streams === false) die("Crashed while ignoring previous console data");
            if($num_changed_streams > 0) {
                $data = fgets($console, 4096);
            }
        } while($num_changed_streams > 0);
    }
    
    function consoleScript(&$console, $script, $logname) {
        global $testOk, $scriptname;
        $testOk = 1;
        $scriptPos = 0;
        $scriptMax = count($script);
        do {
            $read   = array( $console);
            $write  = NULL;
            $except = NULL;
            
            if(!is_resource($console)) return;
            $num_changed_streams = @stream_select($read, $write, $except, null);
            if(feof($console)) return ;
            
            if($num_changed_streams === 0) continue;
            if($num_changed_streams === false) {
                exitError("Script crashed: $scriptname ($scriptPos)\n");
            } elseif ($num_changed_streams > 0) {
                $data = fgets($console, 4096);
                file_put_contents($logname, $data, FILE_APPEND);
                echo "$data";
                if(strpos($data, $script[$scriptPos]['expect']) !== false) {
                    switch($script[$scriptPos]['action']) {
                        case 'none':
                            break;
                        case 'testString':
                        case 'testNotString':
                            array_unshift($script[$scriptPos]['parms'], $data);
                        default:
                            call_user_func_array($script[$scriptPos]['action'], $script[$scriptPos]['parms']);
                    }
                    if($testOk === 0) {
                        exitError("Test $scriptname ($scriptPos) Failed.\n");
                    }
                    $scriptPos++;
                }
            }
        } while(($scriptPos < $scriptMax) && ($testOk === 1));
        if($testOk === 1) {
            echo "Test $scriptname Passed.";
        }
    }

//Configure Parms
    configParms($roku_dev, $channel, $launchparms);
        
//Open Roku console, and ignore initial input from previous channel launches.
    $console = fsockopen($roku_dev, 8085);
    if(!$console) die("Failed to open console connection to $roku_dev");
    consoleClear($console);
    
    file_put_contents($logname, "Testing Started: ".date("Y-m-d h:i:sa")."\n\n");
    
    rokuLaunch($channel, $launchparms);
    consoleScript($console, $script, $logname);
?>