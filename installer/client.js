/*
 +-----------------------------------------------------------------------+
 | Roundcube installer client function                                   |
 |                                                                       |
 | This file is part of the Roundcube Webmail client                     |
 | Copyright (C) The Roundcube Dev Team                                  |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <roundcube@gmail.com>                        |
 +-----------------------------------------------------------------------+
*/

function toggleblock(id, link) {
    var block = document.getElementById(id);

    return false;
}


function addhostfield() {
    var container = document.getElementById('defaulthostlist');
    var row = document.createElement('div');
    var input = document.createElement('input');
    var link = document.createElement('a');

    input.name = '_imap_host[]';
    input.size = '30';
    link.href = '#';
    link.onclick = function () {
        removehostfield(this.parentNode); return false;
    };
    link.className = 'removelink';
    link.innerHTML = 'remove';

    row.appendChild(input);
    row.appendChild(link);
    container.appendChild(row);
}


function removehostfield(row) {
    var container = document.getElementById('defaulthostlist');
    container.removeChild(row);
}

function smtp_auth_switch(input)
{
    var user = document.getElementById('cfgsmtpuser');
    var pass = document.getElementById('cfgsmtppass');
    if (input.checked) {
        user.value = '%u';
        pass.value = '%p';
    } else {
        if (user.value == '%u') {
            user.value = '';
        }
        if (pass.value == '%p') {
            pass.value = '';
        }
    }
}
