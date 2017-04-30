/**
 * Copyright (c) 2017 Pawel Rojek <pawel@pawelrojek.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 *
 **/

(function ($, OCA)
{

    OCA.Drawio = _.extend({}, OCA.Drawio);

    if (!OCA.Drawio.AppName)
    {
        OCA.Drawio = {
            AppName: "drawio"
        };
    }

    OCA.Drawio.DURL = "";
    OCA.Drawio.LoadedXML = null;
    OCA.Drawio.DrawioInit = false;
    OCA.Drawio.FileId = null;
    OCA.Drawio.ModTime = null;


    OCA.Drawio.SetServerURL = function(url)
    {
        url = $.trim(url);
        url = url.replace(/\/$/, '');
        OCA.Drawio.DURL = url;
    };

    OCA.Drawio.SaveFile = function(xml)
    {
        var saving = OC.Notification.show( t(OCA.Drawio.AppName, "Saving...") );
        $.ajax({
            method: "POST",
            url: OC.generateUrl("apps/" + OCA.Drawio.AppName + "/ajax/save/" + OCA.Drawio.FileId),
            data: { content: xml, id: OCA.Drawio.FileId, mtime: OCA.Drawio.ModTime },
            success: function onSuccess(json)
            {
                OC.Notification.hide(saving);
                if (json.status=="ok")
                {
                    OCA.Drawio.ModTime = json.mtime;
                    OC.Notification.showTemporary( t(OCA.Drawio.AppName, "File saved!") );
                }
                else
                {
                    var err = json.error;
                    alert( t(OCA.Drawio.AppName, err) );
                }
            }
        });
    };


    OCA.Drawio.DisplayError = function(error)
    {
        $("#app").text(error).addClass("error");
    };


    OCA.Drawio.LoadFile = function (fileId)
    {
        if (!fileId.length)
        {
            OCA.Drawio.DisplayError( t(OCA.Drawio.AppName, "Error: FileId is empty!") );
            return;
        }

        $.ajax({
            url: OC.generateUrl("apps/" + OCA.Drawio.AppName + "/ajax/load/" + fileId),
            success: function onSuccess(file)
            {
                OCA.Drawio.FileId = fileId;
                OCA.Drawio.LoadedXML = file.content;
                OCA.Drawio.ModTime = file.mtime;
                OCA.Drawio.SendXML(); //[race] either Draw.io inits first or the xml loads, doesn't matter
            }
        });
    };


    OCA.Drawio.SendXML = function()
    {
        if ( (!OCA.Drawio.DrawioInit) || (OCA.Drawio.LoadedXML==null) ) return; //[race]
        var editWindow = document.getElementById('iframeEditor').contentWindow;
        editWindow.postMessage( JSON.stringify({
            action: 'load',
            xml: OCA.Drawio.LoadedXML
        }), "*");
    };


    OCA.Drawio.RegisterListener = function()
    {
        var receive = function(evt)
        {
            var editWindow = document.getElementById('iframeEditor').contentWindow;

            if ( (evt.data.length > 0) && (evt.origin == OCA.Drawio.DURL) )
            {
                   var msg = JSON.parse(evt.data);
                   if (msg.event == 'init')
                   {
                        OCA.Drawio.DrawioInit = true;
                        OCA.Drawio.SendXML(); //[race] either Draw.io inits first or the xml loads, doesn't matter
                   }
                   else if (msg.event == 'load')
                   {
                        //XML Loaded OK
                   }
                   else if (msg.event == 'save')
                   {
                        OCA.Drawio.SaveFile(msg.xml);
                   }
                   else if (msg.event == 'exit')
                   {
                        window.removeEventListener('message', receive);
                        window.close();
                   }
                   else
                   {
                        console.log('DrawIO Integration: Unsupported event: '+msg.event);
                        console.dir(msg);
                   }

            }
            else
            {
                console.log('DrawIO Integration: Incorrect origin:' +msg.event);
            }
        };

        window.addEventListener('message', receive);
    };


})(jQuery, OCA);