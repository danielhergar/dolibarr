-- ===================================================================
-- Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
-- $Id: llx_document_generator.sql,v 1.1 2009/10/07 18:18:10 eldy Exp $
-- ===================================================================

create table llx_document_generator
(
  rowid           integer UNSIGNED NOT NULL PRIMARY KEY,
  name            varchar(255) NOT NULL,
  classfile       varchar(255) NOT NULL,
  class           varchar(255) NOT NULL

)type=innodb;
