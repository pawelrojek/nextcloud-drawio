(function(OCA) {

    OCA.DrawIO = _.extend({}, OCA.DrawIO);
    if (!OCA.DrawIO.AppName) {
        OCA.DrawIO = {
            AppName: "drawio"
        };
    }

    OCA.DrawIO.EditFileNewWindow = function(filePath) {

        var ncClient = OC.Files.getClient();
        ncClient.getFileContents(filePath).then(function(status, contents) {

            // TODO: test for javascript popup blocker
            var editWindow = window.open("https://www.draw.io/?embed=1&spin=1&proto=json");
            var receiver = function(evt) {
                if (evt.data.length > 0 && evt.origin === "https://www.draw.io") {

                    var payload = JSON.parse(evt.data);

                    if (payload.event === "init") {
                        editWindow.postMessage(JSON.stringify({
                            action: "load",
                            xml: contents
                        }), "*");
                    } else if (payload.event === "load"){
                        // TODO: show notification of loading
                    } else if (payload.event === "save"){
                        ncClient.putFileContents(
                                filePath,
                                payload.xml, {
                                    contentType: "x-application/drawio",
                                    overwrite: false
                                }
                            ).then(function(status) {
                                // TODO: show tell fileList to update
                            }).fail(function(status) {
                                console.log("Status Error: " + status);
                                // TODO: show error on failed write
                            });
                    } else if (payload.event === "exit"){
                        window.removeEventListener('message', receiver);
                        editWindow.close();
                    }
                }
            }
            window.addEventListener('message', receiver);
        }).fail(function(status) {
            console.log("Status Error: " + status);
            // TODO: show error on failed read
        });

    }

    OCA.DrawIO.FileList = {
        attach: function(fileList) {
            if (fileList.id == "trashbin") {
                return;
            }

            fileList.fileActions.registerAction({
                name: "drawIoOpenDrawIO",
                displayName: t(OCA.DrawIO.AppName, "Open in DrawIO"),
                mime: "x-application/drawio",
                permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE,
                icon: function() {
                    return OC.imagePath(OCA.DrawIO.AppName, "btn-edit.png");
                },
                actionHandler: function(fileName, context) {
                    var dir = fileList.getCurrentDirectory();
                    OCA.DrawIO.EditFileNewWindow(OC.joinPaths(dir, fileName));
                }
            });

            fileList.fileActions.registerAction({
                name: "drawIoOpenXML",
                displayName: t(OCA.DrawIO.AppName, "Open in DrawIO"),
                mime: "application/xml",
                permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE,
                icon: function() {
                    return OC.imagePath(OCA.DrawIO.AppName, "btn-edit.png");
                },
                actionHandler: function(fileName, context) {
                    var dir = fileList.getCurrentDirectory();
                    OCA.DrawIO.EditFileNewWindow(OC.joinPaths(dir, fileName));
                }
            });

            fileList.fileActions.setDefault("x-application/drawio", "drawIoOpenDrawIO");
        }
    };

    OCA.DrawIO.NewFileMenu = {
        attach: function(menu) {
            var fileList = menu.fileList;

            if (fileList.id !== "files") {
                return;
            }

            menu.addMenuEntry({
                id: "drawIoDiagram",
                displayName: t(OCA.DrawIO.AppName, "Diagram"),
                templateName: t(OCA.DrawIO.AppName, 'New Diagram.drawio'),
                iconClass: "icon-filetype-text",
                fileType: "x-application/drawio",
                actionHandler: function(fileName) {
                    var dir = fileList.getCurrentDirectory();
                    fileList.createFile(fileName).then(function() {
                        var filePath = OC.joinPaths(dir, fileName);
                        var diagramTemplate = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><mxGraphModel dx=\"1430\" dy=\"713\" grid=\"1\" gridSize=\"10\" guides=\"1\" tooltips=\"1\" connect=\"1\" arrows=\"1\" fold=\"1\" page=\"1\" pageScale=\"1\" pageWidth=\"850\" pageHeight=\"1100\" background=\"#ffffff\" math=\"0\" shadow=\"0\"><root><mxCell id=\"0\"/><mxCell id=\"1\" parent=\"0\"/></root></mxGraphModel>";
                        var ncClient = OC.Files.getClient();
                        ncClient.putFileContents(
                            filePath,
                            diagramTemplate, {
                                contentType: "x-application/drawio",
                                overwrite: false
                            }
                        ).then(function(status) {
                            OCA.DrawIO.EditFileNewWindow(filePath);
                        }).fail(function(status) {
                            console.log("Status Error: " + status);
                            // TODO: show error on failed write
                        });

                    });
                }
            });

        }
    };
})(OCA);

OC.Plugins.register("OCA.Files.NewFileMenu", OCA.DrawIO.NewFileMenu);
OC.Plugins.register("OCA.Files.FileList", OCA.DrawIO.FileList);
