--
-- $Id: repair.sql,v 1.3 2010/11/04 18:51:33 eldy Exp $
--
-- Script to repair some fatal errors due to database corruption
-- when current version is 2.6.0 or higher. 
--


-- Requests to clean corrupted database

-- V4.1 delete from llx_facturedet where fk_facture in (select rowid from llx_facture where facnumber = '(PROV)');
delete from llx_facture where facnumber = '(PROV)';
-- V4.1 delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '(PROV)');
delete from llx_commande where ref = '(PROV)';
-- V4.1 delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '(PROV)');
delete from llx_propal where ref = '(PROV)';
-- V4.1 delete from llx_facturedet where fk_facture in (select rowid from llx_facture where facnumber = '');
delete from llx_facture where facnumber = '';
-- V4.1 delete from llx_commandedet where fk_commande in (select rowid from llx_commande where ref = '');
delete from llx_commande where ref = '';
-- V4.1 delete from llx_propaldet where fk_propal in (select rowid from llx_propal where ref = '');
delete from llx_propal where ref = '';
