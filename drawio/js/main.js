/**
 * Copyright (c) 2017 Pawel Rojek <pawel@pawelrojek.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

(function (OCA) {

    OCA.Drawio = _.extend({}, OCA.Drawio);

    if (!OCA.Drawio.AppName)
    {
        OCA.Drawio = {
            AppName: "drawio"
        };
    }


    OCA.Drawio.CreateFile = function (name, fileList)
    {
        var dir = fileList.getCurrentDirectory();

        var winEditor = window.open("");
        if (winEditor)
        {
            winEditor.document.write(t(OCA.Drawio.AppName, "Loading, please wait."));
            winEditor.document.close();
        }

        $.post(OC.generateUrl("apps/" + OCA.Drawio.AppName + "/ajax/new"),
            {
                name: name,
                dir: dir
            },
            function onSuccess(response)
            {
                if (response.error)
                {
                    winEditor.close();
                    var row = OC.Notification.show(response.error);
                    setTimeout(function ()
                    {
                       OC.Notification.hide(row);
                    }, 2500);
                    return;
                }

                fileList.add(response, { animate: true });
                OCA.Drawio.OpenEditor(response.id, winEditor);

                var row = OC.Notification.show(t(OCA.Drawio.AppName, "File created"));
                setTimeout(function ()
                {
                    OC.Notification.hide(row);
                }, 2500);
            }
        );
    };

    OCA.Drawio.OpenEditor = function (fileId, winEditor)
    {
        var url = OC.generateUrl("/apps/" + OCA.Drawio.AppName + "/{fileId}",
            {
                fileId: fileId
            });

        if (winEditor && winEditor.location)
        {
            winEditor.location.href = url;
        }
        else
        {
            winEditor = window.open(url, "_blank");
        }
    };

    OCA.Drawio.FileClick = function (fileName, context, attr)
    {
        var fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName);
        var fileList = context.fileList;

        OCA.Drawio.OpenEditor(fileInfoModel.id);
    };

    OCA.Drawio.FileList = {
        attach: function (fileList) {
            if (fileList.id == "trashbin")
            {
                return;
            }

            var override_default = true;

            $.get(OC.generateUrl("apps/" + OCA.Drawio.AppName + "/ajax/settings"),
                function onSuccess(mimes)
                {

                    OCA.Drawio.mimes = mimes;
                    $.each(mimes, function (ext, attr)
                    {
                        fileList.fileActions.registerAction({
                            name: "drawioOpen",
                            displayName: t(OCA.Drawio.AppName, "Open in Draw.io"),
                            mime: attr.mime,
                            permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE,
                            icon: function () {
                                return OC.imagePath(OCA.Drawio.AppName, "btn-edit");
                            },
                            actionHandler: function (fileName, context) {
                                OCA.Drawio.FileClick(fileName, context, attr);
                            }
                        });

                        if ( (fileList.fileActions.getDefaultFileAction(attr.mime, "file", OC.PERMISSION_READ) == false) || (override_default) )
                        {
                            fileList.fileActions.setDefault(attr.mime, "drawioOpen");
                        }

                    });
                }
            );
        }
    };

    OCA.Drawio.NewFileMenu =
    {
        attach: function (menu)
        {
            var fileList = menu.fileList;

            if (fileList.id !== "files")
            {
                return;
            }

            menu.addMenuEntry({
                id: "drawioXML",
                displayName: t(OCA.Drawio.AppName, "Diagram"),
                iconClass: "icon-drawio-new-xml",
                fileType: "xml",
                actionHandler: function (name) {
                    OCA.Drawio.CreateFile(name + ".xml", fileList);
                }
            });

        }
    };
})(OCA);

OC.Plugins.register("OCA.Files.FileList", OCA.Drawio.FileList);
OC.Plugins.register("OCA.Files.NewFileMenu", OCA.Drawio.NewFileMenu);
