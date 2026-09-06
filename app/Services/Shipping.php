<?php
declare(strict_types=1);
namespace Arcates\Services;
use Arcates\Core\App;
final class Shipping
{
    public static function fee(float $subtotal): float
    {
        $rows=App::db()->fetchAll('SELECT min_total,max_total,fee FROM shipping_rules WHERE is_active=1 AND min_total<=? AND (max_total IS NULL OR max_total>=?) ORDER BY sort_order ASC,id ASC',[$subtotal,$subtotal]);
        return $rows?max(0,(float)$rows[0]['fee']):0.0;
    }
}
