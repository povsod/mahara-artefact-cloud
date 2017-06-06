# mahara-cloud

This is a cloud artefact plugin for Mahara e-portfolio system. It allows integration of several cloud services into Mahara.

## Description

This plugin provides users with several new block types, one for each cloud service enabled.
Users can place these blocks into their pages in order to display a selection of items
from their account on a cloud service. They can also download items from some of the cloud
services, into their Mahara file storage area.

## Requirements

Your site admin will need to have an API key for each of the cloud services you want to
enable. Additionally, each of your users will need to have a user account in any of the
cloud services they want to use.

The configuration page for each cloud blocktype contains instructions about how to register
for an API key on each service.

## Installation

1. Create a directory under your Mahara installation: `htdocs/artefact/cloud`
2. Copy the contents of this repository into that directory.
3. Apply the patch file "htdocs_view_blocks.php", to hack the htdocs/view/blocks.php file to include the necessary Javascript. (Or make the change manually, as described in "install.txt")
    cd /path/to/mahara
    patch -p0 < htdocs/artefact/cloud/htdocs_view_blocks.patch
4. Run the normal Mahara upgrade process.
5. Log in to your Mahara site as an admin.
6. Go to "Administration -> Extensions -> Plugin administration"
7. Under the "Plugin type: blocktype" list, find the list of blocktypes that start with "cloud/".
8. Hide each cloud blocktype that you don't wish to use by clicking "Hide" next to it.
9. Click the configuration icon next to each cloud blocktype you will be using, and fill in the necessary configuration information. You may need to register an API key with the cloud provider. Each configuration page has instructions on how to do so.

## Usage

1. Log in as a normal Mahara user.
2. Go to "Content -> Cloud services"
3. Use the controls on this page to log in to any of the cloud services you wish to share in your Portfolio. You may need to register a user account with the given cloud service.
4. Now when you create a page, you can drag a blocktype for that cloud service into the page, and set it to display a selection of items from that cloud service.

## License and copyright

Copyright (C) 2012-2016 Gregor Anzelj, and others including:
* Aaron Wells at Catalyst IT

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 or later of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Additional permission under GNU GPL version 3 section 7:

If you modify this program, or any covered work, by linking or
combining it with the OpenSSL project's OpenSSL library (or a
modified version of that library), containing parts covered by the
terms of the OpenSSL or SSLeay licenses, the Mahara copyright holders
grant you additional permission to convey the resulting work.
Corresponding Source for a non-source form of such a combination
shall include the source code for the parts of OpenSSL used as well
as that of the covered work.