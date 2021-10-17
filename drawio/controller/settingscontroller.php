<?php

/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 * @author Arno Welzel <privat at arnowelzel.de>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

namespace OCA\Drawio\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;

use OCA\Drawio\AppConfig;
use OCA\Drawio\Migration;



class SettingsController extends Controller
{

    private $trans;
    private $logger;
    private $config;


    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param OCA\Drawio\AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                IRequest $request,
                                IL10N $trans,
                                ILogger $logger,
                                AppConfig $config
                                )
    {
        parent::__construct($AppName, $request);

        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
    }


    /**
     * Config page
     *
     * @return TemplateResponse
     */
    public function index() {
        $data = [
            "drawioUrl" => $this->config->GetDrawioUrl(),
            "drawioOfflineMode" => $this->config->GetOfflineMode(),
            "drawioTheme" => $this->config->GetTheme(),
            "drawioLang" => $this->config->GetLang(),
            "drawioAutosave" => $this->config->GetAutosave(),
            "drawioLibraries" => $this->config->GetLibraries()
        ];
        return new TemplateResponse($this->appName, "settings", $data, "blank");
    }



    public function settings()
    {
        $drawio = trim($_POST['drawioUrl']);
        $offlinemode = trim($_POST['offlineMode']);
        $theme = trim($_POST['theme']);
        $lang = trim($_POST['lang']);
        $autosave = trim($_POST['autosave']);
        $libraries = trim($_POST['libraries']);

        $this->config->SetDrawioUrl($drawio);
        $this->config->SetOfflineMode($offlinemode);
        $this->config->SetTheme($theme);
        $this->config->SetLang($lang);
        $this->config->SetAutosave($autosave);
        $this->config->SetLibraries($libraries);

        if (version_compare(implode(".", \OCP\Util::getVersion()), "13", ">=")) {
            $checkmime = new \OCA\Drawio\Migration\CheckMimeType();
            $registered = $checkmime->run();

            if ($registered == false) {
            $mimeTypeLoader = \OC::$server->getMimeTypeLoader();
            $mime = new \OCA\Drawio\Migration\RegisterMimeType($mimeTypeLoader);
            $output = new \OC\Migration\SimpleOutput($this->logger, $this->appName);
            $mime->run($output);
            }
        }

        return [
            "drawioUrl" => $this->config->GetDrawioUrl(),
            "offlineMode" => $this->config->GetOfflineMode(),
            "theme" => $this->config->GetTheme(),
            "lang" => $this->config->GetLang(),
            "drawioAutosave" =>$this->config->GetAutosave(),
            "drawioLibraries" =>$this->config->GetLibraries()
            ];
    }


    /**
     * Get supported formats
     *
     * @return array
     *
     * @NoAdminRequired
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getsettings()
    {
         $data = array();
         $data['formats'] = $this->config->formats;
         $data['settings'] = array();
         $data['settings']['offlineMode'] = $this->config->GetOfflineMode();
         return $data;
    }

}
