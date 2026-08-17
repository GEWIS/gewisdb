<?php

declare(strict_types=1);

/**
 * Convert a gettext .po catalogue to the XLIFF 1.2 dialect Symfony's XliffFileDumper writes, so that
 * `translation:extract` round-trips the file instead of rewriting every entry.
 *
 * The trans-unit id must match Symfony's own algorithm exactly:
 *   strtr(substr(base64_encode(hash('xxh128', $source, true)), 0, 7), '/+', '._')
 *
 * Usage: php po2xliff.php <in.po> <out.xlf> <source-locale> <target-locale>
 */

[$self, $in, $out, $sourceLocale, $targetLocale] = $argv + [null, null, null, null, null];

if (null === $targetLocale) {
    fwrite(STDERR, "usage: po2xliff.php <in.po> <out.xlf> <source-locale> <target-locale>\n");
    exit(2);
}

function unquote(string $line): string
{
    $line = trim($line);
    if ('"' !== ($line[0] ?? '') || '"' !== substr($line, -1)) {
        return '';
    }

    $raw = substr($line, 1, -1);

    return strtr($raw, [
        '\\n' => "\n",
        '\\t' => "\t",
        '\\r' => "\r",
        '\\"' => '"',
        '\\\\' => '\\',
    ]);
}

$entries = [];
$plurals = [];
$current = null;
$key = null;

foreach (file($in, FILE_IGNORE_NEW_LINES) as $line) {
    if ('' === trim($line) || str_starts_with(trim($line), '#')) {
        if (null !== $current) {
            $entries[$current['msgid']] = $current;
            $current = null;
            $key = null;
        }

        continue;
    }

    if (str_starts_with($line, 'msgid_plural ')) {
        $key = 'msgid_plural';
        $current[$key] = unquote(substr($line, 13));
        $plurals[$current['msgid']] = true;

        continue;
    }

    if (str_starts_with($line, 'msgid ')) {
        if (null !== $current) {
            $entries[$current['msgid']] = $current;
        }

        $current = ['msgid' => unquote(substr($line, 6)), 'msgstr' => ''];
        $key = 'msgid';

        continue;
    }

    if (str_starts_with($line, 'msgstr[')) {
        $key = 'msgstr';
        $idx = (int) substr($line, 7, 1);
        $value = unquote(substr($line, strpos($line, ' ') + 1));
        $current['msgstr'] = 0 === $idx ? $value : $current['msgstr'] . '|' . $value;

        continue;
    }

    if (str_starts_with($line, 'msgstr ')) {
        $key = 'msgstr';
        $current[$key] = unquote(substr($line, 7));

        continue;
    }

    if (null !== $current && null !== $key) {
        $current[$key] .= unquote($line);
    }
}

if (null !== $current) {
    $entries[$current['msgid']] = $current;
}

unset($entries['']);

$doc = new DOMDocument('1.0', 'utf-8');
$doc->formatOutput = true;

$xliff = $doc->appendChild($doc->createElement('xliff'));
$xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');
$xliff->setAttribute('version', '1.2');

$file = $xliff->appendChild($doc->createElement('file'));
$file->setAttribute('source-language', str_replace('_', '-', $sourceLocale));
$file->setAttribute('target-language', str_replace('_', '-', $targetLocale));
$file->setAttribute('datatype', 'plaintext');
$file->setAttribute('original', 'file.ext');

$header = $file->appendChild($doc->createElement('header'));
$tool = $header->appendChild($doc->createElement('tool'));
$tool->setAttribute('tool-id', 'symfony');
$tool->setAttribute('tool-name', 'Symfony');

$body = $file->appendChild($doc->createElement('body'));

$sources = array_keys($entries);
sort($sources, SORT_STRING);

$empty = 0;
foreach ($sources as $source) {
    $target = $entries[$source]['msgstr'];

    $unit = $body->appendChild($doc->createElement('trans-unit'));
    $unit->setAttribute('id', strtr(substr(base64_encode(hash('xxh128', $source, true)), 0, 7), '/+', '._'));
    $unit->setAttribute('resname', $source);

    $unit->appendChild($doc->createElement('source'))->appendChild($doc->createTextNode($source));

    $targetElement = $unit->appendChild($doc->createElement('target'));
    if ('' === $target) {
        ++$empty;
    } else {
        $targetElement->appendChild($doc->createTextNode($target));
        $targetElement->setAttribute('state', 'translated');
    }
}

file_put_contents($out, $doc->saveXML());

fwrite(STDERR, sprintf(
    "%s -> %s: %d units, %d empty targets, %d plural forms\n",
    basename($in),
    basename($out),
    count($sources),
    $empty,
    count($plurals),
));
