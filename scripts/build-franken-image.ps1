param(
    [Parameter(Mandatory = $true)]
    [string]$Tag,
    [string]$Image = "faezsal/symfony-shop-frankenphp",
    [switch]$Push,
    [switch]$SkipVerify
)

$ErrorActionPreference = "Stop"

$repoRoot = "C:\gg\symfony\salshop"
$dockerfile = Join-Path $repoRoot "container\frankenphp\Dockerfile"
$fullImage = "${Image}:${Tag}"

Write-Host "Building $fullImage using $dockerfile"
docker build -f $dockerfile -t $fullImage $repoRoot

if (-not $SkipVerify) {
    Write-Host "Verifying frankenphp binary in $fullImage"
    docker run --rm --entrypoint /bin/sh $fullImage -lc "set -e; FRANKENPHP_BIN=\$(command -v frankenphp); echo frankenphp=\$FRANKENPHP_BIN; ls -l \$FRANKENPHP_BIN"
}

if ($Push) {
    Write-Host "Pushing $fullImage"
    docker push $fullImage
}

Write-Host "Done: $fullImage"
