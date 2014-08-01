<?php
/**
 * Description of rackspace-cdn
 *
 * @author Brett Millett <bmillett@olwm.com>
 */

/**
 * @property OpenCloud\Rackspace $_client
 */
class rackspace_cdn {

    static private $_settings = array();
    static private $_client = NULL;
    static private $_objects = array();

    /**
     * Main method for performing the sync
     *
     * @return boolean
     */
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
        self::setSetting('api', 'username', 'Please enter your username (between 6-16 chars)', '/^[a-zA-Z0-9]{6,16}$/');
        self::setSetting('api', 'key', 'Please enter your API Key (32 char alphanum hash)', '/^[a-fA-F0-9]{32,}$/');
        self::setSetting('api', 'container', 'Please enter your container name', '/^[a-zA-Z0-9]+$/');
        self::setSetting('api', 'region', 'Please enter your containers region. (ie. DFW,IAD,ORD,LON,' .
                'HKG,SYD)', '/^(IAD|ORD|DFW|LON|HKG|SYD)$/');

        self::setSetting('files', 'path', 'Please enter the path to existing uploads folder or equivalent (/path/to/files)', function($path) {
            return file_exists($path);
        });

        self::setSetting('mysql', 'host', 'Please enter database hostname', '/^[a-zA-Z0-9\.\-\_]+$/');
        self::setSetting('mysql', 'host', 'Please enter database name', '/^[a-zA-Z0-9\-\_]+$/');
        self::setSetting('mysql', 'username', 'Please enter database username (up to 16 ascii chars)', function($username) {
            return (ctype_print($username) && strlen($username) <= 16);
        });
        self::setSetting('mysql', 'username', 'Please enter database password (ascii chars only)', function($password) {
            return ctype_print($password);
        });

        // default to US.
        $id_endpoint = OpenCloud\Rackspace::US_IDENTITY_ENDPOINT;

        // see if uk endpoint was set.
        if (isset(self::$_settings['api']['id_endpoint']) && strtolower(trim(self::$_settings['api']['id_endpoint'])) == 'uk') {
            $id_endpoint = OpenCloud\Rackspace::UK_IDENTITY_ENDPOINT;
        }

        // instatiate client
        self::$_client = new OpenCloud\Rackspace($id_endpoint, array(
            'username' => self::$_settings['api']['username'],
            'apiKey' => self::$_settings['api']['key']
        ));



        try {
            // get instance of our object store
            $objectStoreService = self::$_client->objectStoreService(null, self::$_settings['api']['region']);

            // get instance on our container
            $container = $objectStoreService->getContainer(self::$_settings['api']['container']);

            // Setup directory iterator for files to consider for upload
            $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(self::$_settings['files']['path'], RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
            );

            // Loop through files and find out if they need uploading
            foreach ($files as $file) {
                $real_path = $file->getRealPath();
                if (!is_dir($real_path)) {
                    $filename = str_replace(realpath(self::$_settings['files']['path']), '', $real_path);
                    $dirname = dirname($filename);
                    if (!isset(self::$_objects[$dirname])) {
                        // get list of prefix only
                        $options = array(
                            'prefix' => $dirname
                        );
                        self::$_objects[$dirname] = $container->objectList($options);
                        foreach (self::$_objects[$dirname] as $object) {
                            printf("Object name: %s\n", $object->getName());
                        }
                    }
//                    if ($container->objectExists($filename)) {
//                        $object = $container->getObject($filename);
//                        printf('File with name: %s exists in object container: %s.' . PHP_EOL, $object->getName(), $container->name);
//                    } else {
//                        printf('File with name: %s does not exist in object container: %s.' . PHP_EOL, $filename, $container->name);
//                    }
                }
            }
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
            $status_code = $e->getResponse()->getStatusCode();
            switch ($status_code) {
                case 401:
                    echo 'Unauthorized: Please check your username and API key.' . PHP_EOL;
                    break;
                case 404:
                    echo 'Not Found: Please check your container setting.' . PHP_EOL;
                    break;
                default:
                    echo 'A connection error occurred:' . $e->getResponse()->getReasonPhrase() . PHP_EOL;
                    break;
            }

            return FALSE;
        } catch (UnexpectedValueException $e) {
            echo 'Please check your file path. Error: ' . $e->getMessage() . PHP_EOL;
        } catch (Exception $e) {
            echo 'An error ocurred: ' . $e->getMessage() . PHP_EOL;
        }

        return TRUE;
    }

    static private function setSetting($section, $setting, $prompt, $validator) {

        // make a preg_match closure otherwise
        if (!(is_object($validator) && ($validator instanceof Closure))) {
            $pattern = $validator;
            $validator = function($value) use ($pattern) {
                return preg_match($pattern, $value);
            };
        }

        $get_value = function($value = NULL) use ($prompt, $validator, $setting) {

            $attempts = 5; // allow number of attempts before failing.
            $attempts_c = 1; // attempts counter
            // check initial value if supplied.
            if (!is_null($value) && (is_string($value) && !$validator($value))) {
                var_dump($validator);
                var_dump($value);
                $value = NULL;
            }
            while (is_null($value) && $attempts_c <= $attempts) {
                printf('[%d] %s: ', $attempts_c, $prompt);
                fscanf(STDIN, "%s\n", $value); // reads value from STDIN
                if (!$validator($value)) {
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
