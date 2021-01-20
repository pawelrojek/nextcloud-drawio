<?php
    style("drawio", "editor");
    script("drawio", "editor");

    $frame_params = "?embed=1";
    if ($_["drawioOfflineMode"] === "yes")
    {
        $frame_params .= "&offline=1&stealth=1";
    }
    if (!empty($_["drawioTheme"])) $frame_params .= "&ui=".$_["drawioTheme"];
    if (!empty($_["drawioLang"])) $frame_params .= "&lang=".$_["drawioLang"];
    if (!empty($_["drawioUrlArgs"])) $frame_params .= "&".$_["drawioUrlArgs"];
    $frame_params .= "&spin=1&proto=json";
?>

<div id="app-content">

    <iframe id="iframeEditor" data-id="<?php p($_["fileId"]) ?>" data-path="<?php p($_["filePath"]) ?>" data-sharetoken="<?php p($_["shareToken"]) ?>" width="100%" height="100%" align="top" frameborder="0" name="iframeEditor" onmousewheel="" allowfullscreen=""></iframe>

    <script type="text/javascript" nonce="<?php p(base64_encode($_["requesttoken"])) ?>" defer>
        window.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($_['error'])) { ?>
                OCA.DrawIO.DisplayError("<?php p($_['error']) ?>");
            <?php } else { ?>
                var iframe = $("#iframeEditor")[0];
                var filePath = <?php echo json_encode(urldecode($_['drawioFilePath'])); ?>;
                var originUrl = "<?php p($_['drawioUrl']); ?>";
                var drawIoUrl = "<?php p($_['drawioUrl']); print_unescaped($frame_params); ?>"
                var autosave = "<?php p($_['drawioAutosave']); ?>";
                OCA.DrawIO.EditFile(iframe.contentWindow, filePath, originUrl, autosave);
                iframe.setAttribute('src', drawIoUrl);
            <?php } ?>
        });
    </script>

</div>
