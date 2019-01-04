<?php
    style("drawio", "settings");
    script("drawio", "settings");
?>
<div id="drawio" class="section section-drawio">
    <h2>Draw.io</h2>

    <p class="drawio-header"><?php p($l->t("Draw.io URL")) ?></p>
    <input id="drawioUrl" value="<?php p($_["drawioUrl"]) ?>" placeholder="https://<drawio-url>" type="text">

    <p class="drawio-header">
    <label for='theme'><?php p($l->t("Theme:")) ?></label>
    <select id="theme">
      <option value="kennedy"<?php if ($_["drawioTheme"]=="kennedy") echo ' selected'; ?>><?php p($l->t("Kennedy")) ?></option>
      <option value="minimal"<?php if ($_["drawioTheme"]=="minimal") echo ' selected'; ?>><?php p($l->t("Minimal")) ?></option>
      <option value="atlas"<?php if ($_["drawioTheme"]=="atlas") echo ' selected'; ?>><?php p($l->t("Atlas")) ?></option>
      <option value="dark"<?php if ($_["drawioTheme"]=="dark") echo ' selected'; ?>><?php p($l->t("Dark")) ?></option>
    </select>
    </p>

    <p class="drawio-header">
    <label for='lang'><?php p($l->t("Language")) ?></label>
    <input id="lang" value="<?php p($_["drawioLang"]) ?>" placeholder="<<?php p($l->t("auto or en,fr,de,es,ru,pl,zh,jp...")) ?>>" type="text">
    </p>

    <p class="drawio-header">
    <label for='overrideXml'><?php p($l->t("Associate XML files with Draw.io?")) ?>
    <select id="overrideXml">
      <option value="yes"<?php if ($_["drawioOverrideXml"]=="yes") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
      <option value="no"<?php if ($_["drawioOverrideXml"]=="no") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
    </select>
    </p>

    <p><?php p($l->t("Please note: when you disable the XML association, you need to manually register the MIME type application/x-drawio for the extension \".drawio\".")) ?></p>

    <p class="drawio-header">
    <label for='offlineMode'><?php p($l->t("Activate offline mode in Draw.io?")) ?>
    <select id="offlineMode">
      <option value="yes"<?php if ($_["drawioOfflineMode"]=="yes") echo ' selected'; ?>><?php p($l->t("Yes")) ?></option>
      <option value="no"<?php if ($_["drawioOfflineMode"]=="no") echo ' selected'; ?>><?php p($l->t("No")) ?></option>
    </select>
    </p>

    <p><?php p($l->t("When the \"offline mode\" is active, this disables all remote operations and features to protect the users privacy. Draw.io will then also only be in English, even if you set a different language manually.")) ?></p>

    <br />
    <a id="drawioSave" class="button"><?php p($l->t("Save")) ?></a>
</div>
