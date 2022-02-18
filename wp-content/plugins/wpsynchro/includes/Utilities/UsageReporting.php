<?php

namespace WPSynchro\Utilities;

use WPSynchro\CommonFunctions;
use WPSynchro\InstallationFactory;
use WPSynchro\Transport\RemoteTransport;
use WPSynchro\Utilities\Configuration\PluginConfiguration;

/**
 * Usage reporting class
 *
 * @since 1.7.0
 */
class UsageReporting
{
    const VERSION = 1;
    private $usage_reporting_url = "https://wpsynchro.com/api/v1/usage-reporting";
    private $installation = null;

    public function __construct()
    {
    }

    /**
     * Send the usage reporting
     */
    public function sendUsageReporting($installation)
    {
        $this->installation = $installation;
        $this->installation->checkAndUpdateToPreset();

        // Check if user has accepted usage reporting
        $plugin_configuration = new PluginConfiguration();
        if (!$plugin_configuration->getUsageReportingSetting()) {
            return;
        }

        // Collect data for usage reporting
        $data = $this->getData();

        // Log the data in current sync log file, to provide transparency as to what we are sending back
        global $wpsynchro_container;
        $logger = $wpsynchro_container->get("class.Logger");
        $logger->log("DEBUG", "Usage reporting data sent to wpsynchro.com server:", $data);

        // Send it
        $remotetransport = new RemoteTransport();
        $remotetransport->init();
        $remotetransport->setUrl($this->usage_reporting_url);
        $remotetransport->setDataObject($data);
        $remotetransport->setSendDataAsJSON();
        $remotetransport->blocking_request = false;
        $remotetransport->remotePOST();
    }

    /**
     * Get the data to send with usage reporting
     */
    public function getData()
    {
        $installation_factory = new InstallationFactory();
        $installation_count = count($installation_factory->getAllInstallations());

        $data = [
            'version' => self::VERSION,
            'site_hash' => sha1(get_home_url()),
            'lang' => get_locale(),
            'is_pro' => CommonFunctions::isPremiumVersion(),
            'installation_count' => $installation_count,
            'total_synchronizations' => get_option('wpsynchro_success_count', 0),
            'features_used_this_sync' => [
                'success_notification_email' => count(explode(';', $this->installation->success_notification_email_list)),
                'error_notification_email' => count(explode(';', $this->installation->error_notification_email_list)),
                'clear_cache_on_success' => $this->installation->clear_cache_on_success,
                'sync_preset' => $this->installation->sync_preset,
                'sync_database' => $this->installation->sync_database,
                'sync_files' => $this->installation->sync_files,
                'db_make_backup' => $this->installation->db_make_backup,
                'db_table_prefix_change' => $this->installation->db_table_prefix_change,
                'db_preserve_activeplugins' => $this->installation->db_preserve_activeplugins,
                'include_all_database_tables' => $this->installation->include_all_database_tables,
                'only_include_database_table_count' => count($this->installation->only_include_database_table_names),
                'searchreplaces_count' => count($this->installation->searchreplaces),
                'file_locations_count' => count($this->installation->file_locations),
                'files_exclude_files_match_count' => count(explode(',', $this->installation->files_exclude_files_match)),
                'files_ask_user_for_confirm' => $this->installation->files_ask_user_for_confirm,
            ],
        ];

        return $data;
    }
}
