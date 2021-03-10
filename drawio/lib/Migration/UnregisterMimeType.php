<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 * Based on Keeweb solution
 *
 **/

namespace OCA\Drawio\Migration;


use OCP\Files\IMimeTypeLoader;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class UnregisterMimeType extends MimeTypeMigration
{
    public function getName()
    {
        return 'Unregister MIME type for "application/x-drawio"';
    }

    private function unregisterForExistingFiles()
    {
        $mimeTypeId = $this->mimeTypeLoader->getId('application/octet-stream');
        $this->mimeTypeLoader->updateFilecache('drawio', $mimeTypeId);
    }

    private function unregisterForNewFiles()
    {
        $mappingFile = \OC::$configDir . self::CUSTOM_MIMETYPEMAPPING;

        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                unset($mapping['drawio']);
            } else {
                $mapping = [];
            }
            file_put_contents($mappingFile, json_encode($mapping, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }

    public function run(IOutput $output)
    {
        $output->info('Unregistering the mimetype...');

        // Register the mime type for existing files
        $this->unregisterForExistingFiles();

        // Register the mime type for new files
        $this->unregisterForNewFiles();

        $output->info('The mimetype was successfully unregistered.');
    }
}
