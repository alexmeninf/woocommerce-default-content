<?php

namespace WPSynchro\REST;

use WPSynchro\Transport\RemoteTransport;
use WPSynchro\Transport\ReturnResult;
use WPSynchro\Transport\Transfer;
use WPSynchro\Transport\TransferAccessKey;

/**
 * Class for handling REST service "getfiles" - Pulling files from remote
 * @since 1.0.3
 */
class GetFiles
{

    public function service(\WP_REST_Request $request)
    {
        // Get transfer object, so we can get data
        $transfer = new Transfer();
        $transfer->setEncryptionKey(TransferAccessKey::getAccessKey());
        $transfer->populateFromString($request->get_body());
        $body = $transfer->getDataObject();

        // Get data from request
        $files = $body->files;
        $maxsize = $body->max_file_size;

        // Get remote transfer object, to be used for its functions to read files
        $remotetransport = new RemoteTransport();
        $remotetransport->init();
        $remotetransport->setMaxRequestSize($maxsize);

        $filesync_added = [];
        foreach ($files as $file) {
            $more_space = $remotetransport->addFiledata($file);
            $filesync_added[] = $file;

            // If it could not be added, probably due to hitting max size, break off
            if ($more_space === false) {
                break;
            }
        }

        // Return the result
        $returnresult = new ReturnResult();
        $returnresult->init();
        $returnresult->setTransferObject($remotetransport->transfer);
        $returnresult->setDataObject($filesync_added);  // This NEEDS to be after the new transferobject assigment, to make it is added to the new transferobject
        return $returnresult->echoDataFromRestAndExit();
    }
}
