$patterns = @{
    'âœ•' = '&#x2715;'
    'â€”' = '—'
    'Â·' = '·'
    'â”€' = '-'
}

Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    $path = $_.FullName
    $text = Get-Content -Raw -Encoding UTF8 $path
    $new = $text
    foreach ($old in $patterns.Keys) {
        $new = $new.Replace($old, $patterns[$old])
    }
    if ($new -ne $text) {
        Set-Content -Encoding UTF8 -Value $new $path
        Write-Output "Updated $path"
    }
}
