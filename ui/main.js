//
// This is the main app for the ttn module
//
function va3ned_ttn_main() {
    //
    // The panel to list the net
    //
    this.menu = new M.panel('Training Net', 'va3ned_ttn_main', 'menu', 'mc', 'medium', 'sectioned', 'va3ned.ttn.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search net',
            'noData':'No net found',
            },
        'nets':{'label':'Net', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Name', 'Start Date', 'Status'],
            'noData':'No net',
            'addTxt':'Add Net',
            'addFn':'M.va3ned_ttn_main.edit.open(\'M.va3ned_ttn_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('va3ned.ttn.netSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.va3ned_ttn_main.menu.liveSearchShow('search',null,M.gE(M.va3ned_ttn_main.menu.panelUID + '_' + s), rsp.nets);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.va3ned_ttn_main.net.open(\'M.va3ned_ttn_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'nets' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.start_date_text;
                case 2: return d.status_text;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'nets' ) {
            return 'M.va3ned_ttn_main.net.open(\'M.va3ned_ttn_main.menu.open();\',\'' + d.id + '\',M.va3ned_ttn_main.net.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('va3ned.ttn.netList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.va3ned_ttn_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to display Net
    //
    this.net = new M.panel('Net', 'va3ned_ttn_main', 'net', 'mc', 'large mediumaside', 'sectioned', 'va3ned.ttn.main.net');
    this.net.data = null;
    this.net.net_id = 0;
    this.net.sections = {
        'details':{'label':'Net', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.va3ned_ttn_main.edit.open(\'M.va3ned_ttn_main.net.open();\',M.va3ned_ttn_main.net.net_id);',
            },
        'add':{'label':'Add Participant', 'aside':'yes', 'fields':{
            'callsign':{'label':'Callsign', 'autofocus':'yes', 'livesearch':'yes', 'required':'yes', 'type':'text', 
//                'onkeyupFn':'M.va3ned_ttn_main.net.keyup();',
//                'enterFn':'M.va3ned_ttn_main.net.switchFocus(\'name\');',
//                'tabFn':'M.va3ned_ttn_main.net.switchFocus(\'name\');',
                'livesearch':'yes',
                },
            'name':{'label':'Name', 'type':'text',
//                'onkeyupFn':'M.va3ned_ttn_main.net.keyup();',
//                'enterFn':'M.va3ned_ttn_main.net.switchFocus(\'email\');',
//                'tabFn':'M.va3ned_ttn_main.net.switchFocus(\'email\');',
                },
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Net Control'}}},
            'email':{'label':'Email', 'type':'text',
//                'onkeyupFn':'M.va3ned_ttn_main.net.keyup();',
//                'enterFn':'M.va3ned_ttn_main.net.addParticipant();',
//                'tabFn':'M.va3ned_ttn_main.net.switchFocus(\'callsign\');',
                },
            }},
        '_addbuttons':{'label':'', 'aside':'yes', 'buttons':{
            'add':{'label':'Add Participant', 'fn':'M.va3ned_ttn_main.net.addParticipant();'},
            }},
        'participants':{'label':'Participants', 'type':'simplegrid', 'num_cols':3,
            'cellClasses':['', '', 'alignright'],
            'headerValues':['Callsign', 'Email', ''],
            'noData':'No callsigns added',
            },
        'messages':{'label':'Messages', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Callsign', 'Email', 'Message', ''],
            'noData':'No messages added',
            },
    }
    this.net.fieldValue = function(s, i, d) {
        return '';
    }
    this.net.cellValue = function(s, i, j, d) {
        if( s == 'details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'participants' ) {
            switch(j) {
                case 0: return d.callsign;
                case 1: return d.email;
                case 2: return '<button onclick="M.va3ned_ttn_main.net.newMessage(' + d.id + ');">New Message</button>'
                    + ' <button onclick="M.va3ned_ttn_main.net.removeParticipant(' + d.id + ');">Delete</button>';
            }
        }
        if( s == 'messages' ) {
            switch(j) {
                case 0: return d.callsign;
                case 1: return d.number;
                case 2: return d.message + ' <span class="subdue">' + d.signature + '</span>';
                case 3: return '<button onclick="M.va3ned_ttn_main.net.resentMessage(' + d.id + ');">Resend</button>';
            }
        }
    }
    this.net.liveSearchCb = function(s, i, value) {
        M.api.getJSONBgCb('va3ned.ttn.participantSearch', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) {
            M.va3ned_ttn_main.net.liveSearchShow(s, i, M.gE(M.va3ned_ttn_main.net.panelUID + '_' + i), rsp['participants']);
});
    }
    this.net.liveSearchResultValue = function(s, f, i, j, d) {
        return d.callsign + ' - ' + d.name;
    }
    this.net.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.va3ned_ttn_main.net.addSearchParticipant(\'' + escape(d.callsign) + '\',\'' + escape(d.name) + '\',\'' + escape(d.email) + '\');';
    }
    this.net.switchFocus = function(f) {
        var e = M.gE(this.panelUID + '_' + f);
        e.focus();
    }
    this.net.keyup = function() {
        console.log('testing');
        return false;
    }
    this.net.newMessage = function(pid) {
        M.api.getJSONCb('va3ned.ttn.messageAdd', {'tnid':M.curTenantID, 'net_id':this.net_id, 'participant_id':pid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.va3ned_ttn_main.net;
            p.data.messages = rsp.net.messages;
            p.refreshSection('messages');
        });
    }
    this.net.resendMessage = function(mid) {
        M.api.getJSONCb('va3ned.ttn.messageResend', {'tnid':M.curTenantID, 'net_id':this.net_id, 'message_id':mid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.va3ned_ttn_main.net;
            p.data.messages = rsp.net.messages;
            p.refreshSection('messages');
        });
    }
    this.net.addSearchParticipant = function(callsign, name, email) {
        var c = '&callsign=' + M.eU(unescape(callsign))
            + '&name=' + M.eU(unescape(name))
            + '&email=' + M.eU(unescape(email));
        M.api.postJSONCb('va3ned.ttn.participantAdd', {'tnid':M.curTenantID, 'net_id':this.net_id}, c, this.openFinish);
    }
    this.net.addParticipant = function() {
        if( !this.checkForm() ) {
            return false;
        }
        var c = this.serializeForm('yes');
        M.api.postJSONCb('va3ned.ttn.participantAdd', {'tnid':M.curTenantID, 'net_id':this.net_id}, c, this.openFinish);
    }
    this.net.removeParticipant = function(i) {
        if( confirm('Are you sure you want to remove this participant?') ) {
            M.api.getJSONCb('va3ned.ttn.participantDelete', {'tnid':M.curTenantID, 'net_id':this.net_id, 'participant_id':i}, this.openFinish);
        }
    }
    this.net.open = function(cb, nid, list) {
        if( nid != null ) { this.net_id = nid; }
        if( list != null ) { this.nplist = list; }
        if( cb != null ) { this.cb = cb; }
        M.api.getJSONCb('va3ned.ttn.netGet', {'tnid':M.curTenantID, 'net_id':this.net_id}, this.openFinish);
    }
    this.net.openFinish = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.va3ned_ttn_main.net;
        p.data = rsp.net;
        p.data.details = [
            {'label':'Name', 'value':rsp.net.name},
            {'label':'Status', 'value':rsp.net.status_text},
            {'label':'Start', 'value':rsp.net.start_utc_date + ' ' + rsp.net.start_utc_time},
            {'label':'End', 'value':rsp.net.end_utc_date + ' ' + rsp.net.end_utc_time},
            ];
        p.refresh();
        p.show();
    }
    this.net.addButton('edit', 'Edit', 'M.va3ned_ttn_main.edit.open(\'M.va3ned_ttn_main.net.open();\',M.va3ned_ttn_main.net.net_id);');
    this.net.addClose('Back');

    //
    // The panel to edit Net
    //
    this.edit = new M.panel('Net', 'va3ned_ttn_main', 'edit', 'mc', 'medium', 'sectioned', 'va3ned.ttn.main.edit');
    this.edit.data = null;
    this.edit.net_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Pending', '50':'Active', '90':'Closed'}},
            'start_utc_date':{'label':'Start Date', 'required':'yes', 'type':'date', 'hint':'UTC'},
            'start_utc_time':{'label':'Start Time', 'required':'yes', 'type':'text', 'size':'small'},
            'end_utc_date':{'label':'End Date', 'required':'yes', 'type':'date', 'hint':'UTC'},
            'end_utc_time':{'label':'End Time', 'required':'yes', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.va3ned_ttn_main.edit.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.va3ned_ttn_main.edit.net_id > 0 ? 'yes' : 'no'; },
                'fn':'M.va3ned_ttn_main.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'va3ned.ttn.netHistory', 'args':{'tnid':M.curTenantID, 'net_id':this.net_id, 'field':i}};
    }
    this.edit.open = function(cb, nid, list) {
        if( nid != null ) { this.net_id = nid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('va3ned.ttn.netGet', {'tnid':M.curTenantID, 'net_id':this.net_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.va3ned_ttn_main.edit;
            p.data = rsp.net;
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.va3ned_ttn_main.edit.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.net_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('va3ned.ttn.netUpdate', {'tnid':M.curTenantID, 'net_id':this.net_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('va3ned.ttn.netAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.va3ned_ttn_main.edit.net_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.edit.remove = function() {
        if( confirm('Are you sure you want to remove net?') ) {
            M.api.getJSONCb('va3ned.ttn.netDelete', {'tnid':M.curTenantID, 'net_id':this.net_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.va3ned_ttn_main.edit.close();
            });
        }
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.net_id) < (this.nplist.length - 1) ) {
            return 'M.va3ned_ttn_main.edit.save(\'M.va3ned_ttn_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.net_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.net_id) > 0 ) {
            return 'M.va3ned_ttn_main.edit.save(\'M.va3ned_ttn_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.net_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.va3ned_ttn_main.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'va3ned_ttn_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
