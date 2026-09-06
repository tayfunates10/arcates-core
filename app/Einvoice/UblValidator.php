<?php
declare(strict_types=1);
namespace Arcates\Einvoice;

final class UblValidator
{
    public static function inspect(string $xml,int $maxBytes=2097152): array
    {
        $xml=trim($xml);if($xml===''||strlen($xml)>$maxBytes)throw new \RuntimeException('UBL-TR XML boş veya izin verilen boyutu aşıyor.');if(preg_match('/<!\s*(DOCTYPE|ENTITY)/i',$xml))throw new \RuntimeException('UBL-TR XML içinde DTD/ENTITY kullanılamaz.');if(!class_exists(\DOMDocument::class))throw new \RuntimeException('e-Belge doğrulaması için PHP DOM/XML eklentisi gerekli.');
        $prev=libxml_use_internal_errors(true);$doc=new \DOMDocument();$ok=$doc->loadXML($xml,LIBXML_NONET|LIBXML_NOBLANKS);$errors=libxml_get_errors();libxml_clear_errors();libxml_use_internal_errors($prev);if(!$ok||$doc->documentElement===null)throw new \RuntimeException('UBL-TR XML iyi biçimli değil'.($errors?': '.trim($errors[0]->message):'.'));
        if($doc->documentElement->localName!=='Invoice')throw new \RuntimeException('UBL-TR kök elemanı Invoice olmalı.');$xp=new \DOMXPath($doc);$profile=trim((string)$xp->evaluate('string(/*[local-name()="Invoice"]/*[local-name()="ProfileID"][1])'));$currency=trim((string)$xp->evaluate('string(/*[local-name()="Invoice"]/*[local-name()="DocumentCurrencyCode"][1])'));$uuid=trim((string)$xp->evaluate('string(/*[local-name()="Invoice"]/*[local-name()="UUID"][1])'));if($profile==='')throw new \RuntimeException('UBL-TR ProfileID alanı gerekli.');if($currency==='')throw new \RuntimeException('UBL-TR DocumentCurrencyCode alanı gerekli.');
        return ['xml'=>$xml,'profile_id'=>mb_substr($profile,0,80),'currency'=>mb_substr($currency,0,10),'uuid'=>$uuid,'sha256'=>hash('sha256',$xml)];
    }
}
