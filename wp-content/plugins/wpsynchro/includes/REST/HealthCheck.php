<?php

namespace WPSynchro\REST;

use WPSynchro\CommonFunctions;
use WPSynchro\Masterdata\MasterdataRetrieval;
use WPSynchro\Transport\TransferToken;
use WPSynchro\Transport\TransferAccessKey;
use WPSynchro\REST\MasterData;
use WPSynchro\Logger\NullLogger;
use WPSynchro\Initiate\InitiateTokenRetrieval;
use WPSynchro\InstallationFactory;
use WPSynchro\Licensing;
use WPSynchro\Transport\BasicAuth;

/**
 * Class for handling REST service "healthcheck"
 * Call should already be verified by permissions callback
 *
 * @since 1.1
 */
class HealthCheck
{
    public $healthcheck_errors;
    private $healthcheck_documentation_url = 'https://wpsynchro.com/documentation/health-check-errors';

    public function __construct()
    {
        $this->healthcheck = new \stdClass();
        $this->healthcheck->errors = [];
        $this->healthcheck->warnings = [];
    }

    public function service($request)
    {
        global $wpdb;

        // Get methods and execute the tests
        $check_methods = $this->getTestFunctions();
        foreach ($check_methods as $method) {
            $this->$method();
            if (count($this->healthcheck->errors) > 0) {
                break;
            }
        }

        // If no errors or warnings, set timestamp in database
        if (count($this->healthcheck->errors) == 0) {
            update_site_option("wpsynchro_healthcheck_timestamp", time());
        }

        return new \WP_REST_Response($this->healthcheck, 200);
    }

    /**
     *  Get functions to test
     */
    public function getTestFunctions()
    {
        // Find test functions
        $class_methods = get_class_methods($this);
        $check_methods = [];
        foreach ($class_methods as $method) {
            if (strpos($method, 'check') === 0) {
                $check_methods[] = $method;
            }
        }
        return $check_methods;
    }

    /**
     *  Check environment, WP/PHP/SQL
     */
    public function checkEnvironment()
    {
        $commonfunctions = new CommonFunctions();
        $errors_from_env = $commonfunctions->checkEnvCompatability();
        if (count($errors_from_env) > 0) {
            $this->healthcheck->errors = array_merge($this->healthcheck->errors, $errors_from_env);
        }
    }

    /**
     *  Check that database is current, but not newer
     */
    public function checkDatabaseIsCurrent()
    {
        $dbversion = get_option('wpsynchro_dbversion');
        if (!$dbversion || $dbversion == "") {
            $dbversion = 0;
        }
        if ($dbversion > WPSYNCHRO_DB_VERSION) {
            $this->healthcheck->errors[] = __("WP Synchro database version is newer than the currently installed plugin version - Please upgrade plugin to newest version - Continue at own risk", "wpsynchro");
        }
    }

    /**
     *  Check that local installation has access key set
     */
    public function checkAccessKeyIsSet()
    {
        $accesskey = TransferAccessKey::getAccessKey();
        if (strlen(trim($accesskey)) < 20) {
            $this->healthcheck->errors[] = __("Access key for this site is not set - This needs to be configured for WP Synchro to work.", "wpsynchro");
        }
    }

    /**
     *  Check proper PHP extensions
     */
    public function checkPHPExtensions()
    {
        $required_php_extensions = ["curl", "mbstring", "openssl", "mysqli"];
        $php_extensions_loaded = get_loaded_extensions();
        $missing_extensions = [];
        foreach ($required_php_extensions as $required_php_extension) {
            if (!in_array($required_php_extension, $php_extensions_loaded)) {
                $missing_extensions[] = $required_php_extension;
            }
        }
        if (count($missing_extensions) > 0) {
            $this->healthcheck->errors[] = sprintf(__("Missing PHP extensions for WP Synchro to work. Enable extension(s) '%s' to php.ini and reload.", "wpsynchro"), implode(", ", $missing_extensions));
        }
    }

    /**
     * Check that sql max_allowed_packet is set to something proper
     */
    public function checkSQLMaxAllowPacket()
    {
        global $wpdb;
        $max_allowed_packet = (int) $wpdb->get_row("SHOW VARIABLES LIKE 'max_allowed_packet'")->Value;
        if ($max_allowed_packet < 1024) {
            $this->healthcheck->errors[] = sprintf(
                __("Your database server is misconfigured - The setting 'max_allowed_packet' is too low. It is currently set to: %d. Check out the documentation for the SQL server you are using and correct this setting.", "wpsynchro"),
                $max_allowed_packet
            );
        }
    }

