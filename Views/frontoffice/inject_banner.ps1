$pages = @(
    'nutrismart-website.html',
    'contact.html',
    'login.html',
    'nutrismart-home.html',
    'profile.html',
    'register.html',
    'reset_password.html',
    'smart_picks.html'
)

$scriptTag = '<script src="js/quiz-banner.js"></script>'
$base = 'c:\xampp\htdocs\NutriSmart\Views\frontoffice'

foreach ($page in $pages) {
    $path = Join-Path $base $page
    if (Test-Path $path) {
        $content = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)
        if ($content -notmatch 'quiz-banner\.js') {
            $content = $content.Replace('</body>', "$scriptTag`n</body>")
            [System.IO.File]::WriteAllText($path, $content, [System.Text.Encoding]::UTF8)
            Write-Host "Patched: $page"
        } else {
            Write-Host "Already has it: $page"
        }
    } else {
        Write-Host "Not found: $page"
    }
}

Write-Host "Done!"
