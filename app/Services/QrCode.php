<?php
declare(strict_types=1);
namespace Arcates\Services;

final class QrCode
{
    private int $version;
    private int $size;
    private array $modules=[];
    private array $function=[];

    public static function svg(string $text,int $scale=6,int $border=4): string
    {
        $qr=new self($text);$dim=($qr->size+$border*2)*$scale;$path=[];
        for($y=0;$y<$qr->size;$y++){for($x=0;$x<$qr->size;$x++){if($qr->modules[$y][$x]){$xx=($x+$border)*$scale;$yy=($y+$border)*$scale;$path[]="M{$xx},{$yy}h{$scale}v{$scale}h-{$scale}z";}}}
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$dim.' '.$dim.'" width="'.$dim.'" height="'.$dim.'" role="img" aria-label="QR kod"><rect width="100%" height="100%" fill="#fff"/><path d="'.implode('',$path).'" fill="#000"/></svg>';
    }

    private function __construct(string $text)
    {
        $bytes=array_values(unpack('C*',$text)?:[]);$configs=[1=>[19,7,17],2=>[34,10,32],3=>[55,15,53],4=>[80,20,78],5=>[108,26,106]];$this->version=0;
        foreach($configs as $v=>$cfg){if(count($bytes)<=$cfg[2]){$this->version=$v;break;}}if($this->version===0)throw new \RuntimeException('QR menü URL adresi 106 bayttan uzun.');
        [$dataWords,$eccWords]=$configs[$this->version];$this->size=17+4*$this->version;$this->modules=array_fill(0,$this->size,array_fill(0,$this->size,false));$this->function=array_fill(0,$this->size,array_fill(0,$this->size,false));
        $this->drawFunctions();$data=$this->encode($bytes,$dataWords);$all=array_merge($data,$this->reedSolomon($data,$eccWords));$bits=[];foreach($all as $b){for($i=7;$i>=0;$i--)$bits[]=(($b>>$i)&1)===1;}$this->drawData($bits);$this->drawFormat(0);
    }

    private function encode(array $bytes,int $dataWords): array
    {
        $bits=[false,true,false,false];for($i=7;$i>=0;$i--)$bits[]=((count($bytes)>>$i)&1)===1;foreach($bytes as $b){for($i=7;$i>=0;$i--)$bits[]=(($b>>$i)&1)===1;}$capacity=$dataWords*8;for($i=0;$i<min(4,$capacity-count($bits));$i++)$bits[]=false;while(count($bits)%8!==0)$bits[]=false;$out=[];for($i=0;$i<count($bits);$i+=8){$v=0;for($j=0;$j<8;$j++)$v=($v<<1)|($bits[$i+$j]?1:0);$out[]=$v;}$pads=[0xEC,0x11];$p=0;while(count($out)<$dataWords){$out[]=$pads[$p++%2];}return $out;
    }

    private function drawFunctions(): void
    {
        $this->finder(3,3);$this->finder($this->size-4,3);$this->finder(3,$this->size-4);
        for($i=8;$i<$this->size-8;$i++){$this->setFunction($i,6,$i%2===0);$this->setFunction(6,$i,$i%2===0);}
        if($this->version>=2){$c=4*$this->version+10;$this->alignment($c,$c);}
        for($i=0;$i<=5;$i++){$this->reserve(8,$i);$this->reserve($i,8);} $this->reserve(8,7);$this->reserve(8,8);$this->reserve(7,8);
        for($i=0;$i<8;$i++){$this->reserve($this->size-1-$i,8);$this->reserve(8,$this->size-1-$i);} $this->setFunction(8,$this->size-8,true);
    }

    private function finder(int $cx,int $cy): void
    {
        for($dy=-4;$dy<=4;$dy++){for($dx=-4;$dx<=4;$dx++){$x=$cx+$dx;$y=$cy+$dy;if($x<0||$y<0||$x>=$this->size||$y>=$this->size)continue;$dist=max(abs($dx),abs($dy));$black=$dist!==2&&$dist!==4;$this->setFunction($x,$y,$black);}}
    }

    private function alignment(int $cx,int $cy): void
    {
        for($dy=-2;$dy<=2;$dy++){for($dx=-2;$dx<=2;$dx++){$d=max(abs($dx),abs($dy));$this->setFunction($cx+$dx,$cy+$dy,$d!==1);}}
    }

    private function drawData(array $bits): void
    {
        $i=0;$up=true;for($right=$this->size-1;$right>=1;$right-=2){if($right===6)$right--;for($vert=0;$vert<$this->size;$vert++){$y=$up?$this->size-1-$vert:$vert;for($j=0;$j<2;$j++){$x=$right-$j;if($this->function[$y][$x])continue;$bit=$i<count($bits)?$bits[$i++]:false;if((($x+$y)&1)===0)$bit=!$bit;$this->modules[$y][$x]=$bit;}}$up=!$up;}
    }

    private function drawFormat(int $mask): void
    {
        $data=(1<<3)|$mask;$rem=$data;for($i=0;$i<10;$i++)$rem=($rem<<1)^((($rem>>9)&1)!==0?0x537:0);$bits=(($data<<10)|$rem)^0x5412;$get=static fn(int $i): bool => (($bits>>$i)&1)!==0;
        for($i=0;$i<=5;$i++)$this->setFunction(8,$i,$get($i));$this->setFunction(8,7,$get(6));$this->setFunction(8,8,$get(7));$this->setFunction(7,8,$get(8));for($i=9;$i<15;$i++)$this->setFunction(14-$i,8,$get($i));for($i=0;$i<8;$i++)$this->setFunction($this->size-1-$i,8,$get($i));for($i=8;$i<15;$i++)$this->setFunction(8,$this->size-15+$i,$get($i));$this->setFunction(8,$this->size-8,true);
    }

    private function reedSolomon(array $data,int $degree): array
    {
        $div=array_fill(0,$degree,0);$div[$degree-1]=1;$root=1;for($i=0;$i<$degree;$i++){for($j=0;$j<$degree;$j++){$div[$j]=$this->gfMul($div[$j],$root);if($j+1<$degree)$div[$j]^=$div[$j+1];}$root=$this->gfMul($root,2);}$res=array_fill(0,$degree,0);foreach($data as $b){$factor=$b^$res[0];array_shift($res);$res[]=0;for($i=0;$i<$degree;$i++)$res[$i]^=$this->gfMul($div[$i],$factor);}return $res;
    }

    private function gfMul(int $x,int $y): int
    {
        $z=0;for($i=7;$i>=0;$i--){$z=(($z<<1)^(($z>>7)*0x11D));if((($y>>$i)&1)!==0)$z^=$x;}return $z&0xFF;
    }

    private function reserve(int $x,int $y): void{$this->function[$y][$x]=true;}
    private function setFunction(int $x,int $y,bool $black): void{$this->modules[$y][$x]=$black;$this->function[$y][$x]=true;}
}
