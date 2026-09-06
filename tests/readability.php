<?php
declare(strict_types=1);
// Okunabilirlik kapısı (BULGU-20).
//
// CLAUDE.md "400 satırı aşan dosya bölünür" diyor; bu kural sağlanıyor, çünkü mantık
// tek satıra sıkıştırılmış durumda. Bu kontrol eksik yarıyı kapatır: satır UZUNLUĞU.
//
// Depo bugün 77 dosyada 400 karakteri aşan satır içeriyor; hepsini bir anda biçimlendirmek
// devasa ve riskli bir diff olur. Bu yüzden kapı bir CIRCIR (ratchet) olarak çalışır:
//   - Taban listesinde OLMAYAN dosya sınırı aşamaz  -> yeni kod baştan temiz gelir.
//   - Taban listesinde OLAN dosya kendi kaydını aşamaz -> mevcut borç büyüyemez.
//   - Dosya iyileştiğinde bilgi verilir; taban listesi daraltılarak kalıcılaştırılır.
$root = dirname(__DIR__);
$limit = 400;
$baselineFile = $root . '/tests/fixtures/readability_baseline.json';
$baseline = is_file($baselineFile)
    ? (array) json_decode((string) file_get_contents($baselineFile), true, 512, JSON_THROW_ON_ERROR)
    : [];

$skipDir = ['/vendor/', '/uploads/', '/logs/', '/.git/', '/node_modules/'];
$widest = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    $relative = ltrim(str_replace($root, '', $path), '/');
    if ($relative === 'config.php') {
        continue; // commit edilmez; CI bunu fixture'dan kopyalar
    }
    foreach ($skipDir as $skip) {
        if (str_contains($path, $skip)) {
            continue 2;
        }
    }
    $longest = 0;
    foreach (file($file->getPathname(), FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $longest = max($longest, strlen($line));
    }
    $widest[$relative] = $longest;
}
ksort($widest);

$failures = [];
$improved = [];
foreach ($widest as $relative => $longest) {
    $allowed = isset($baseline[$relative]) ? (int) $baseline[$relative] : $limit;
    if ($longest > $allowed) {
        $failures[] = $baseline[$relative] ?? null
            ? sprintf('Okunabilirlik gerilemesi: %s (%d karakter, taban %d)', $relative, $longest, $allowed)
            : sprintf('Yeni kod %d karakter sınırını aşıyor: %s (%d karakter)', $limit, $relative, $longest);
        continue;
    }
    if (isset($baseline[$relative]) && $longest < (int) $baseline[$relative]) {
        $improved[] = sprintf('%s: %d -> %d', $relative, (int) $baseline[$relative], $longest);
    }
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    fwrite(STDERR, sprintf(
        'Satır uzunluğu sınırı %d karakter. Mevcut borç tests/fixtures/readability_baseline.json içinde.' . PHP_EOL,
        $limit
    ));
    exit(1);
}

$debt = count($baseline);
if ($improved) {
    echo 'Okunabilirlik iyileşmesi: ' . implode(', ', array_slice($improved, 0, 5))
        . (count($improved) > 5 ? sprintf(' (+%d dosya daha)', count($improved) - 5) : '') . "\n";
    echo "Taban listesini daraltmak için bu değerleri readability_baseline.json içinde güncelleyin.\n";
}
echo sprintf("Okunabilirlik kapısı: OK (sınır %d karakter, taban listesinde %d dosya)\n", $limit, $debt);
