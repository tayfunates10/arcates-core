<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\Database;
final class Installer
{
    public static function locked(): bool{return is_file(ARCATES_ROOT.'/install/install.lock');}
    public static function run(array $config,string $email,string $password): void
    {
        if(self::locked())throw new \RuntimeException('Kurulum kilitli.');
        $database=new Database($config);$database->pdo()->exec((string)file_get_contents(ARCATES_ROOT.'/schema.sql'));Migrator::run($database);
        $database->execute('INSERT INTO users (email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, 1, NOW())',[mb_strtolower(trim($email)),password_hash($password,PASSWORD_DEFAULT),'admin']);
        file_put_contents(ARCATES_ROOT.'/install/install.lock',date('c')."\n",LOCK_EX);
    }
}
