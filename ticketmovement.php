<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       tpvmercao/ticketmovement.php
 *	\ingroup    tpvmercao
 *	\brief      Creates movement and updates credit from a TPV ticket
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/sociasmercao/class/modadherents.class.php';
require_once DOL_DOCUMENT_ROOT.'/sociasmercao/class/movements.class.php';

$entitytotest = $conf->entity;

// Load invoice# from URL
$invoiceid = GETPOST('invoiceid', 'int');

// Create objects for invoice, adherent and modadherent 
$myFacture = new Facture($db);
$myAdherent = new Adherent($db);
$myModAdherent = new ModAdherents($db);
$myMovement = new Movements($db);

// Load the invoice from ticket
$myFacture->fetch($invoiceid);

// Get the ref, third (societe) id and total with taxes from invoice
$invoiceref = $myFacture->ref;
$socid = $myFacture->socid;
$amount = $myFacture->total_ttc;

// Load the adherent from third id
$myAdherent->fetch(null, null, $socid);

// Get the adherent id
$adherentid = $myAdherent->id;

// And finally load modadherent for adherent id (Great investigation! maybe we shoud simply put socid in fk_soc field of adherent when creating? )
$myModAdherent->fetch($adherentid);

// Now we go with the magic

 // 1. Write down the adherent
$myMovement->ref = $adherentid;
// 2. Write down the invoice ref
$myMovement->description = "Ticket/Factura " . $invoiceref;
// 3. Write down the previous credit
$myMovement->credit_prev = $myModAdherent->credit;
// 4. Write down the amount and charge it to adherent credit
$myMovement->amount = $amount * -1;
$myModAdherent->credit -= $amount;        
// 5. Write down the final credit
$myMovement->credit_final = $myModAdherent->credit;
// 6. Not pretty sure what that does...
$myMovement->quota_type = $myModAdherent->quota_type;
// 7. ...nor that other
$myMovement->status = 1;
// 8. Movement type is payment. Maybe we want a new type for tpv payments?
$myMovement->mov_type = 20;
// And done!
$myMovement->create($user);
$myModAdherent->update($user);
//echo $myMovement->errors[0];

echo "Factura: " . $invoiceid . "</br>";
echo "Cliente: " . $socid . "</br>";
echo "Socix: " . $adherentid . "(" . $myAdherent->firstname . ")</br>";
echo "Total: " . $amount . "</br>";
echo "Saldo: " . $myModAdherent->credit . "</br>";
echo "Tipo de cuota " . $myModAdherent->quota_type . "</br>";

//$myModAdherente = new ModAdherent();
//$myMovement = new Movement();

//echo Facture->
