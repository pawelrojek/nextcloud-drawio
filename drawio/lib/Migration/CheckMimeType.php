<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

namespace OCA\Drawio\Migration;


class CheckMimeType
{
    const CUSTOM_MIMETYPEMAPPING = 'mimetypemapping.json';

    private function check()
    {
        $mappingFile = \OC::$configDir . self::CUSTOM_MIMETYPEMAPPING;

        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($mapping['drawio'])) return true;
            }
        }

        return false;
    }

    public function run()
    {
        return $this->check();
    }
}
