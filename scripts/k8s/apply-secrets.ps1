[CmdletBinding()]
param(
    [string]$Namespace = "default"
)

$ErrorActionPreference = "Stop"

function Assert-CommandExists {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name
    )

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required command not found: $Name"
    }
}

function Get-RequiredEnvValue {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name
    )

    $value = [Environment]::GetEnvironmentVariable($Name)
    if ([string]::IsNullOrWhiteSpace($value)) {
        throw "Missing required environment variable: $Name"
    }

    return $value
}

function Apply-SecretFromLiterals {
    param(
        [Parameter(Mandatory = $true)]
        [string]$SecretName,

        [Parameter(Mandatory = $true)]
        [hashtable]$Data
    )

    $args = @("create", "secret", "generic", $SecretName, "-n", $Namespace, "--dry-run=client", "-o", "yaml")

    foreach ($key in $Data.Keys) {
        $args += "--from-literal=$key=$($Data[$key])"
    }

    $yaml = & kubectl @args
    if ($LASTEXITCODE -ne 0) {
        throw "Failed generating secret manifest for: $SecretName"
    }

    $yaml | & kubectl apply -f -
    if ($LASTEXITCODE -ne 0) {
        throw "Failed applying secret: $SecretName"
    }

    Write-Host "Applied secret: $SecretName (namespace: $Namespace)"
}

Assert-CommandExists -Name "kubectl"

$appSecret = @{
    APP_SECRET           = Get-RequiredEnvValue "APP_SECRET"
    DATABASE_URL         = Get-RequiredEnvValue "DATABASE_URL"
    DATABASE_REPLICA_URL = Get-RequiredEnvValue "DATABASE_REPLICA_URL"
    PAYPAL_CLIENT_ID     = Get-RequiredEnvValue "PAYPAL_CLIENT_ID"
    PAYPAL_CLIENT_SECRET = Get-RequiredEnvValue "PAYPAL_CLIENT_SECRET"
}

$dbSecret = @{
    MYSQL_ROOT_PASSWORD       = Get-RequiredEnvValue "MYSQL_ROOT_PASSWORD"
    MYSQL_DATABASE            = Get-RequiredEnvValue "MYSQL_DATABASE"
    MYSQL_USER                = Get-RequiredEnvValue "MYSQL_USER"
    MYSQL_PASSWORD            = Get-RequiredEnvValue "MYSQL_PASSWORD"
    MYSQL_REPLICATION_USER    = Get-RequiredEnvValue "MYSQL_REPLICATION_USER"
    MYSQL_REPLICATION_PASSWORD = Get-RequiredEnvValue "MYSQL_REPLICATION_PASSWORD"
}

$mysqlExporterSecret = @{
    DATA_SOURCE_NAME = Get-RequiredEnvValue "MYSQL_EXPORTER_DATA_SOURCE_NAME"
}

Apply-SecretFromLiterals -SecretName "symfony-shop-secrets" -Data $appSecret
Apply-SecretFromLiterals -SecretName "symfony-shop-db-secret" -Data $dbSecret
Apply-SecretFromLiterals -SecretName "mysql-exporter-secret" -Data $mysqlExporterSecret

Write-Host "All Kubernetes secrets applied successfully."
