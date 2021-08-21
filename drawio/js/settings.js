/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

(function ($, OC) {

    $(document).ready(function () {
        OCA.Drawio = _.extend({}, OCA.Drawio);
        if (!OCA.Drawio.AppName)
        {
            OCA.Drawio = {
                AppName: "drawio"
            };
        }

        $("#drawioSave").click(function ()
        {
            var f_drawioUrl = $("#drawioUrl").val().trim();
            var f_offlineMode = $("#offlineMode option:selected").val();
            var f_theme = $("#theme option:selected").val();
            var f_lang = $("#lang").val().trim();
            var f_autosave = $("#drawioAutosave option:selected").val();
            var f_libraries = $("#drawioLibraries option:selected").val();

            var saving = OC.Notification.show( t(OCA.Drawio.AppName, "Saving...") );

            var settings = {
                    drawioUrl: f_drawioUrl,
                    offlineMode: f_offlineMode,
                    theme: f_theme,
                    lang: f_lang,
                    autosave: f_autosave,
                    libraries: f_libraries
            };


            $.ajax({
                method: "POST",
                url: OC.generateUrl("apps/"+ OCA.Drawio.AppName + "/ajax/settings"),
                data: settings,
                success: function onSuccess(response)
                {
                    OC.Notification.hide(saving);

                    if (response && response.drawioUrl != null)
                    {
                        $("#drawioUrl").val(response.drawioUrl);
                        $("#offlineMode").val(response.offlineMode);
                        $("#theme").val(response.theme);
                        $("#lang").val(response.lang);
                        $("#drawioAutosave").val(response.drawioAutosave);
                        $("#drawioLibraries").val(response.libraries);

                        var message =
                            response.error
                                ? (t(OCA.Drawio.AppName, "Error when trying to connect") + " (" + response.error + ")")
                                : t(OCA.Drawio.AppName, "Settings have been successfully saved");
                        var row = OC.Notification.show(message);
                        setTimeout(function () {
                            OC.Notification.hide(row);
                        }, 2500);
                    }
                }
            });
        });

        $("#drawioUrl, #lang").keypress(function (e)
        {
            var code = e.keyCode || e.which;
            if (code === 13) $("#drawioSave").click();
        });
    });

})(jQuery, OC);
