[CmdletBinding()]
param(
    [string]$Namespace = "default",
    [string]$WorkerLabel = "app=symfony-shop-worker",
    [string]$WorkerDeployment = "symfony-shop-worker",
    [string]$WorkerContainer = "worker",
    [string]$AppDeployment = "symfony-shop-deploy3",
    [string]$AppContainer = "symfony-shopapp-new3",
    [int]$IntervalSeconds = 5,
    [int]$TailLines = 60,
    [string]$LogPattern = "FinalizeOrderCommand|QueueLoadTest|error|failed|exception|retry|redeliver",
    [switch]$NoClear
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

function Invoke-KubectlSafe {
    param(
        [Parameter(Mandatory = $true)]
        [string[]]$Args
    )

    $output = & kubectl @Args 2>&1
    if ($LASTEXITCODE -ne 0) {
        return @("<error> kubectl $($Args -join ' ')", ($output | Out-String).Trim())
    }

    if ($null -eq $output) {
        return @("<empty>")
    }

    if ($output -is [System.Array]) {
        return $output
    }

    return @("$output")
}

function Write-Section {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Title,
        [Parameter(Mandatory = $true)]
        [string[]]$Lines
    )

    Write-Host ""
    Write-Host "=== $Title ===" -ForegroundColor Cyan
    foreach ($line in $Lines) {
        Write-Host $line
    }
}

Assert-CommandExists -Name "kubectl"

if ($IntervalSeconds -lt 1) {
    throw "IntervalSeconds must be >= 1."
}

if ($TailLines -lt 1) {
    throw "TailLines must be >= 1."
}

Write-Host "Starting queue monitor. Press Ctrl+C to stop." -ForegroundColor Green
Start-Sleep -Seconds 1

while ($true) {
    if (-not $NoClear) {
        Clear-Host
    }

    $now = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "Queue Monitor | namespace=$Namespace | updated=$now" -ForegroundColor Yellow
    Write-Host "WorkerLabel=$WorkerLabel | Interval=${IntervalSeconds}s | TailLines=$TailLines"
    Write-Host "LogPattern=$LogPattern"

    $pods = Invoke-KubectlSafe -Args @(
        "-n", $Namespace,
        "get", "pods",
        "-l", $WorkerLabel,
        "-o", "custom-columns=NAME:.metadata.name,READY:.status.containerStatuses[0].ready,RESTARTS:.status.containerStatuses[0].restartCount,STATUS:.status.phase,AGE:.metadata.creationTimestamp",
        "--no-headers"
    )
    Write-Section -Title "Worker Pods" -Lines $pods

    $hpa = Invoke-KubectlSafe -Args @(
        "-n", $Namespace,
        "get", "hpa"
    )
    Write-Section -Title "HPA" -Lines $hpa

    $scaledObject = Invoke-KubectlSafe -Args @(
        "-n", $Namespace,
        "get", "scaledobject", "symfony-shop-worker-queue-scaledobject"
    )
    Write-Section -Title "KEDA ScaledObject" -Lines $scaledObject

    $topPods = Invoke-KubectlSafe -Args @(
        "-n", $Namespace,
        "top", "pods",
        "-l", $WorkerLabel
    )
    Write-Section -Title "Worker CPU/Memory (kubectl top)" -Lines $topPods

    $workerLogs = Invoke-KubectlSafe -Args @(
        "-n", $Namespace,
        "logs", "deploy/$WorkerDeployment",
        "-c", $WorkerContainer,
        "--tail=$TailLines"
    ) | Select-String -Pattern $LogPattern -CaseSensitive:$false | ForEach-Object { $_.Line }
    if ($null -eq $workerLogs -or $workerLogs.Count -eq 0) {
        $workerLogs = @("<no matching lines>")
    }
    Write-Section -Title "Worker Logs (filtered)" -Lines $workerLogs

    $appLogs = Invoke-KubectlSafe -Args @(
        "-n", $Namespace,
        "logs", "deploy/$AppDeployment",
        "-c", $AppContainer,
        "--tail=$TailLines"
    ) | Select-String -Pattern $LogPattern -CaseSensitive:$false | ForEach-Object { $_.Line }
    if ($null -eq $appLogs -or $appLogs.Count -eq 0) {
        $appLogs = @("<no matching lines>")
    }
    Write-Section -Title "App Logs (filtered)" -Lines $appLogs

    Write-Host ""
    Write-Host "Refreshing in $IntervalSeconds second(s)... (Ctrl+C to stop)" -ForegroundColor DarkGray
    Start-Sleep -Seconds $IntervalSeconds
}

