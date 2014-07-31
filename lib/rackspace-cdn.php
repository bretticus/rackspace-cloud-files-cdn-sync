<?php

/**
 * Description of rackspace-cdn
 *
 * @author Brett Millett <bmillett@olwm.com>
 */
class rackspace_cdn {

    static private $_settings = array();

    static public function doSync() {

        $sapi = php_sapi_name();
        if ($sapi !== 'cli') {
            echo "This command must be ran from command line!";
            return FALSE;
        }

        // load settings ini file values if present
        self::$_settings = @parse_ini_file('settings.ini', TRUE);


        self::setSetting('api', 'username', 'Please enter your username', '^[a-zA-Z0-9]{6,16}$');
        self::setSetting('api', 'key', 'Please enter your API Key', '^[a-fA-F0-9]{32,}$');

        var_dump(self::$_settings);

        return;

        require realpath(dirname(__FILE__) . '/../vendor/autoload.php');

        $client = new OpenCloud\Rackspace(OpenCloud\Rackspace::US_IDENTITY_ENDPOINT, array(
            'username' => 'foo',
            'apiKey' => 'bar'
        ));

        return TRUE;
    }

    static private function setSetting($section, $setting, $prompt, $pattern) {

        $get_value = function($value = NULL) use ($prompt, $pattern) {

            $attempts = 5; // allow number of attempts before failing.
            $attempts_c = 1; // attempts counter
            // check initial value if supplied.
            if (!is_null($value) && !preg_match('/' . $pattern . '/', $value)) {
                $value = NULL;
            }
            while (is_null($value) && $attempts_c <= $attempts) {
                printf('[%d] %s: ', $attempts_c, $prompt);
                fscanf(STDIN, "%s\n", $value); // reads value from STDIN
                if (!preg_match('/' . $pattern . '/', $value)) {
                    $value = NULL;
                }
                $attempts_c++;
            }
            if ($attempts_c >= $attempts) {
                printf("Attempts to get setting have failed %d times. Bugging out!\n", $attempts);
                exit();
            }
            return $value;
        };

        $current_setting = (isset(self::$_settings[$section][$setting])) ? self::$_settings[$section][$setting] : FALSE;
        if ($current_setting === FALSE) {
            self::$_settings[$section][$setting] = $get_value;
        } else {
            self::$_settings[$section][$setting] = $get_value($current_setting);
        }
    }

}

rackspace_cdn::doSync();
