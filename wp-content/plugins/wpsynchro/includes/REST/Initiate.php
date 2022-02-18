<?php

/**
 * Class for handling REST service "initate" - Starting a synchronization
 * @since 1.0.0
 */

namespace WPSynchro\REST;

use WPSynchro\Licensing;
use WPSynchro\Transport\ReturnResult;
use WPSynchro\Utilities\Compatibility\MUPluginHandler;
use WPSynchro\Transport\TransferToken;
use WPSynchro\Utilities\Configuration\PluginConfiguration;

class Initiate
{

    public function service($request)
    {
        $sync_response = new \stdClass();
        $sync_response->errors = [];
        $token_lifespan = 10800;

        $allowed_types = ['push', 'pull', 'local'];
        if (isset($request['type']) && in_array($request['type'], $allowed_types)) {
            $type = $request['type'];
            // Get allowed methods for this site
            $plugin_configuration = new PluginConfiguration();
            $methods_allowed = $plugin_configuration->getAllowedSynchronizationMethods();

            // Check the type and if it is allowed
            if ($type == 'pull' && !$methods_allowed->pull) {
                $sync_response->errors[] = __('Pulling from this site is not allowed - Change configuration on remote server', 'wpsynchro');
            } elseif ($type == 'push' && !$methods_allowed->push) {
                $sync_response->errors[] = __('Pushing to this site is not allowed - Change configuration on remote server', 'wpsynchro');
            }

            // Check licensing
            if (\WPSynchro\CommonFunctions::isPremiumVersion()) {
                $licensing = new Licensing();
                $licensecheck = $licensing->verifyLicense();

                if ($licensecheck == false) {
                    $sync_response->errors[] = $licensing->getLicenseErrorMessage();
                }
            }
        } else {
            $sync_response->errors[] = __('Remote host does not allow that - Make sure it is same WP Synchro version', 'wpsynchro');
        }

        if (count($sync_response->errors) > 0) {
            $return_result = new ReturnResult();
            $return_result->init();
            $return_result->setDataObject($sync_response);
            return $return_result->echoDataFromRestAndExit();
        }

        // Create a new transfer object
        $token_class = new TransferToken();
        $token = $token_class->setNewToken($token_lifespan);
        $sync_response->token = $token;

        // Check if MU plugin needs update
        $muplugin_handler = new MUPluginHandler();
        $muplugin_handler->checkNeedsUpdate();

        // Return
        $return_result = new ReturnResult();
        $return_result->init();
        $return_result->setDataObject($sync_response);
        return $return_result->echoDataFromRestAndExit();
    }
}
