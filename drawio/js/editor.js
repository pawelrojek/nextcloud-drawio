/**
 *
 * @author Pawel Rojek <pawel at pawelrojek.com>
 * @author Ian Reinhart Geiser <igeiser at devonit.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

(function (OCA) {

    // ADD SUPPORT TO IE
    if (!String.prototype.includes) {
        String.prototype.includes = function(search, start) {
            if (typeof start !== 'number') {
                start = 0;
            }
            if (start + search.length > this.length) {
                return false;
            } else {
                return this.indexOf(search, start) !== -1;
            }
        };
    }

    OCA.DrawIO = _.extend({}, OCA.DrawIO);
    if (!OCA.DrawIO.AppName) {
        OCA.DrawIO = {
            AppName: "drawio"
        };
    }

    OCA.DrawIO.DisplayError = function (error) {
        $("#app")
        .text(error)
        .addClass("error");
    };

    OCA.DrawIO.Cleanup = function (receiver, filePath) {
        window.removeEventListener("message", receiver);

        var ncClient = OC.Files.getClient();
        ncClient.getFileInfo(filePath)
        .then(function (status, fileInfo) {
            var url = OC.generateUrl("/apps/files/?dir={currentDirectory}&fileid={fileId}", {
                currentDirectory: fileInfo.path,
                fileId: fileInfo.id
            });
            window.location.href = url;
        })
        .fail(function () {
            var url = OC.generateUrl("/apps/files");
            window.location.href = url;
        });
    };

    OCA.DrawIO.EditFile = function (editWindow, filePath, origin) {
        var ncClient = OC.Files.getClient();
        var receiver = function (evt) {
            if (evt.data.length > 0 && origin.includes(evt.origin)) {
                var payload = JSON.parse(evt.data);
                if (payload.event === "init") {
                    var loadMsg = OC.Notification.show(t(OCA.DrawIO.AppName, "Loading, please wait."));
                    ncClient.getFileContents(filePath)
                    .then(function (status, contents) {
                        if (contents === " ") {
                            editWindow.postMessage(JSON.stringify({
                                action: "template",
                                name: filePath
                            }), "*");
                        } else if (contents.indexOf("mxGraphModel") !== -1) {
                            // TODO: show error to user
                            OCA.DrawIO.Cleanup(receiver, filePath);
                        } else {
                            editWindow.postMessage(JSON.stringify({
                                action: "load",
                                xml: contents
                            }), "*");
                        }
                    })
                    .fail(function (status) {
                        console.log("Status Error: " + status);
                        // TODO: show error on failed read
                        OCA.DrawIO.Cleanup(receiver, filePath);
                    })
                    .done(function () {
                        OC.Notification.hide(loadMsg);
                    });
                } else if (payload.event === "load") {
                    // TODO: notify user of loaded
                } else if (payload.event === "export") {
                    // TODO: handle export event
                } else if (payload.event === "save") {
                    var saveMsg = OC.Notification.show(t(OCA.DrawIO.AppName, "Saving..."));
                    ncClient.putFileContents(
                        filePath,
                        payload.xml, {
                            contentType: "x-application/drawio",
                            overwrite: false
                        }
                    )
                    .then(function (status) {
                        OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File saved!"));
                    })
                    .fail(function (status) {
                        // TODO: handle on failed write
                        OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File not saved!"));
                    })
                    .done(function () {
                        OC.Notification.hide(saveMsg);
                    });
                } else if (payload.event === "exit") {
                    OCA.DrawIO.Cleanup(receiver, filePath);
                } else {
                    console.log("DrawIO Integration: unknown event " + payload.event);
                    console.dir(payload);
                }
            } else {
                console.log("DrawIO Integration: bad origin " + evt.origin);
            }
        }
        window.addEventListener("message", receiver);
    }
    
    OCA.DrawIO.EditFileWImport = function (editWindow, filePath, origin) {
        var ncClient = OC.Files.getClient();
        var supportedExtension = "drawio"

        // Check extension
        var isSupportedExt = filePath.split('.').pop() == supportedExtension
        // Response arraybuffer for non XML files
        if (isSupportedExt) {
            var responseType = ""
            var fileToSave = filePath
        } else {
            var responseType = "arraybuffer"
            var date = new Date()
            var datetime =  "Opened_" +
                            date.getDate() + "-" +
                            (date.getMonth()+1) + "-" +
                            date.getFullYear() + "_T" +
                            date.getHours() + "." +
                            date.getMinutes()
            var fileToSave = filePath + "_" + datetime + "." + supportedExtension
        }

        var receiver = function (evt) {
            if (evt.data.length > 0 && origin.includes(evt.origin)) {
                var payload = JSON.parse(evt.data);
                if (payload.event === "init") {
                    var loadMsg = OC.Notification.show(t(OCA.DrawIO.AppName, "Loading, please wait."));
                    ncClient.getFileContentsRT(filePath, null, responseType)
                    .then(function (status, contents) {
                        if(isSupportedExt) {
                            if (contents === " ") {
                                editWindow.postMessage(JSON.stringify({
                                    action: "template",
                                    name: filePath
                                }), "*");
                            } else if (contents.indexOf("mxGraphModel") !== -1) {
                                // TODO: show error to user
                                OCA.DrawIO.Cleanup(receiver, filePath);
                            } else {
                                editWindow.postMessage(JSON.stringify({
                                    action: "load",
                                    xml: contents
                                }), "*");
                            }
                        } else {
                            blob = new Blob([contents], {type: "application/vnd.visio"})

                            reader = new FileReader();
                            reader.onloadend = function() {
                                editWindow.postMessage(JSON.stringify({
                                    action: "load",
                                    title: fileToSave,
                                    xml: reader.result
                                }), "*");
                            }
                            reader.readAsDataURL(blob);
                        }
                    })
                    .fail(function (status) {
                        console.log("Status Error: " + status);
                        // TODO: show error on failed read
                        OCA.DrawIO.Cleanup(receiver, filePath);
                    })
                    .done(function () {
                        OC.Notification.hide(loadMsg);
                    });
                } else if (payload.event === "load") {
                    // TODO: notify user of loaded
                } else if (payload.event === "export") {
                    // TODO: handle export event
                } else if (payload.event === "save") {
                    var saveMsg = OC.Notification.show(t(OCA.DrawIO.AppName, "Saving..."));
                    ncClient.putFileContents(
                        fileToSave,
                        payload.xml, {
                            contentType: "x-application/drawio",
                            overwrite: false
                        }
                    )
                    .then(function (status) {
                        OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File saved as " + fileToSave));
                    })
                    .fail(function (status) {
                        // TODO: handle on failed write
                        OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File not saved!"));
                    })
                    .done(function () {
                        OC.Notification.hide(saveMsg);
                    });
                } else if (payload.event === "exit") {
                    OCA.DrawIO.Cleanup(receiver, filePath);
                } else {
                    console.log("DrawIO Integration: unknown event " + payload.event);
                    console.dir(payload);
                }
            } else {
                console.log("DrawIO Integration: bad origin " + evt.origin);
            }
        }
        window.addEventListener("message", receiver);
    }
})(OCA);
