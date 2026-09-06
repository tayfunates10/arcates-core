<?php
declare(strict_types=1);
define('ARCATES_ROOT',dirname(__DIR__,2));
echo "########## Q. Çeviri dosyası bütünlüğü (lang/) ##########\n";
$files=glob(ARCATES_ROOT.'/lang/*.php');
$sets=[];
foreach($files as $f){ $k=basename($f,'.php'); $sets[$k]=(array)require $f; printf("   lang/%s.php : %d anahtar\n",$k,count($sets[$k])); }
$all=[]; foreach($sets as $k=>$v)$all=array_merge($all,array_keys($v)); $all=array_values(array_unique($all));
sort($all);
echo "   Birleşik anahtar sayısı: ".count($all)."\n";
foreach($sets as $loc=>$v){
    $missing=array_values(array_diff($all,array_keys($v)));
    printf("   %-4s eksik: %d %s\n",$loc,count($missing),$missing?('-> '.implode(', ',array_slice($missing,0,12)).(count($missing)>12?' …':'')):'');
}
// boş / TR ile aynı bırakılmış çeviriler
foreach($sets as $loc=>$v){
    if($loc==='tr')continue;
    $same=[]; $empty=[];
    foreach($v as $k=>$val){ if(trim((string)$val)==='')$empty[]=$k; elseif(isset($sets['tr'][$k])&&$val===$sets['tr'][$k])$same[]=$k; }
    printf("   %-4s boş: %-3d | TR ile birebir aynı: %-3d %s\n",$loc,count($empty),count($same),$same?('('.implode(', ',array_slice($same,0,8)).')'):'');
}
