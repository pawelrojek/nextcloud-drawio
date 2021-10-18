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

    OCA.DrawIO.EditFile = function (editWindow, filePath, origin,  autosave) {
        var ncClient = OC.Files.getClient();
        var autosaveEnabled = autosave === "yes";
        var fileId = $("#iframeEditor").data("id");
        var shareToken = $("#iframeEditor").data("sharetoken");
        var etag = null;
        var draftEtag = null; // cache the remote Draft's ETag
        var saveInProgress = false;
        var draftShown = false; // was the draftModal used recently?
        var tempDiagramXml = null; // used if chosen to overwrite the remote Draft
        if (!fileId && !shareToken) {
            displayError(t(OCA.DrawIO.AppName, "FileId is empty"));
            return;
        }
        if(shareToken) {
            var fileUrl = OC.generateUrl("apps/" + OCA.DrawIO.AppName + "/ajax/shared/{fileId}", { fileId: fileId || 0 });
            var params = [];
            if (filePath) {
                params.push("filePath=" + encodeURIComponent(filePath));
            }
            if (shareToken) {
                params.push("shareToken=" + encodeURIComponent(shareToken));
            }
            if (params.length) {
                fileUrl += "?" + params.join("&");
            }
        }
        var receiver = function (evt) {
            if (evt.data.length > 0 && origin.includes(evt.origin)) {
                var payload = JSON.parse(evt.data);
                if (payload.event === "init") {
                    var loadMsg = OC.Notification.show(t(OCA.DrawIO.AppName, "Loading, please wait."));
		    if(!fileId) {
		        $.ajax({
        		    url: fileUrl,
		            success: function onSuccess(data) {
                                    editWindow.postMessage(JSON.stringify({
		                            action: "load",
	                                    xml: data
    		                    }), "*");
                		    OC.Notification.hide(loadMsg);
			    },
			    fail: function (status) {

                                console.log("Status Error: " + status);
	                        // TODO: show error on failed read
    	                        OCA.DrawIO.Cleanup(receiver, filePath);
			    },
			    done: function() {
                                OC.Notification.hide(loadMsg);
			    }
			});
		    } else {
                    webdavUrl =  OC.getProtocol() + '://' + OC.getHost() + OC.webroot + '/remote.php/dav/files/' + OC.currentUser + filePath;                                        
                    
                    $.get(webdavUrl)
                    .then(function (contents, result, response) {
                        etag = response.getResponseHeader('etag');                                             
                        if (contents === " ") {
                            OCA.DrawIO.NewFileMode = true; //[workaround] "loading" file without content, to display "template" later in "load" callback event without another filename prompt
                            editWindow.postMessage(JSON.stringify({
                                action: "load",
                                autosave: Number(autosaveEnabled)
                            }), "*");
                        } else if (contents.indexOf("mxfile") == -1 || contents.indexOf("diagram") == -1) {
                            // TODO: show error to user
                            OCA.DrawIO.Cleanup(receiver, filePath);
                        } else {
                            OCA.DrawIO.NewFileMode = false;
                            editWindow.postMessage(JSON.stringify({
                                action: "load",
                                autosave: Number(autosaveEnabled),
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
        }
                } else if (payload.event === "template") {
                  //template selected
                } else if (payload.event === "load") {
                   if (OCA.DrawIO.NewFileMode) {
                       editWindow.postMessage(JSON.stringify({
                             action: "template"
                      }), "*");
                   }
                } else if (payload.event === "export") {
                    // TODO: handle export event
                } else if (payload.event === "autosave") {

                    if (!saveInProgress) {
                        var time = new Date();
                        saveInProgress = true;

                        $.ajax({
                            url: webdavUrl,
                            type: 'PUT',
                            data: payload.xml,
                            contentType: 'application/x-drawio',
                            beforeSend: function(request){
                                request.setRequestHeader('If-Match', etag); // Server will check if Remote-ETag matches this local etag
                            },                   
                        })
                        .then(function (status, content, result) {                        
                            etag = result.getResponseHeader('etag');
                            
                            editWindow.postMessage(JSON.stringify({
                                action: 'status',
                                message: "Autosave successful at " + time.toLocaleTimeString(),
                                modified: false
                            }), '*');
                            saveInProgress = false;
                        })
                        .fail(function (result) {
                            saveInProgress = false; 

                            if (result.status == 412) { // Wrong ETag -> 412="Precondition Failed"
                                OC.Notification.showUpdate(t(OCA.DrawIO.AppName, "Error: Conflict, please try saving manually."));	
                                editWindow.postMessage(JSON.stringify({
                                    action: 'status',
                                    message: "Error: Conflict, please try saving manually.",
                                    modified: false
                                }), '*');
                            } else {
                                editWindow.postMessage(JSON.stringify({
                                    action: 'status',
                                    message: "Autosave failed at " + time.toLocaleTimeString(),
                                    modified: false
                                }), '*');
                            }
                        });

                    }

                } else if (payload.event === "save") {                                            

                    if (!saveInProgress) {
                        var saveMsg = OC.Notification.show(t(OCA.DrawIO.AppName, "Saving...")); 

                        saveInProgress = true;
                        $.ajax({
                            url: webdavUrl,
                            type: 'PUT',
                            data: payload.xml,
                            contentType: 'application/x-drawio',
                            beforeSend: function(request){
                                request.setRequestHeader('If-Match', etag); // Server will check if Remote-ETag matches this local etag
                            },                   
                        })
                        .then(function (status, content, result) {                        
                            etag = result.getResponseHeader('etag');                           
                            OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File saved!"));
                        })
                        .fail(function (result) {
                            // TODO: handle on failed write
                            saveInProgress = false;
                            OC.Notification.hide(saveMsg);

                            if (result.status == 412) { // Wrong ETag -> 412="Precondition Failed"

                                // Let the User decide whether to Overwrite the remote file, or work the remote Draft
                                $.get(webdavUrl)
                                .then(function (contents, result, response) {
                                        draftEtag = response.getResponseHeader('etag'); 

                                        tempDiagramXml = payload.xml; // Cache the current version, to perhaps save it in draft Modal
                                        draftShown = true; // Draft Modal was opened
                                        editWindow.postMessage(JSON.stringify({
                                            action: 'draft',
                                            name: 'Remote File',                                            
                                            editKey: 'Load',
                                            discardKey: 'Overwrite',                                                                                                                    
                                            xml: contents
                                        }), '*');
                                    
                                })
                                .fail(function (status) {
                                    console.log("Status Error: " + status);
                                    // TODO: show error on failed read
                                    OCA.DrawIO.Cleanup(receiver, filePath);
                                });

                            };
                            OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File not saved!"));
                        })
                        .done(function () {
                            OC.Notification.hide(saveMsg);
                            saveInProgress = false;
                        });
                    }                    
                } else if (payload.event === "exit") {
                    // Only Exit the Drawio Editor, if the usual "Exit" Button was used
                    // dont exit, when the Draft Modal's Cancel/Exit Button was used
                    if (!draftShown) {
                        OCA.DrawIO.Cleanup(receiver, filePath);
                    }
                    draftShown = false;
                } else if (payload.event === "draft") {                                      
                    draftShown = false;
                    switch (payload.result) {
                        case "edit":    // Load and use the Remote Draft Version
                            etag = draftEtag;
                            editWindow.postMessage(JSON.stringify({
                                action: 'load',                                
                                xml: payload.message.xml,                                
                            }), '*');
                            break;

                        case "discard":  // Overwrite the Remote Version                            
                            saveInProgress = true;
                            var time = new Date();                         
                            $.ajax({
                                url: webdavUrl,
                                type: 'PUT',
                                data: tempDiagramXml,
                                contentType: 'application/x-drawio',                                
                            })
                            .then(function (status, content, result) {                                          
                                editWindow.postMessage(JSON.stringify({
                                    action: 'status',
                                    message: "Save successful at " + time.toLocaleTimeString(),
                                    modified: false
                                }), '*');
                                OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "File saved!"));
                                etag = result.getResponseHeader('etag'); 
                                saveInProgress = false;
                            })
                            .fail(function (result) {                                            
                                saveInProgress = false; 
                                OC.Notification.showTemporary(t(OCA.DrawIO.AppName, "Error while saving."));	
                            })                                        
                            .done(function () {                                            
                                saveInProgress = false;
                                tempDiagramXml = null;
                            });
                            break;

                    }

                } else {
                    console.log("DrawIO Integration: unknown event " + payload.event);
                    draftShown = false;
                }
            } else {
                console.log("DrawIO Integration: bad origin " + evt.origin);
            }
        }
        window.addEventListener("message", receiver);
    }
})(OCA);