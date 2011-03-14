<?php
/* Copyright (C) 2004-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**	    \file       htdocs/webcalendar/webcal.php
        \ingroup    webcalendar
		\brief      Page generant 2 frames, une pour le menu Dolibarr, l'autre pour l'affichage du calendrier
		\author	    Laurent Destailleur
		\version    $Id: webcal.php,v 1.1 2010/05/13 15:55:15 hregis Exp $
*/

require("../main.inc.php");

if (empty($conf->global->PHPWEBCALENDAR_URL))
{
	llxHeader();
	print '<div class="error">Module Webcalendar was not configured properly.</div>';
	llxFooter('$Date: 2010/05/13 15:55:15 $ - $Revision: 1.1 $');
}

$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
$leftmenu=isset($_GET["leftmenu"])?$_GET["leftmenu"]:"";
$idmenu=isset($_GET["idmenu"])?$_GET["idmenu"]:"";

print "
<html>
<head>
<title>Dolibarr frame for Webcalendar</title>
</head>

<frameset rows=\"".$heightforframes.",*\" border=0 framespacing=0 frameborder=0>
    <frame name=\"barre\" src=\"webcaltop.php?mainmenu=".$mainmenu."&leftmenu=".$leftmenu."&idmenu=".$idmenu."&nobackground=1\" noresize scrolling=\"NO\" noborder>
    <frame name=\"main\" src=\"".$conf->global->PHPWEBCALENDAR_URL."\">
    <noframes>
    <body>

    </body>
    </noframes>
</frameset>

<noframes>
<body>
	<br><center>
	Sorry, your browser is too old or not correctly configured to view this area.<br>
	Your browser must support frames.<br>
	</center>
</body>
</noframes>

</html>
";


?>
