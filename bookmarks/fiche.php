<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *       \file       htdocs/bookmarks/fiche.php
 *       \brief      Page display/creation of bookmarks
 *       \ingroup    bookmark
 *       \version    $Id: fiche.php,v 1.26 2010/08/19 20:29:55 eldy Exp $
 */


require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/bookmarks/class/bookmark.class.php");

$langs->load("other");

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$title=isset($_GET["title"])?$_GET["title"]:$_POST["title"];
$url=isset($_GET["url"])?$_GET["url"]:$_POST["url"];
$target=isset($_GET["target"])?$_GET["target"]:$_POST["target"];
$userid=isset($_GET["userid"])?$_GET["userid"]:$_POST["userid"];
$position=isset($_GET["position"])?$_GET["position"]:$_POST["position"];


/*
 * Actions
 */

if ($action == 'add' || $action == 'addproduct' || $action == 'update')
{
	if ($_POST["cancel"])
	{
		$urlsource=(! empty($_REQUEST["urlsource"]))?$_REQUEST["urlsource"]:((! empty($url))?$url:DOL_URL_ROOT.'/bookmarks/liste.php');
        header("Location: ".$urlsource);
        exit;
	}

    $mesg='';

    $bookmark=new Bookmark($db);
    if ($action == 'update') $bookmark->fetch($_POST["id"]);
    $bookmark->fk_user=$userid;
    $bookmark->title=$title;
    $bookmark->url=$url;
    $bookmark->target=$target;
    $bookmark->position=$position;

    if (! $title) $mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("BookmarkTitle"));
    if (! $url)   $mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("UrlOrLink"));

    if (! $mesg)
    {
        $bookmark->favicon='none';

        if ($action == 'update') $res=$bookmark->update();
        else $res=$bookmark->create();

        if ($res > 0)
        {
			$urlsource=! empty($_REQUEST["urlsource"])?urldecode($_REQUEST["urlsource"]):DOL_URL_ROOT.'/bookmarks/liste.php';
            header("Location: ".$urlsource);
            exit;
        }
        else
        {
        	if ($bookmark->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
        	{
        		$langs->load("errors");
            	$mesg='<div class="warning">'.$langs->trans("WarningBookmarkAlreadyExists").'</div>';
        	}
        	else
        	{
            	$mesg='<div class="error">'.$bookmark->error.'</div>';
        	}
        	$action='create';
        }
    }
    else
    {
        $mesg='<div class="error">'.$mesg.'</div>';
        $action='create';
    }
}

if ($_GET["action"] == 'delete')
{
    $bookmark=new Bookmark($db);
    $bookmark->id=$_GET["bid"];
    $bookmark->url=$user->id;
    $bookmark->target=$user->id;
    $bookmark->title='xxx';
    $bookmark->favicon='xxx';

    $res=$bookmark->remove();
    if ($res > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$bookmark->error.'</div>';
    }
}


/*
 * View
 */

llxHeader();

$html=new Form($db);


if ($action == 'create')
{
    /*
     * Fact bookmark creation mode
     */

    print '<form action="fiche.php" method="post" enctype="multipart/form-data">'."\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print_fiche_titre($langs->trans("NewBookmark"));

    if ($mesg) print "$mesg<br>";

    print '<table class="border" width="100%">';

    print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("BookmarkTitle").'</td><td><input class="flat" name="title" size="30" value="'.$title.'"></td><td>'.$langs->trans("SetHereATitleForLink").'</td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("UrlOrLink").'</td><td><input class="flat" name="url" size="50" value="'.$url.'"></td><td>'.$langs->trans("UseAnExternalHttpLinkOrRelativeDolibarrLink").'</td></tr>';

    print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
    $liste=array(0=>$langs->trans("ReplaceWindow"),1=>$langs->trans("OpenANewWindow"));
    print $html->selectarray('target',$liste,1);
    print '</td><td>'.$langs->trans("ChooseIfANewWindowMustBeOpenedOnClickOnBookmark").'</td></tr>';

    print '<tr><td>'.$langs->trans("Owner").'</td><td>';
    $html->select_users(isset($_POST['userid'])?$_POST['userid']:$user->id,'userid',1);
    print '</td><td>&nbsp;</td></tr>';

    // Position
    print '<tr><td>'.$langs->trans("Position").'</td><td>';
    print '<input class="flat" name="position" size="5" value="'.(isset($_POST["position"])?$_POST["position"]:$bookmark->position).'">';
    print '</td></tr>';

    print '<tr><td colspan="3" align="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("CreateBookmark").'" name="create"> &nbsp; ';
    print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel">';
    print '</td></tr>';

    print '</table>';

    print '</form>';
}


