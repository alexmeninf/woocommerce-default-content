<?php

namespace WPSynchro\Database;

/**
 * Database helper functions
 * @since 1.6.0
 */
class DatabaseHelperFunctions
{

    /**
     *  Handle table prefix name changes, if needed
     *  @since 1.3.2
     */
    public static function handleTablePrefixChange($table_name, $source_prefix, $target_prefix)
    {

        // Check if we need to change prefixes
        if ($source_prefix != $target_prefix) {
            if (substr($table_name, 0, strlen($source_prefix)) == $source_prefix) {
                $table_name = substr($table_name, strlen($source_prefix));
                $table_name = $target_prefix . $table_name;
            }
        }
        return $table_name;
    }

    /**
     *  Check if specific table is being moved, by search for table name ends with X
     *  @since 1.6.0
     */
    public static function isTableBeingTransferred($tablelist, $table_prefix, $table_ends_with)
    {
        foreach ($tablelist as $table) {
            $tablename_with_prefix = str_replace($table_prefix, "", $table->name);
            if ($tablename_with_prefix === $table_ends_with) {
                return true;
            }
        }
        return false;
    }

    /**
     *  Get last db query error
     */
    public function getLastDBQueryErrors()
    {
        global $wpdb;
        $log_errors = [];
        $user_errors = [];

        // Check what error we have
        $base_error = sprintf(
            __('Synchronization aborted, due to a SQL query failing. See WP Synchro log (found in menu "Logs") for specific information about the query that failed. The specific error from database server was: "%s".', 'wpsynchro'),
            $wpdb->last_error
        );
        if (strpos($wpdb->last_error, 'Specified key was too long') !== false) {
            // Too long key
            $user_errors[] = $base_error . " " . __('That means that the key was longer than supported on the target database. The table need to be fixed or excluded from synchronization. See documentation on wpsynchro.com for further help.', 'wpsynchro');
        } elseif (strpos($wpdb->last_error, 'Unknown collation') !== false) {
            // Not supported collation/charset
            $user_errors[] = $base_error . " " . __('That means that the charset/collation used is not supported by the target database engine. The table charset/collations needs to be changed into a supported charset/collation for the target database or excluded from synchronization. See documentation on wpsynchro.com for further help.', 'wpsynchro');
        } elseif (strpos($wpdb->last_query, 'CREATE VIEW') === 0) {
            // Could not create view. Typically, because the required other tables are not there
            $user_errors[] = $base_error . " " . __('The error was caused by trying to create a view in the database. The error is normally thrown from the database server, when the view references tables that do not exist on the target database, so make sure they are there.', 'wpsynchro');
        } else {
            // General error message
            $user_errors[] = $base_error . " " . __('If you need help, contact WP Synchro support.', 'wpsynchro');
        }

        // Logging for log files
        $log_errors[] = "SQL query failed execution: " . $wpdb->last_query;
        $log_errors[] = "WPDB last error: " . $wpdb->last_error;

        return [
            'log_errors' => $log_errors,
            'user_errors' => $user_errors,
        ];
    }
}
