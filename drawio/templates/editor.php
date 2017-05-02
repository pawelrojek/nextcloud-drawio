<?php
    style("drawio", "editor");
    script("drawio", "editor");

    $frame_params = "?embed=1";
    if (!empty($_["theme"])) $frame_params .= "&ui=".$_["theme"];
    if (!empty($_["lang"])) $frame_params .= "&lang=".$_["lang"];
    $frame_params .= "&spin=1&proto=json";
?>

<div id="app">

    <iframe id="iframeEditor" src="<?php if (!empty($_["drawioUrl"])) { p($_["drawioUrl"]); p($frame_params); } ?>" width="100%" height="100%" align="top" frameborder="0" name="iframeEditor" onmousewheel="" allowfullscreen=""></iframe>

    <script type="text/javascript" nonce="<?php p(base64_encode($_["requesttoken"])) ?>">
        $( document ).ready(function()
        {
        <?php if (!empty($_['error'])) { ?>
            OCA.Drawio.DisplayError("<?php p($_['error']) ?>");
        <?php } else { ?>
            // load file
            var filePath = "<?php p($_['filePath']); ?>";
            var drawioUrl = "<?php p($_['drawioUrl']); ?>";
        <?php } ?>
        });
    </script>

</div>
