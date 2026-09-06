<?php
declare(strict_types=1);
namespace Arcates\Services;
final class PdfPriceList
{
    public static function build(string $title,array $rows,float $discountPercent=0): string
    {
        $lines=[$title,''];foreach($rows as $row){$price=max(0,(float)$row['price']*(1-$discountPercent/100));$lines[]=self::ascii((string)$row['sku'].' | '.(string)$row['product_name'].' - '.(string)$row['variant_name'].' | '.number_format($price,2,'.','').' TL');}
        $stream="BT /F1 11 Tf 50 790 Td 14 TL\n";foreach($lines as $i=>$line){if($i>0)$stream.="T*\n";$stream.='('.self::escape($line).") Tj\n";}$stream.="ET";
        $objects=[];$objects[]='1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';$objects[]='2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';$objects[]='3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >> endobj';$objects[]='4 0 obj << /Length '.strlen($stream).' >> stream' . "\n".$stream."\nendstream endobj";$objects[]='5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
        $pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as $obj){$offsets[]=strlen($pdf);$pdf.=$obj."\n";}$xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";for($i=1;$i<=count($objects);$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";$pdf.='trailer << /Size '.(count($objects)+1).' /Root 1 0 R >>' . "\nstartxref\n{$xref}\n%%EOF";return $pdf;
    }
    private static function escape(string $s): string{return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);}
    private static function ascii(string $s): string{$map=['ş'=>'s','Ş'=>'S','ğ'=>'g','Ğ'=>'G','ü'=>'u','Ü'=>'U','ö'=>'o','Ö'=>'O','ç'=>'c','Ç'=>'C','ı'=>'i','İ'=>'I'];return preg_replace('/[^\x20-\x7E]/','?',strtr($s,$map))??'';}
}
