<?php

namespace WPSynchro\Pages;

/**
 * Class for handling when running a sync
 *
 * @since 1.0.0
 */
class AdminRunSync
{

    public static function render()
    {

        $instance = new self;
        $instance->handleGET();
    }

    private function handleGET()
    {
        global $wpsynchro_container;
        $commonfunctions = $wpsynchro_container->get('class.CommonFunctions');

        if (isset($_REQUEST['syncid'])) {
            $id = $_REQUEST['syncid'];
        } else {
            $id = "";
        }
        if (isset($_REQUEST['jobid'])) {
            $jobid = $_REQUEST['jobid'];
        } else {
            $jobid = uniqid();
        }

        if (strlen($id) < 1) {
            echo "<div class='notice wpsynchro-notice'><p>" . __('No syncid provided - This should not happen', 'wpsynchro') . '</p></div>';
            return;
        }

        if (strlen($jobid) < 1) {
            echo "<div class='notice wpsynchro-notice'><p>" . __('No jobid provided - This should not happen', 'wpsynchro') . '</p></div>';
            return;
        }

        // Create log dir if needed
        $commonfunctions->createLogLocation();

        // Create new job with this sync
        $inst_factory = $wpsynchro_container->get('class.InstallationFactory');
        $jobid = $inst_factory->startInstallationSync($id, $jobid);
        if ($jobid == null) {
            echo "<div class='notice wpsynchro-notice'><p>" . __('Installation not found - This should not happen', 'wpsynchro') . '</p></div>';
            return;
        }

        // Get base stages
        $status_controller = $wpsynchro_container->get('class.SynchronizeStatus');
        $status_controller->setup($id, $jobid);
        $default_stages = $status_controller->getStages();

        // Localize the script with data
        $adminjsdata = [
            'id' => $id,
            'jobid' => $jobid,
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'rest_root' => esc_url_raw(rest_url()),
            'text_ajax_response_error' => __("Could not get data from local REST service ({0}) - Maybe local server has troubles?", "wpsynchro"),
            'text_ajax_request_error' => __("No proper response from local server - Maybe REST service is blocked? This can also be a temporary issue, if the host has issues. Please try again", "wpsynchro"),
            'text_ajax_default_error' => __("Unknown error - Maybe this helps:", "wpsynchro"),
            'aborted_decline_file_changes' => __("Synchronization aborted, due to user decline of file changes", "wpsynchro"),
            'rest_accept_file_changes_error' => __("Could not accept the file changes - Error contacting REST service - Try again", "wpsynchro"),
            'rest_get_file_changes_error' => __("Could not get the file changes - Error contacting REST service", "wpsynchro"),
            'default_stages' => $default_stages,
        ];
        wp_localize_script('wpsynchro_admin_js', 'wpsynchro_run', $adminjsdata);

        $file_accept_modal = [
            'headline' => __("Verify the file changes", "wpsynchro"),
            'button_accept_text' => __("Accept changes", "wpsynchro"),
            'button_decline_text' => __("Decline changes", "wpsynchro"),
            'added_changed_files_tab' => __("Added/changed", "wpsynchro"),
            'deleted_files_tab' => __("Will be deleted", "wpsynchro"),
            'controls_help_text' => __("Choose if you want to see the files with full path, or just see clipped paths that start above the web root.", "wpsynchro"),
            'show_full_path' => __("Show full paths", "wpsynchro"),
            'files_changed_pre_text' => __("Files that will be added or overwritten:", "wpsynchro"),
            'files_deleted_pre_text' => __("Files that will be deleted:", "wpsynchro"),
            'files_no_changed' => __("No files will be added or overwritten.", "wpsynchro"),
            'files_no_deletes' => __("There is no files marked for deletion.", "wpsynchro"),
        ];
        wp_localize_script('wpsynchro_admin_js', 'wpsynchro_file_changes', $file_accept_modal);

?>
        <div id="wpsynchro-run-sync" class="wrap wpsynchro" v-cloak>
            <h2 class="pagetitle">
                <img src="<?= $commonfunctions->getAssetUrl("icon.png") ?>" width="35" height="35" />WP Synchro <?= WPSYNCHRO_VERSION ?>
                <?php echo (\WPSynchro\CommonFunctions::isPremiumVersion() ? 'PRO' : 'FREE'); ?> - <?php _e('Run synchronization', 'wpsynchro'); ?>
                <div v-if="overall_spinner" class="spinner"></div>
            </h2>
            <file-changes-dialog v-if="shouldShowFileChangesDialog" v-on:accept-file-changes="userAcceptedFileChanges()" v-on:decline-file-changes="userDeclinedFileChanges" v-bind:added-files="confirmFiles.addedFiles" v-bind:deleted-files="confirmFiles.deletedFiles" v-bind:basepath="confirmFiles.basepath"></file-changes-dialog>

            <div class="runsync-container">
                <div class="syncsection">
                    <div v-if="!is_completed && sync_errors.length == 0" class="syncnotice">
                        <?php _e('Do not navigate away from this page until synchronization is completed - It may break your installation', 'wpsynchro'); ?>
                    </div>

                    <div v-if="is_completed && sync_errors.length == 0" class="synccompleted">
                        <div class="iconpart">&#10003;</div>
                        <div>
                            <p><?php _e('Synchronization completed', 'wpsynchro'); ?></p>
                        </div>
                    </div>

                    <div v-if="sync_errors.length > 0 " class="syncerrors">
                        <div class="iconpart">&#9940;</div>
                        <div>
                            <p><b>{{ sync_errors.length }} <?php echo strtoupper(__('Error(s) during synchronization:', 'wpsynchro')); ?></b></p>
                            <ul>
                                <li v-for="error in sync_errors">{{error}}</li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="sync_warnings.length > 0" class="syncwarnings">
                        <div class="iconpart">&#9888;</div>
                        <div>
                            <p><b>{{ sync_warnings.length }} <?php _e('WARNING(S) (synchronization will continue):', 'wpsynchro'); ?></b></p>
                            <ul>
                                <li v-for="warning in sync_warnings">{{warning}}</li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="is_completed && sync_finalize_messages.length > 0 " class="sync-completed-messages">
                        <div class="iconpart">&#8505;</div>
                        <div>
                            <p><b><?php echo strtoupper(__('Manual steps might be needed to complete synchronization', 'wpsynchro')); ?></b></p>
                            <ul>
                                <li v-for="message in sync_finalize_messages" v-html="message"></li>
                            </ul>
                        </div>
                    </div>

                    <div class="">
                        <p><?php _e('Time elapsed', 'wpsynchro'); ?>: <span>{{ time_from_start.hours }}</span> <?php _e('Hours', 'wpsynchro'); ?> <span>{{ time_from_start.minutes }}</span> <?php _e('Minutes', 'wpsynchro'); ?> <span>{{ time_from_start.seconds }}</span> <?php _e('Seconds', 'wpsynchro'); ?></p>
                    </div>

                    <div v-for="(stage, index) in stages">
                        <h3><?php _e('Stage', 'wpsynchro'); ?> {{ index+1 }} - {{ stage.title }} <span v-if="stage.help_text.length > 0" v-bind:title="stage.help_text" class="stagehelp dashicons dashicons-editor-help"></span> ({{ stage.percent_complete }}%) <span class="stagedata" v-if="stage.status_text.length > 0">{{ stage.status_text }}</span></h3>
                        <b-progress v-bind:value="stage.percent_complete" v-bind:max="100"></b-progress>
                    </div>


                </div>

                <div class="cardboxes">
                    <?php
                    if (!\WPSynchro\CommonFunctions::isPremiumVersion()) {
                        echo $commonfunctions->getTemplateFile("card-pro-version");
                    }

                    ?>
                </div>
            </div>

        </div>
<?php
    }
}
