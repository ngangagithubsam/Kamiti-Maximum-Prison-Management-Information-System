$text = Get-Content -Raw .\index.php
$start = $text.IndexOf('<script id="main-app-script">')
if ($start -lt 0) { Write-Error 'start not found'; exit 1 }
$end = $text.IndexOf('</script>', $start)
if ($end -lt 0) { Write-Error 'end not found'; exit 1 }
$body = $text.Substring($start + '<script id="main-app-script">'.Length, $end - $start - '<script id="main-app-script">'.Length)
$lines = $body -split '\r?\n'
for ($i = 0; $i -lt $lines.Length; $i++) {
    if ($lines[$i] -match 'await') {
        $from = [math]::Max(0, $i - 3)
        $to = [math]::Min($lines.Length - 1, $i + 3)
        Write-Output "-- line $($i + 1): $($lines[$i].Trim())"
        for ($j = $from; $j -le $to; $j++) {
            $num = $j + 1
            Write-Output "$num: $($lines[$j])"
        }
        Write-Output '------'
    }
}
