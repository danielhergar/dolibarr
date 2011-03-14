-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- $Id: llx_c_methode_commande_fournisseur.sql,v 1.1 2009/10/25 07:42:55 hregis Exp $
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (1, 'OrderByMail',  'Courrier',  1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (2, 'OrderByFax',   'Fax',       1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (3, 'OrderByEMail', 'EMail',     1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (4, 'OrderByPhone', 'Téléphone', 1);
INSERT INTO llx_c_methode_commande_fournisseur (rowid, code, libelle, active) VALUES (5, 'OrderByWWW',   'En ligne',  1);