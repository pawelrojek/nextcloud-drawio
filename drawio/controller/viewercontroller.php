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

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;
use OCP\AutoloadNotAllowedException;
use OCP\Constants;
use OCP\Files\FileInfo;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;

use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;

use OCA\Files\Helper;
use OCA\Files_Versions\Storage;
use OCA\Viewer\Event\LoadViewer;

use OCA\Drawio\AppConfig;


class ViewerController extends Controller
{

    private $userSession;
    private $root;
    private $urlGenerator;
    private $trans;
    private $logger;
    private $config;
    private $userId;
    /**
     * Session
     *
     * @var ISession
     */
    private $session;
    /**
     * Share manager
     *
     * @var IManager
     */
    private $shareManager;


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
                                AppConfig $config,
                                IManager $shareManager,
                                ISession $session,
                                $UserId
                               )
    {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
        $this->shareManager = $shareManager;
        $this->session = $session;
        $this->userId = $UserId;
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
    public function index($fileId, $shareToken = NULL, $filePath = NULL) {
        //$this->logger->warning("Open: $fileId $shareToken $filePath", array("app" => $this->appName));
        if (empty($shareToken) && !$this->userSession->isLoggedIn()) {
            $redirectUrl = $this->urlGenerator->linkToRoute("core.login.showLoginForm", [
                "redirect_url" => $this->request->getRequestUri()
            ]);
            return new RedirectResponse($redirectUrl);
        }

        $drawioUrl = $this->config->GetDrawioUrl();
        $theme = $this->config->GetTheme();
	    $offlineMode = $this->config->GetOfflineMode();
        $lang = $this->config->GetLang();
        $lang = trim(strtolower($lang));

        $eventDispatcher = \OC::$server->getEventDispatcher();

        if ("auto" === $lang)
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

        if( $fileId ) {
            list ($file, $error) = $this->getFile($fileId);

            if (isset($error))
            {
              $this->logger->error("Load: " . $fileId . " " . $error, array("app" => $this->appName));
              return ["error" => $error];
            }

            $uid = $this->userSession->getUser()->getUID();
            $baseFolder = $this->root->getUserFolder($uid);
            $relativePath = $baseFolder->getRelativePath($file->getPath());
        }
        else {
            list ($file, $error, $share) = $this->getFileByToken($fileId, $shareToken);
            if (isset($error))
            {
              $this->logger->error("Load with token: " . $shareToken . " " . $error, array("app" => $this->appName));
              return ["error" => $error];
            }
            $relativePath = $file->getPath();
            //$relativePath = "/s/$shareToken/download";//$file->getPath();
        }

        $params = [
            "drawioUrl" => $drawioUrl,
            "drawioUrlArgs" => $drawioUrlArgs,
            "drawioTheme" => $theme,
            "drawioLang" => $lang,
      	    "drawioOfflineMode" => $offlineMode,
            //"drawioFilePath" => rawurlencode($relativePath), // info not needed for public view
            "drawioAutosave" => $this->config->GetAutosave(),
            "drawioLibraries" =>$this->config->GetLibraries(),
            "fileId" => $fileId,
            "filePath" => $filePath,
            "shareToken" => $shareToken,
            "drawioReadOnly" => true
        ];


        if (isset($share)) {

            $share_type = $share->getShareType();

            $permissions = $share->getPermissions();
            if (($permissions & Constants::PERMISSION_UPDATE) !== 0) {
                $params ['drawioReadOnly'] = false;
            }

            if ($share_type === \OCP\Share::SHARE_TYPE_LINK) { // public links / anonymous editing should not be possible (?)
                $params ['drawioReadOnly'] = true;
                
                if ($permissions == CONSTANTS::PERMISSION_READ + CONSTANTS::PERMISSION_UPDATE   // 3 = 1 + 2
                    or $permissions == CONSTANTS::PERMISSION_SHARE + CONSTANTS::PERMISSION_READ + CONSTANTS::PERMISSION_UPDATE) { // 19 = 16 + 1 + 2
                    $params ['drawioReadOnly'] = false;
                }          

            }

        }

        if ($this->userId) {
            $response = new TemplateResponse($this->appName, "viewer", $params);
        } else {
            $response = new PublicTemplateResponse($this->appName, "viewer", $params);
        }

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
     * Print public editor section
     *
     * @param integer $fileId - file identifier
     * @param string $shareToken - access token
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function PublicPage($fileId, $shareToken) {
        return $this->index($fileId, $shareToken);
    }

    /**
     * Collecting the file parameters for the DrawIo application
     *
     * @param integer $fileId - file identifier
     * @param string $filePath - file path
     * @param string $shareToken - access token
     *
     * @return DataDownloadResponse
     *
     * @NoAdminRequired
     * @PublicPage
     */
    public function PublicFile($fileId, $filePath = NULL, $shareToken = NULL) {
        if (empty($shareToken)) {
            return ["error" => $this->trans->t("Not permitted")];
        }

        $user = $this->userSession->getUser();
        $userId = NULL;
        if (!empty($user)) {
            $userId = $user->getUID();
        }

        list ($file, $error, $share) = $this->getFileByToken($fileId, $shareToken);

        if (isset($error)) {
            $this->logger->error("Config: $fileId $error", array("app" => $this->appName));
            return ["error" => $error];
        }

        $fileName = $file->getName();
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $format = $this->config->formats[$ext];

        if (!isset($format)) {
            $this->logger->info("Format is not supported for editing: $fileName", array("app" => $this->appName));
            return ["error" => $this->trans->t("Format is not supported")];
        }

        $fileUrl = "";//$this->getUrl($file, $shareToken);

        $params = [
            "url" => $fileUrl,
            "file" => ""
        ];

        try {
                return new DataDownloadResponse($file->getContent(), $file->getName(), $file->getMimeType());
            } catch (NotPermittedException  $e) {
                $this->logger->error("Download Not permitted: $fileId " . $e->getMessage(), array("app" => $this->appName));
                //$params["error"] = new JSONResponse(["message" => $this->trans->t("Not permitted")], Http::STATUS_FORBIDDEN);
                return new JSONResponse(["message" => $this->trans->t("Not permitted")], Http::STATUS_FORBIDDEN);
            }
            return new JSONResponse(["message" => $this->trans->t("Download failed")], Http::STATUS_INTERNAL_SERVER_ERROR);

	//return $params;
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

    /**
     * Getting file by token
     *
     * @param integer $fileId - file identifier
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getFileByToken($fileId, $shareToken) {
        list ($node, $error, $share) = $this->getNodeByToken($shareToken);

        if (isset($error)) {
            return [NULL, $error, NULL];
        }

        if ($node instanceof Folder) {
            try {
                $files = $node->getById($fileId);
            } catch (\Exception $e) {
                $this->logger->error("getFileByToken: $fileId " . $e->getMessage(), array("app" => $this->appName));
                return [NULL, $this->trans->t("Invalid request"), NULL];
            }

            if (empty($files)) {
                $this->logger->info("Files not found: $fileId", array("app" => $this->appName));
                return [NULL, $this->trans->t("File not found"), NULL];
            }
            $file = $files[0];
        } else {
            $file = $node;
        }

        return [$file, NULL, $share];
    }

    /**
     * Getting file by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getNodeByToken($shareToken) {
        list ($share, $error) = $this->getShare($shareToken);

        if (isset($error)) {
            return [NULL, $error, NULL];
        }

        if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file"), NULL];
        }

        try {
            $node = $share->getNode();
        } catch (NotFoundException $e) {
            $this->logger->error("getFileByToken error: " . $e->getMessage(), array("app" => $this->appName));
            return [NULL, $this->trans->t("File not found"), NULL];
        }

        return [$node, NULL, $share];
    }
    /**
     * Getting share by token
     *
     * @param string $shareToken - access token
     *
     * @return array
     */
    private function getShare($shareToken) {
        if (empty($shareToken)) {
            return [NULL, $this->trans->t("FileId is empty")];
        }

        $share;
        try {
            $share = $this->shareManager->getShareByToken($shareToken);
        } catch (ShareNotFound $e) {
            $this->logger->error("getShare error: " . $e->getMessage(), array("app" => $this->appName));
            $share = NULL;
        }

        if ($share === NULL || $share === false) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file")];
        }

        if ($share->getPassword()
            && (!$this->session->exists("public_link_authenticated")
                || $this->session->get("public_link_authenticated") !== (string) $share->getId())) {
            return [NULL, $this->trans->t("You do not have enough permissions to view the file")];
        }

        return [$share, NULL];
    }

    /**
     * Print error page
     *
     * @param string $error - error message
     * @param string $hint - error hint
     *
     * @return TemplateResponse
     */
    private function renderError($error, $hint = "") {
        return new TemplateResponse("", "error", array(
                "errors" => array(array(
                "error" => $error,
                "hint" => $hint
            ))
        ), "error");
    }

}
