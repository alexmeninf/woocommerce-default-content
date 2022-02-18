<?php

namespace WPSynchro\REST;

use WPSynchro\Files\SyncList;
use WPSynchro\Job;

/**
 * Class for handling REST to get file changes for current sync
 * Call should already be verified by permissions callback
 *
 * @since 1.7.0
 */
class StatusFileChanges
{

    public function service($request)
    {
        if (!isset($request['jobid']) || strlen($request['jobid']) == 0) {
            return new \WP_REST_Response([], 400);
        }
        if (!isset($request['instid']) || strlen($request['instid']) == 0) {
            return new \WP_REST_Response([], 400);
        }
        $inst_id = $request['instid'];
        $job_id = $request['jobid'];

        $job = new Job();
        $job_loaded = $job->load($inst_id, $job_id);
        if (!$job_loaded) {
            return new \WP_REST_Response([], 400);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->acceptFileChanges($job);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->getFileChanges($job);
        }
        return new \WP_REST_Response([], 400);
    }

    /**
     *  Get the file changes
     */
    public function getFileChanges($job)
    {
        $sync_list = new SyncList();
        $sync_list->job = $job;

        $need_transfer = $sync_list->getFileChangesByType("add");
        $files_for_delete = $sync_list->getFileChangesByType("delete");

        $files_changed = [
            'will_be_deleted' => $files_for_delete,
            'will_be_added_changed' => $need_transfer,
            'basepath' => $job->to_files_above_webroot_dir,
        ];

        return new \WP_REST_Response($files_changed, 200);
    }

    /**
     *  Accept from user of file changes
     */
    public function acceptFileChanges($job)
    {
        // Set it as confirmed
        $job->files_user_confirmed_actions = true;
        // As the worker is paused, we just update the run lock timer, giving the JS worker thread 20 seconds to start again
        $job->run_lock_problem_time = time() + 20;
        // Boyaa!
        $job->save();

        return new \WP_REST_Response([], 200);
    }
}
