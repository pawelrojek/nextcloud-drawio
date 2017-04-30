<?php
/**
 * Copyright (c) 2017 Pawel Rojek <pawel@pawelrojek.com>
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

    //creates new file
    public function create($name, $dir)
    {
        $userId = $this->userSession->getUser()->getUID();
        $userFolder = $this->root->getUserFolder($userId);
        $folder = $userFolder->get($dir);

        if ($folder === NULL)
        {
            $this->logger->info("Folder for file creation was not found: " . $dir, array("app" => $this->appName));
            return ["error" => $this->trans->t("The required folder was not found")];
        }
        if (!$folder->isCreatable())
        {
            $this->logger->info("Folder for file creation without permission: " . $dir, array("app" => $this->appName));
            return ["error" => $this->trans->t("You don't have enough permission to create file")];
        }

        $name = $userFolder->getNonExistingName($name);
        $filePath = $dir . DIRECTORY_SEPARATOR . $name;
        $ext = strtolower("." . pathinfo($filePath, PATHINFO_EXTENSION));
        $templatePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "new" . $ext;

        $template = file_get_contents($templatePath);
        if (!$template)
        {
            $this->logger->info("Template for file creation not found: " . $templatePath, array("app" => $this->appName));
            return ["error" => $this->trans->t("Template not found")];
        }

        $view = Filesystem::getView();
        if (!$view->file_put_contents($filePath, $template))
        {
            $this->logger->error("Can't create file: " . $filePath, array("app" => $this->appName));
            return ["error" => $this->trans->t("Can't create file")];
        }

        $fileInfo = $view->getFileInfo($filePath);

        if ($fileInfo === false)
        {
            $this->logger->info("File not found: " . $filePath, array("app" => $this->appName));
            return ["error" => $this->trans->t("File not found")];
        }

        $result = Helper::formatFileInfo($fileInfo);
        return $result;
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

        $params = [
            "drawioUrl" => $drawioUrl,
            "theme" => $theme,
            "lang" => $lang,
            "overrideXml" => $overrideXml,
            "fileId" => $fileId
        ];

        $response = new TemplateResponse($this->appName, "editor", $params);

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (isset($drawioUrl) && !empty($drawioUrl))
        {
            $csp->addAllowedScriptDomain($drawioUrl);
            $csp->addAllowedFrameDomain($drawioUrl);
        }
        $response->setContentSecurityPolicy($csp);

        return $response;
    }



    public function load($fileId)
    {
        list ($file, $error) = $this->getFile($fileId);

        if (isset($error))
        {
            $this->logger->error("Load: " . $fileId . " " . $error, array("app" => $this->appName));
            return ["error" => $error];
        }

        $fileName = $file->getName();
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $format = $this->config->formats[$ext];
        if (!isset($format))
        {
            $this->logger->info( $this->trans->t("Format not supported"). " " . $fileName, array("app" => $this->appName));
            return ["error" => $this->trans->t("Format not supported")];
        }

        $mtime = $file->getMtime();
        $content = $file->getContent();

        $params =
        [
            "title" => $fileName,
            "fileType" => pathinfo($fileName, PATHINFO_EXTENSION),
            "documentType" => $format["type"],
            "content" => $content,
            "mtime" => $mtime
        ];

        return $params;
    }


    public function save($fileId)
    {
        $content = $_POST['content'];
        $mtime = isset($_POST['mtime']) ? $_POST['mtime'] : '';

        list ($file, $error) = $this->getFile($fileId);

        if (isset($error))
        {
            $this->logger->error("Save: " . $fileId . " - " . $error, array("app" => $this->appName));
            return ["error" => $error];
        }

        $filePath = $file->getPath();

        $server_mtime = $file->getMtime();
        $server_content = $file->getContent();

        if($server_mtime != $mtime)
        {
            $error = $this->trans->t("The file has changed since opening");
            $this->logger->error("Save: " . $fileId . " - " . $error, array("app" => $this->appName));
            return ["error" => $error];
        }


        if($file->isUpdateable()==false)
        {
            $error = $this->trans->t("User does not have permissions to write to the file:")." $filePath";
            $this->logger->error("Save: " . $fileId . " - " . $error, array("app" => $this->appName));
            return ["error" => $error];
        }

        //taken from the old plugin, don't know if still needed
        $content = iconv(mb_detect_encoding($content), "UTF-8", $content);

        $file->putContent($content);

        $file->stat();

        $newmtime = $file->getMtime();
        $newsize = $file->getSize();

        $params =
        [
            "status" => "ok",
            "mtime" => $newmtime,
            "size" => $newsize
        ];

        return $params;
    }


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