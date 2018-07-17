<?php
require '../config.php';
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/compta/facture/class/facture.class.php');

if((float)DOL_VERSION >= 7)
{
    print "Début de conversion des lignes de propale<br>";
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."propaldet WHERE special_code = 104777 AND qty != 50 AND product_type = 9 AND (label = '' OR label IS NULL)";
    $res = $db->query($sql);
    if($res)
    {
        print $db->num_rows($res)." lignes à convertir<br>";
        $i = 0;
        while ($obj = $db->fetch_object($res))
        {
            $prop = new PropaleLigne($db);
            $prop->fetch($obj->rowid);
            $prop->fetch_optionals();
            if (empty($prop->label)){
                $prop->label = strip_tags($prop->desc);
                $prop->desc = '';
            }
            $ret = $prop->update(1);
            if($ret>0) $i++;
        }
        print $i." lignes converties<br>";
    }
    
    print "Début de conversion des lignes de commande<br>";
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commandedet WHERE special_code = 104777 AND qty != 50 AND product_type = 9 AND (label = '' OR label IS NULL)";
    $res = $db->query($sql);
    if($res)
    {
        print $db->num_rows($res)." lignes à convertir<br>";
        $i = 0;
        while ($obj = $db->fetch_object($res))
        {
            $cdeline = new OrderLine($db);
            $cdeline->fetch($obj->rowid);
            $cdeline->fetch_optionals($obj->rowid);
            if (empty($cdeline->label)){
                $cdeline->label = strip_tags($cdeline->desc);
                $cdeline->desc = '';
            }
            $ret = $cdeline->update($user, 1);
            if($ret>0) $i++;
        }
        
        print $i." lignes converties<br>";
    }
    
    print "Début de conversion des lignes de facture<br>";
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facturedet WHERE special_code = 104777 AND qty != 50 AND product_type = 9 AND (label = '' OR label IS NULL)";
    
    $res = $db->query($sql);
    if($res)
    {
        print $db->num_rows($res)." lignes à convertir<br>";
        $i = 0;
        while ($obj = $db->fetch_object($res))
        {
            $facline = new FactureLigne($db);
            $facline->fetch($obj->rowid);
            $facline->fetch_optionals($obj->rowid);
            if (empty($facline->label)){
                $facline->label = strip_tags($facline->desc);
                $facline->desc = '';
            }
            $ret = $facline->update($user, 1);
            if($ret>0) $i++;
        }
        
        print $i." lignes converties<br>";
    }
    
}