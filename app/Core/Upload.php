<?php
declare(strict_types=1);
namespace Arcates\Core;
final class Upload
{
    private const EXTENSIONS=['jpg','jpeg','png','webp'];
    private const MIMES=['image/jpeg','image/png','image/webp'];
    public static function validateCandidate(string $name,int $size,string $mime,int $maxBytes): bool
    {
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION)); return in_array($ext,self::EXTENSIONS,true)&&in_array($mime,self::MIMES,true)&&$size>0&&$size<=$maxBytes;
    }
    public static function image(array $file,int $maxWidth=1920): array
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new \RuntimeException('Yükleme hatası.');
        $tmp=(string)$file['tmp_name']; $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp)?:''; $max=(int)App::config('security.max_upload_bytes',5242880);
        if(!self::validateCandidate((string)$file['name'],(int)$file['size'],$mime,$max))throw new \RuntimeException('Dosya tipi veya boyutu geçersiz.');
        if(!function_exists('imagecreatefromjpeg'))throw new \RuntimeException('GD eklentisi gerekli.');
        $source=match($mime){'image/jpeg'=>imagecreatefromjpeg($tmp),'image/png'=>imagecreatefrompng($tmp),'image/webp'=>imagecreatefromwebp($tmp),default=>false};
        if(!$source)throw new \RuntimeException('Görsel okunamadı.');
        $w=imagesx($source);$h=imagesy($source);$tw=min($w,$maxWidth);$th=(int)round($h*($tw/max(1,$w)));$target=imagecreatetruecolor($tw,$th);imagealphablending($target,false);imagesavealpha($target,true);imagecopyresampled($target,$source,0,0,0,0,$tw,$th,$w,$h);
        $name=Security::randomToken(16).'.webp';$dir=ARCATES_ROOT.'/uploads/'.date('Y/m');if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new \RuntimeException('Upload dizini oluşturulamadı.');$path=$dir.'/'.$name;
        if(!imagewebp($target,$path,82))throw new \RuntimeException('WebP yazılamadı.');imagedestroy($source);imagedestroy($target);
        return ['path'=>str_replace(ARCATES_ROOT,'',$path),'width'=>$tw,'height'=>$th,'mime'=>'image/webp'];
    }
}