if ($_GET["id"] > 0 && ! preg_match('/^add/i',$_GET["action"]))
{
    /*
     * Fact bookmark mode or visually edition
     */
    $bookmark=new Bookmark($db);
    $bookmark->fetch($_GET["id"]);


    dol_fiche_head($head, $hselected, $langs->trans("Bookmark"),0,'bookmark');

    if ($_GET["action"] == 'edit')
    {
    	print '<form name="edit" method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	print '<input type="hidden" name="action" value="update">';
    	print '<input type="hidden" name="id" value="'.$bookmark->id.'">';
    	print '<input type="hidden" name="urlsource" value="'.DOL_URL_ROOT.'/bookmarks/fiche.php?id='.$bookmark->id.'">';
    }

    print '<table class="border" width="100%">';

    print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>'.$bookmark->ref.'</td></tr>';

    print '<tr><td>'.$langs->trans("BookmarkTitle").'</td><td>';
    if ($_GET["action"] == 'edit') print '<input class="flat" name="title" size="30" value="'.(isset($_POST["title"])?$_POST["title"]:$bookmark->title).'">';
    else print $bookmark->title;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("UrlOrLink").'</td><td>';
    if ($_GET["action"] == 'edit') print '<input class="flat" name="url" size="80" value="'.(isset($_POST["url"])?$_POST["url"]:$bookmark->url).'">';
    else print '<a href="'.(preg_match('/^http/i',$bookmark->url)?$bookmark->url:DOL_URL_ROOT.$bookmark->url).'"'.($bookmark->target?' target="_blank"':'').'>'.$bookmark->url.'</a>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
    if ($_GET["action"] == 'edit')
    {
	    $liste=array(1=>$langs->trans("OpenANewWindow"),0=>$langs->trans("ReplaceWindow"));
	    print $html->selectarray('target',$liste,isset($_POST["target"])?$_POST["target"]:$bookmark->target);
   	}
    else
    {
    	if ($bookmark->target == 0) print $langs->trans("ReplaceWindow");
    	if ($bookmark->target == 1) print $langs->trans("OpenANewWindow");
    }
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Owner").'</td><td>';
    if ($_GET["action"] == 'edit' && $user->admin)
    {
	    $html->select_users(isset($_POST['userid'])?$_POST['userid']:($bookmark->fk_user?$bookmark->fk_user:''),'userid',1);
    }
    else
    {
	    if ($bookmark->fk_user)
	    {
		    $fuser=new User($db);
		    $fuser->fetch($bookmark->fk_user);
		    //$fuser->nom=$fuser->login; $fuser->prenom='';
		    print $fuser->getNomUrl(1);
		}
		else
		{
			print $langs->trans("Public");
		}
    }
    print '</td></tr>';

    // Position
    print '<tr><td>'.$langs->trans("Position").'</td><td>';
    if ($_GET["action"] == 'edit') print '<input class="flat" name="position" size="5" value="'.(isset($_POST["position"])?$_POST["position"]:$bookmark->position).'">';
    else print $bookmark->position;
    print '</td></tr>';

    // Date creation
    print '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.dol_print_date($bookmark->datec,'dayhour').'</td></tr>';

    if ($_GET["action"] == 'edit') print '<tr><td colspan="2" align="center"><input class="button" type="submit" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';


    print '</table>';

    if ($_GET["action"] == 'edit') print '</form>';



    print "</div>\n";



    print "<div class=\"tabsAction\">\n";

    // Edit
    if ($user->rights->bookmark->creer && $_GET["action"] != 'edit')
    {
        print "  <a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?id=".$bookmark->id."&amp;action=edit\">".$langs->trans("Edit")."</a>\n";
    }

    // Remove
    if ($user->rights->bookmark->supprimer && $_GET["action"] != 'edit')
    {
        print "  <a class=\"butActionDelete\" href=\"liste.php?bid=".$bookmark->id."&amp;action=delete\">".$langs->trans("Delete")."</a>\n";
    }

    print '</div>';

}

$db->close();


llxFooter('$Date: 2010/08/19 20:29:55 $ - $Revision: 1.26 $');
?>
