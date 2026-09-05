<?php
declare(strict_types=1);
namespace Arcates\Services;
use PDO;
final class Installer
{
    public static function locked(): bool{return is_file(ARCATES_ROOT.'/install/install.lock');}
    public static function run(array $config,string $email,string $password): void
    {
        if(self::locked())throw new \RuntimeException('Kurulum kilitli.');$db=$config['db']??[];$dsn=sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$db['host'],$db['port']??3306,$db['name'],$db['charset']??'utf8mb4');$pdo=new PDO($dsn,(string)$db['user'],(string)$db['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec((string)file_get_contents(ARCATES_ROOT.'/schema.sql'));
        $migrations=glob(ARCATES_ROOT.'/db/migrations/*.sql')?:[];sort($migrations,SORT_STRING);foreach($migrations as $migration){$pdo->exec((string)file_get_contents($migration));}
        $stmt=$pdo->prepare('INSERT INTO users (email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, 1, NOW())');$stmt->execute([mb_strtolower(trim($email)),password_hash($password,PASSWORD_DEFAULT),'admin']);file_put_contents(ARCATES_ROOT.'/install/install.lock',date('c')."\n",LOCK_EX);
    }
}
