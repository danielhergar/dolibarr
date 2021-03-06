<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin <patrick.raguin@gmail.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/lib/format_cards.lib.php
 *	\brief      Set of functions used for cards generation
 *	\ingroup    core
 *	\version    $Id: format_cards.lib.php,v 1.1 2010/02/11 22:48:10 eldy Exp $
 */



global $_Avery_Labels;


$_Avery_Labels = array (
			      '5160'=>array('name'=>'5160',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>1.762,
					    'marginTop'=>10.7,
					    'NX'=>3,
					    'NY'=>10,
					    'SpaceX'=>3.175,
					    'SpaceY'=>0,
					    'width'=>66.675,
					    'height'=>25.4,
					    'font-size'=>8),
			      '5161'=>array('name'=>'5161',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>0.967,
					    'marginTop'=>10.7,
					    'NX'=>2,
					    'NY'=>10,
					    'SpaceX'=>3.967,
					    'SpaceY'=>0,
					    'width'=>101.6,
					    'height'=>25.4,
					    'font-size'=>8),
			      '5162'=>array('name'=>'5162',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>0.97,
					    'marginTop'=>20.224,
					    'NX'=>2,
					    'NY'=>7,
					    'SpaceX'=>4.762,
					    'SpaceY'=>0,
					    'width'=>100.807,
					    'height'=>35.72,
					    'font-size'=>8),
			      '5163'=>array('name'=>'5163',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>1.762,
					    'marginTop'=>10.7,
					    'NX'=>2,
					    'NY'=>5,
					    'SpaceX'=>3.175,
					    'SpaceY'=>0,
					    'width'=>101.6,
					    'height'=>50.8,
					    'font-size'=>8),
			      '5164'=>array('name'=>'5164',
					    'paper-size'=>'letter',
					    'metric'=>'in',
					    'marginLeft'=>0.148,
					    'marginTop'=>0.5,
					    'NX'=>2,
					    'NY'=>3,
					    'SpaceX'=>0.2031,
					    'SpaceY'=>0,
					    'width'=>4.0,
					    'height'=>3.33,
					    'font-size'=>12),
			      '8600'=>array('name'=>'8600',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>7.1,
					    'marginTop'=>19,
					    'NX'=>3,
					    'NY'=>10,
					    'SpaceX'=>9.5,
					    'SpaceY'=>3.1,
					    'width'=>66.6,
					    'height'=>25.4,
					    'font-size'=>8),
			      'L7163'=>array('name'=>'L7163',
					     'paper-size'=>'A4',
					     'metric'=>'mm',
					     'marginLeft'=>5,
					     'marginTop'=>15,
					     'NX'=>2,
					     'NY'=>7,
					     'SpaceX'=>25,
					     'SpaceY'=>0,
					     'width'=>99.1,
					     'height'=>38.1,
					     'font-size'=>10),
			      'AVERYC32010'=>array('name'=>'AVERY-C32010',
					     'paper-size'=>'A4',
					     'metric'=>'mm',
					     'marginLeft'=>15,
					     'marginTop'=>13,
					     'NX'=>2,
					     'NY'=>5,
					     'SpaceX'=>10,
					     'SpaceY'=>0,
					     'width'=>85,
					     'height'=>54,
					     'font-size'=>10),
					'CARD'=>array('name'=>'Dolibarr cards',
					    'paper-size'=>'A4',
					    'metric'=>'mm',
					    'marginLeft'=>15,
					    'marginTop'=>15,
					    'NX'=>2,
					    'NY'=>5,
					    'SpaceX'=>0,
					    'SpaceY'=>0,
					    'width'=>85,
					    'height'=>54,
					    'font-size'=>10)
		);


?>