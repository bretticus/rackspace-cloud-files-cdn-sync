<?php

/**
 * Description of rackspace-cdn
 *
 * @author Brett Millett <bmillett@olwm.com>
 */
class rackspace_cdn {

    static private $_settings = array();
    static private $_client = NULL;

    static public function doSync() {

        $sapi = php_sapi_name();
        if ($sapi !== 'cli') {
            echo "This command must be ran from command line!";
            return FALSE;
        }
        
        // autoloader for vendor
        require realpath(dirname(__FILE__) . '/../vendor/autoload.php');
        
        

        // load settings ini file values if present
        self::$_settings = @parse_ini_file('settings.ini', TRUE);

        // validate current settings and/or get required.
        self::setSetting('api', 'username', 'Please enter your username', '^[a-zA-Z0-9]{6,16}$');
        self::setSetting('api', 'key', 'Please enter your API Key', '^[a-fA-F0-9]{32,}$');
        self::setSetting('api', 'container', 'Please enter your container name', '^[a-zA-Z0-9]+$');
        self::setSetting('api', 'region', 'Please enter your containers region. (ie. DFW,IAD,ORD,LON,' .
                'HKG,SYD)', '^(IAD|ORD|DFW|LON|HKG|SYD)$');
        
        self::setSetting('files', 'path', 'Please enter the path to existing uploads folder or equivalent', '^[^\s]+$');
        
        self::setSetting('mysql', 'host', 'Please enter database hostname', '^[a-zA-Z0-9\.\-\_]+$');
        self::setSetting('mysql', 'host', 'Please enter database name', '^[a-zA-Z0-9\.\-\_]+$');
        self::setSetting('mysql', 'username', 'Please enter database username', '^[a-zA-Z0-9\.\-\_]+$');
        self::setSetting('mysql', 'username', 'Please enter database password', '^[^\s]+$');
        
        // default to US.
        $id_endpoint = OpenCloud\Rackspace::US_IDENTITY_ENDPOINT;
                
        // see if uk endpoint was set.
        if (isset(self::$_settings['api']['id_endpoint']) && strtolower(trim(self::$_settings['api']['id_endpoint'])) == 'uk' ) {
            $id_endpoint = OpenCloud\Rackspace::UK_IDENTITY_ENDPOINT;
        }

        // instatiate client
        self::$_client = new OpenCloud\Rackspace($id_endpoint, array(
            'username' => self::$_settings['api']['username'],
            'apiKey' => self::$_settings['api']['key']
        ));

        return TRUE;
    }

    static private function setSetting($section, $setting, $prompt, $pattern) {

        $get_value = function($value = NULL) use ($prompt, $pattern, $setting) {

            $attempts = 5; // allow number of attempts before failing.
            $attempts_c = 1; // attempts counter
            // check initial value if supplied.
            if (!is_null($value) && (is_string($value) && !preg_match('/' . $pattern . '/', $value))) {
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
                printf("Attempts to get %s setting have failed %d times. Bugging out!\n", $setting, $attempts);
                exit();
            }
            return $value;
        };

        $current_setting = (isset(self::$_settings[$section][$setting])) ? self::$_settings[$section][$setting] : FALSE;
        if ($current_setting === FALSE) {
            self::$_settings[$section][$setting] = $get_value();
        } else {
            self::$_settings[$section][$setting] = $get_value($current_setting);
        }
    }

}

rackspace_cdn::doSync();
