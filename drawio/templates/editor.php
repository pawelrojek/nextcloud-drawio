<?php
    style("drawio", "editor");
    script("drawio", "editor");

    $frame_params = "?embed=1";
    if ($_["offlineMode"] == "yes")
    {
        $frame_params .= "&offline=1&stealth=1";
    }
    if (!empty($_["theme"])) $frame_params .= "&ui=".$_["theme"];
    if (!empty($_["lang"])) $frame_params .= "&lang=".$_["lang"];
    if (!empty($_["drawioUrlArgs"])) $frame_params .= "&".$_["drawioUrlArgs"];
    $frame_params .= "&spin=1&proto=json";
?>

<div id="app">

    <iframe id="iframeEditor" width="100%" height="100%" align="top" frameborder="0" name="iframeEditor" onmousewheel="" allowfullscreen=""></iframe>

    <script type="text/javascript" nonce="<?php p(base64_encode($_["requesttoken"])) ?>" defer>
        window.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($_['error'])) { ?>
                OCA.DrawIO.DisplayError("<?php p($_['error']) ?>");
            <?php } else { ?>
                var iframe = $("#iframeEditor")[0];
                var filePath = "<?php echo urldecode($_['filePath']); ?>";
                var originUrl = "<?php p($_['drawioUrl']); ?>";
                var drawIoUrl = "<?php p($_['drawioUrl']); print_unescaped($frame_params); ?>"
                OCA.DrawIO.EditFile(iframe.contentWindow, filePath, originUrl);
                iframe.setAttribute('src', drawIoUrl );
            <?php } ?>
        });
    </script>

</div>
