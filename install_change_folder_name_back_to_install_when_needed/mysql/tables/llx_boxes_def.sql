-- ============================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
-- Copyright (C) 2010      Laurent Destailleur  <eldy@users.sourceforge.net>
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
-- $Id: llx_boxes_def.sql,v 1.4 2010/09/24 11:15:24 hregis Exp $
-- ===========================================================================

create table llx_boxes_def
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  file        varchar(200) NOT NULL,        -- Do not increase this as file+note must be small to allow index
  entity      integer DEFAULT 1 NOT NULL,	-- multi company id
  tms         timestamp,  
  note        varchar(130)                  -- Do not increase this as file+note must be small to allow index
)type=innodb;
