<?php
declare(strict_types=1);

namespace Arcates\Accounting;

final class TemplateRenderer
{
    private const MAX_NODES = 10000;

    public static function render(array $template, array $order, array $items): array
    {
        $nodes = 0;
        $value = self::node($template, $order, $items, null, false, $nodes);
        if (!is_array($value)) {
            throw new \RuntimeException('Muhasebe şablonu JSON nesnesi üretmeli.');
        }
        return $value;
    }

    private static function node(
        mixed $value,
        array $order,
        array $items,
        ?array $item,
        bool $insideEach,
        int &$nodes
    ): mixed {
        $nodes++;
        if ($nodes > self::MAX_NODES) {
            throw new \RuntimeException('Muhasebe şablonu çıktı sınırını aşıyor.');
        }

        if (is_array($value)) {
            if (isset($value['$each']) && $value['$each'] === 'items' && array_key_exists('template', $value)) {
                if ($insideEach) {
                    throw new \RuntimeException('İç içe $each kullanımı desteklenmez.');
                }
                $out = [];
                foreach ($items as $row) {
                    $out[] = self::node($value['template'], $order, $items, $row, true, $nodes);
                }
                return $out;
            }

            $out = [];
            foreach ($value as $key => $child) {
                $out[$key] = self::node($child, $order, $items, $item, $insideEach, $nodes);
            }
            return $out;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^\{\{(order|item)\.([a-zA-Z0-9_]+)\}\}$/', $value, $matches)) {
            $source = $matches[1] === 'order' ? $order : ($item ?? []);
            return $source[$matches[2]] ?? null;
        }

        return preg_replace_callback(
            '/\{\{(order|item)\.([a-zA-Z0-9_]+)\}\}/',
            static function (array $matches) use ($order, $item): string {
                $source = $matches[1] === 'order' ? $order : ($item ?? []);
                $resolved = $source[$matches[2]] ?? '';
                return is_scalar($resolved) ? (string) $resolved : '';
            },
            $value
        ) ?? $value;
    }
}