    /**
     *  Check that permalink structure is NOT plain
     */
    public function checkPermalinkStructure()
    {
        $permalink_structure = get_option('permalink_structure');
        if (trim($permalink_structure) == "") {
            $this->healthcheck->errors[] = __("Plain permalinks is not supported in WP Synchro. You should change it to %postname% instead", "wpsynchro");
        }
    }

    /**
     *  Check that SAVEQUERIES are not active
     */
    public function checkSaveQueries()
    {
        if (defined("SAVEQUERIES") && SAVEQUERIES == true) {
            $this->healthcheck->errors[] = __("SAVEQUERIES constant is set. This is normally only for debugging. It will generate out of memory errors with WP Synchro synchronizations", "wpsynchro");
        }
    }

    /**
     *  Check license okay, if PRO
     */
    public function checkLicenseIfPRO()
    {
        if (\WPSynchro\CommonFunctions::isPremiumVersion()) {
            $licensing = new Licensing();
            if ($licensing->hasProblemWithLicensing()) {
                $this->healthcheck->errors[] = $licensing->getLicenseErrorMessage();
            }
        }
    }

    /**
     *  Check that multiple connections to local services can be done - LocalWP problems most of time or misconfigured hosting
     */
    public function checkMultipleConnections($http_type = 'GET')
    {
        $multiple_connection_test_url = get_rest_url(null, '/wpsynchro/v1/test/');

        $args = [
            'method' => $http_type,
            'redirection' => 0,
            'timeout' => 5,
            'sslverify' => false,
            'headers' => [],
        ];

        // Check for basic auth setup
        $installation_factory = new InstallationFactory();
        $local_installation = $installation_factory->getLocalInstallation();
        if ($local_installation->connection_type == "basicauth") {
            $args["headers"]["Authorization"] = "Basic " . base64_encode($local_installation->basic_auth_username . ":" . $local_installation->basic_auth_password);
        }

        $tests_per_http_type = 5;
        $error_runs = [];
        for ($i = 0; $i < $tests_per_http_type; $i++) {
            if ($http_type == 'GET') {
                $response = wp_remote_get($multiple_connection_test_url, $args);
            } elseif ($http_type == 'POST') {
                $response = wp_remote_post($multiple_connection_test_url, $args);
            }
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                // Check correct body
                $body = wp_remote_retrieve_body($response);
                if ($body != '[]') {
                    $error_runs[$i] = $response;
                }
            } else {
                $error_runs[$i] = $response;
            }
        }
        if (count($error_runs) > 0) {
            $this->healthcheck->errors[] = sprintf(
                __("REST test error - Tried making %d consecutive requests (with HTTP %s) to a test REST service on this site. %d of them failed, with these errors:", "wpsynchro"),
                $tests_per_http_type,
                $http_type,
                count($error_runs)
            );

            // Get basic auth class, to check if we are hitting basic auth
            $basic_auth = new BasicAuth();
            $atleast_one_used_basic_auth = false;

            foreach ($error_runs as $error_run_num => $response) {
                if (is_wp_error($response)) {
                    $this->healthcheck->errors[] = sprintf(__("Error from request (number %d):", "wpsynchro"), $error_run_num + 1) . " " . $response->get_error_message();
                } else {
                    // Check for authentication on remote
                    if ($basic_auth->checkResponseHeaderForBasicAuth($response)) {
                        $atleast_one_used_basic_auth = true;
                        $this->healthcheck->errors[] = __("This site is protected by Basic Authentication, which requires a username and password.
                        You can add the correct username/password in the 'Setup' menu.", "wpsynchro");
                        break;
                    }
                    // Maybe a content error
                    $body = wp_remote_retrieve_body($response);
                    if ($body != '[]') {
                        $this->healthcheck->errors[] = sprintf(__("Error from request (number %d) - Got wrong data in response from webservice - Expected '[]' - Got: ", "wpsynchro"), $error_run_num + 1) . " '" . $body . "'";
                    }
                }
            }
            if ($atleast_one_used_basic_auth ===  false) {
                // Catch LocalWP bug
                if (isset($error_runs[1]) && isset($error_runs[3]) && count($error_runs) === 2) {
                    $this->healthcheck->errors[] = __("The pattern of errors suggest you are using LocalWP as development environment. It contains a bug where 50% of remote requests fail, when called from the code. That is why request 2 and 4 fails, but 1,3 and 5 succeed. Read more on https://wpsynchro.com/documentation/local-by-flywheel-localwp", "wpsynchro");
                } else {
                    $this->healthcheck->errors[] = sprintf(
                        __("This issue is most likely caused by a misconfiguration of the hosting environment. Most often because of too few available worker processes. See more documentation on this here: %s", "wpsynchro"),
                        $this->healthcheck_documentation_url
                    );
                }
            }
        }
    }

