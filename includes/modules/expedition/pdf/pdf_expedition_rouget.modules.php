<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/expedition/pdf/pdf_expedition_rouget.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele Rouget
 *	\version    $Id: pdf_expedition_rouget.modules.php,v 1.53 2010/12/26 20:22:59 kujiu Exp $
 */

require_once DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php";
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');


/**
 *	\class      pdf_expedition_dorade
 *	\brief      Classe permettant de generer les borderaux envoi au modele Rouget
 */
Class pdf_expedition_rouget extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 *	\brief  Constructeur
	 *	\param	db		Database handler
	 */
	function pdf_expedition_rouget($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "rouget";
		$this->description = $langs->trans("DocumentModelSimple");

		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // By default if not defined
	}

	/**
	 *   	\param      pdf     		Objet PDF
	 *   	\param      exp     		Objet expedition
	 *      \param      showadress      0=non, 1=oui
	 *      \param      outputlang		Objet lang cible
	 */
	function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
	{
		global $conf,$langs,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$langs->load("orders");

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SENDING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SENDING_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->nom;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		// Show barcode
		if ($conf->barcode->enabled)
		{
			$posx=105;
		}
		else
		{
			$posx=$this->marge_gauche+3;
		}
		//$pdf->Rect($this->marge_gauche, $this->marge_haute, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 30);
		if ($conf->barcode->enabled)
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}

		$pdf->SetDrawColor(128,128,128);
		if ($conf->barcode->enabled)
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}


		$posx=100;
		$posy=$this->marge_haute;

		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("SendingSheet");
		$pdf->MultiCell(100, 4, $title, '' , 'R');
        $posy+=1;

		$pdf->SetFont('','', $default_font_size + 2);

		$posy+=5;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefSending") ." : ".$object->ref, '', 'R');

		//Date Expedition
		$posy+=5;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->date_delivery,"%d %b %Y",false,$outputlangs,true), '', 'R');

		if (! empty($object->client->code_client))
		{
			$posy+=5;
			$pdf->SetXY(100,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}


		$pdf->SetFont('','', $default_font_size + 4);
	    $Yoff=40;

	    // Add list of linked orders
	    $object->load_object_linked();

	    if ($conf->commande->enabled)
		{
			$outputlangs->load('orders');
			foreach($object->linked_object as $key => $val)
			{
				if ($key == 'commande')
				{
					for ($i = 0; $i<sizeof($val);$i++)
					{
						$newobject=new Commande($this->db);
						$result=$newobject->fetch($val[$i]);
						if ($result >= 0)
						{
							$pdf->SetFont('','', $default_font_size - 2);
							$text=$newobject->ref;
							if ($newobject->ref_client) $text.=' ('.$newobject->ref_client.')';
                            $Yoff = $Yoff+8;
							$pdf->SetXY($this->page_largeur - $this->marge_droite - 60,$Yoff);
							$pdf->MultiCell(60, 4, $outputlangs->transnoentities("RefOrder") ." : ".$outputlangs->transnoentities($text), 0, 'R');
                            $Yoff = $Yoff+4;
                            $pdf->SetXY($this->page_largeur - $this->marge_droite - 60,$Yoff);
                            $pdf->MultiCell(60, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->commande->date,"%d %b %Y",false,$outputlangs,true), 0, 'R');
						}
					}
				}
			}
		}

	}


	/**
	 *		\brief      Fonction generant le document sur le disque
	 *		\param	    object			Objet expedition a generer (ou id si ancienne methode)
	 *		\param		outputlangs		Lang output object
	 * 	 	\return	    int     		1=ok, 0=ko
	 */
	function write_file(&$object, $outputlangs)
	{
		global $user,$conf,$langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$object->fetch_thirdparty();

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!class_exists('TCPDF')) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("propal");
		$outputlangs->load("deliveries");
        $outputlangs->load("sendings");

		if ($conf->expedition->dir_output)
		{
			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output."/sending/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Protection et encryption du pdf
/*				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm',$this->format);
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}
*/
                $pdf=pdf_getInstance($this->format);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->AliasNbPages();

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Sending"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($fac->ref)." ".$outputlangs->transnoentities("Sending"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				$height_note = 180;
				$pdf->Rect($this->marge_gauche, $tab_top-10, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+10);
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note);
				if ($this->barcode->enabled)
				{
					$this->posxdesc=$this->marge_gauche+35;
				}
				else
				{
					$this->posxdesc=$this->marge_gauche+1;
				}
				$this->tableau_top = 80;

				$pdf->SetFont('','', $default_font_size);
				$curY = $this->tableau_top + 4;
				$pdf->writeHTMLCell(100, 3, 12,  $curY, $outputlangs->trans("Description"), 0, 0);
				$curY = $this->tableau_top + 4;
				$pdf->writeHTMLCell(30, 3, 140, $curY, $outputlangs->trans("QtyOrdered"), 0, 0);
				$curY = $this->tableau_top + 4;
				$pdf->writeHTMLCell(30, 3, 170, $curY, $outputlangs->trans("QtyToShip"), 0, 0);

				$nexY = $this->tableau_top + 14;

				for ($i = 0 ; $i < sizeof($object->lines) ; $i++)
				{
					$curY = $nexY;

					if ($this->barcode->enabled)
					{
						$pdf->i25($this->marge_gauche+3, ($curY - 2), "000000".$object->lines[$i]->fk_product, 1, 8);
					}

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page

					// Description de la ligne produit
					//$libelleproduitservice=pdf_getlinedesc($object,$i,$outputlangs);
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,150,3,$this->posxdesc,$curY);
					//$pdf->writeHTMLCell(150, 3, $this->posxdesc, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					$pdf->SetXY (160, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_asked);

					$pdf->SetXY (186, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_shipped);

					$nexY+=2;    // Passe espace entre les lignes
				}


				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}


	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		PDF factory
	 * 		\param		object			Object invoice
	 *      \param      outputlangs		Object lang for output
	 * 		\remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'SENDING_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}

}

?>
