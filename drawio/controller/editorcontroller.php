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

use OCP\App;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCP\AutoloadNotAllowedException;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;

use OCA\Files\Helper;
use OCA\Files_Versions\Storage;

use OCA\Drawio\AppConfig;



class EditorController extends Controller
{

    private $userSession;
    private $root;
    private $urlGenerator;
    private $trans;
    private $logger;
    private $config;


    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param OCA\Drawio\AppConfig $config - app config
     */
    public function __construct($AppName,
                                IRequest $request,
                                IRootFolder $root,
                                IUserSession $userSession,
                                IURLGenerator $urlGenerator,
                                IL10N $trans,
                                ILogger $logger,
                                AppConfig $config
                                )
    {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
    }

     /**
     * This comment is very important, CSRF fails without it
     *
     * @param integer $fileId - file identifier
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId) {
        $drawioUrl = $this->config->GetDrawioUrl();
        $theme = $this->config->GetTheme();
        $overrideXml = $this->config->GetOverrideXml();
		$offlineMode = $this->config->GetOfflineMode();
        $lang = $this->config->GetLang();
        $lang = trim(strtolower($lang));

        if ($lang=="auto")
        {
            $lang = \OC::$server->getL10NFactory("")->get("")->getLanguageCode();
        }

        if (empty($drawioUrl))
        {
            $this->logger->error("drawioUrl is empty", array("app" => $this->appName));
            return ["error" => $this->trans->t("Draw.io app not configured! Please contact admin.")];
        }

        $drawioUrlArray = explode("?",$drawioUrl);

        if (count($drawioUrlArray) > 1){
            $drawioUrl = $drawioUrlArray[0];
            $drawioUrlArgs = $drawioUrlArray[1];
        } else {
            $drawioUrlArgs = "";
        }

        list ($file, $error) = $this->getFile($fileId);

        if (isset($error))
        {
            $this->logger->error("Load: " . $fileId . " " . $error, array("app" => $this->appName));
            return ["error" => $error];
        }

        $uid = $this->userSession->getUser()->getUID();
        $baseFolder = $this->root->getUserFolder($uid);

        $params = [
            "drawioUrl" => $drawioUrl,
            "drawioUrlArgs" => $drawioUrlArgs,
            "drawioTheme" => $theme,
            "drawioLang" => $lang,
            "drawioOverrideXml" => $overrideXml,
			"drawioOfflineMode" => $offlineMode,
            "drawioFilePath" => $baseFolder->getRelativePath($file->getPath())
        ];

        $response = new TemplateResponse($this->appName, "editor", $params);

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (isset($drawioUrl) && !empty($drawioUrl))
        {
            $csp->addAllowedScriptDomain($drawioUrl);
            $csp->addAllowedFrameDomain($drawioUrl);
            $csp->addAllowedFrameDomain("blob:");
            $csp->addAllowedChildSrcDomain($drawioUrl);
            $csp->addAllowedChildSrcDomain("blob:");
        }
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * @NoAdminRequired
    */
    private function getFile($fileId)
    {
        if (empty($fileId))
        {
            return [null, $this->trans->t("FileId is empty")];
        }

        $files = $this->root->getById($fileId);
        if (empty($files))
        {
            return [null, $this->trans->t("File not found")];
        }
        $file = $files[0];

        if (!$file->isReadable())
        {
            return [null, $this->trans->t("You do not have enough permissions to view the file")];
        }
        return [$file, null];
    }

}
