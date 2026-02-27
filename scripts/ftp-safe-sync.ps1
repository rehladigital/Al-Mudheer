param(
    [Parameter(Mandatory = $true)]
    [string]$SftpHost,

    [Parameter(Mandatory = $true)]
    [int]$Port,

    [Parameter(Mandatory = $true)]
    [string]$User,

    [Parameter(Mandatory = $true)]
    [string]$Password,

    [Parameter(Mandatory = $true)]
    [string]$RemotePath,

    [string]$SourcePath = "."
)

$ErrorActionPreference = "Stop"

$resolvedSource = (Resolve-Path $SourcePath).Path
$scriptPath = Join-Path $env:TEMP "winscp-safe-sync-$(Get-Date -Format 'yyyyMMddHHmmss').txt"

$escapedPassword = [uri]::EscapeDataString($Password)

$winScpScript = @"
option batch abort
option confirm off
open sftp://$($User):$($escapedPassword)@$($SftpHost):$($Port)/ -hostkey=*
synchronize remote -criteria=time -transfer=binary -filemask="|.git/;.idea/;node_modules/;vendor/;storage/;userfiles/;config/.env;*.log;*.sqlite;*.db;*.db-journal" "$resolvedSource" "$RemotePath"
exit
"@

Set-Content -Path $scriptPath -Value $winScpScript -Encoding ASCII

& "C:/Program Files (x86)/WinSCP/WinSCP.com" /ini=nul /script="$scriptPath"
$exitCode = $LASTEXITCODE

Remove-Item $scriptPath -ErrorAction SilentlyContinue

if ($exitCode -ne 0) {
    throw "Safe FTP sync failed with exit code $exitCode"
}

Write-Host "Safe FTP sync completed."