    /**
     *  Check that multiple connections to local services can be done via POST
     */
    public function checkMultipleConnectionsPOST()
    {
        $this->checkMultipleConnections('POST');
    }

    /**
     *  Check local REST urls for connectivity and proper response
     */
    public function checkInitiateAndMastedata()
    {
        $initiate_token = "";

        $initiate_server_okay = false;

        $logger = new NullLogger();
        $inst_factory = new InstallationFactory();
        $local_installation = $inst_factory->getLocalInstallation();
        $retrieval = new InitiateTokenRetrieval($logger, $local_installation);
        $result = $retrieval->getInitiateToken();

        if ($result && isset($retrieval->token) && strlen($retrieval->token) > 0) {
            $initiate_token = $retrieval->token;
            $initiate_server_okay = true;
        } else {
            $this->healthcheck->errors = array_merge($this->healthcheck->errors, $retrieval->getErrors());
            $this->healthcheck->warnings = array_merge($this->healthcheck->warnings, $retrieval->getWarnings());
            $this->healthcheck->errors[] = __("REST error - Can not reach 'initiate' REST service - Check that REST services is accessible and not being blocked", "wpsynchro");
        }

        if ($initiate_server_okay) {

            // Create a transfer token based on the token we just got
            $transfer_token = TransferToken::getTransferToken(TransferAccessKey::getAccessKey(), $initiate_token);

            // Get masterdata retrival object
            $retrieval = new MasterdataRetrieval($local_installation);
            $retrieval->setDataToRetrieve(['dbtables', 'filedetails']);
            $retrieval->setToken($transfer_token);
            $retrieval->setEncryptionKey(TransferAccessKey::getAccessKey());
            $result = $retrieval->getMasterdata();

            // Check for errors
            if ($result) {
                if (!$retrieval->data->dbtables) {
                    $this->healthcheck->errors[] = __("REST error - Masterdata REST service returns improper response - Data was not returned in usable way - Check PHP error log", "wpsynchro");
                }
            } else {
                $this->healthcheck->errors[] = __("REST error - Can not reach 'masterdata' REST service - Check that WP Synchro is activated and REST service accessible", "wpsynchro");
            }
        }
    }

    /**
     *  Check writable log directory
     */
    public function checkWritableLogDir()
    {
        $commonfunctions = new CommonFunctions();
        $commonfunctions->createLogLocation();
        $log_location = $commonfunctions->getLogLocation();
        $log_dir = realpath($log_location);
        if (!is_writable($log_dir)) {
            $this->healthcheck->errors[] = sprintf(__("WP Synchro log dir is not writable for PHP - Path: %s ", "wpsynchro"), $log_dir);
        }
    }

    /**
     *  Check other relevant dir for writability (typically for files sync)
     */
    public function checkRelevantDirsForWritable()
    {
        if (!\WPSynchro\CommonFunctions::isPremiumVersion()) {
            return;
        }
        $paths_check = [
            // Document root
            $_SERVER['DOCUMENT_ROOT'],
            // Absolut directory of WP_CONTENT folder, or whatever it is called
            WP_CONTENT_DIR,
            // One dir above webroot
            dirname(realpath($_SERVER['DOCUMENT_ROOT']))
        ];
        foreach ($paths_check as $path) {
            if (!MasterData::checkReadWriteOnDir($path)) {
                $this->healthcheck->warnings[] = sprintf(__("Path that WP Synchro might use for synchronization is not writable- Path: %s -  This can be caused by PHP's open_basedir setting or file permissions", "wpsynchro"), $path);
            }
        }
    }
}
