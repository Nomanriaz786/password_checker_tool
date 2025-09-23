# SonarCloud Setup Script for Password Checker Tool
# This script helps configure your project for SonarCloud integration

param(
    [string]$Organization,
    [string]$ProjectKey,
    [switch]$SkipBrowserOpen
)

$ErrorActionPreference = "Stop"

Write-Host "🌐 SonarCloud Setup for Password Checker Tool" -ForegroundColor Green
Write-Host "=============================================="

function Write-Status {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "⚠️  $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "ℹ️  $Message" -ForegroundColor Cyan
}

# Step 1: Check prerequisites
Write-Info "Checking prerequisites..."

# Check if we're in the right directory
if (-not (Test-Path "sonar-project.properties")) {
    Write-Error "Please run this script from the Password_Checker_Tool directory"
    exit 1
}

# Check Git configuration
try {
    $gitRemote = git remote get-url origin 2>$null
    if ($gitRemote) {
        Write-Status "Git repository detected: $gitRemote"
    } else {
        Write-Warning "No Git remote configured - you'll need to set this up"
    }
} catch {
    Write-Warning "Git not found or not configured"
}

Write-Status "Prerequisites checked"

# Step 2: Collect organization and project information
if (-not $Organization) {
    Write-Info "SonarCloud requires an organization key"
    Write-Host "You can find this in your SonarCloud account after logging in"
    $Organization = Read-Host "Enter your SonarCloud organization key"
}

if (-not $ProjectKey) {
    $suggestedKey = "$Organization`_password-checker-tool"
    Write-Info "Suggested project key: $suggestedKey"
    $ProjectKey = Read-Host "Enter project key (or press Enter for suggested)"
    if ([string]::IsNullOrEmpty($ProjectKey)) {
        $ProjectKey = $suggestedKey
    }
}

Write-Status "Configuration: Organization=$Organization, ProjectKey=$ProjectKey"

# Step 3: Update sonar-project.properties
Write-Info "Updating sonar-project.properties for SonarCloud..."

$sonarConfig = @"
# SonarCloud Configuration for Password Checker Tool
# DevSecOps Implementation with Security Analysis

# Project Information
sonar.projectKey=$ProjectKey
sonar.projectName=Password Strength Checker Web Application
sonar.projectVersion=1.0.0

# SonarCloud Organization (Required)
sonar.organization=$Organization

# Project Description
sonar.projectDescription=Secure Password Strength Checker with DevSecOps Implementation

# Source Code Settings
sonar.sources=.
sonar.sourceEncoding=UTF-8

# Language-specific settings
sonar.php.version=8.2
sonar.javascript.version=ECMASCRIPT_2022

# Include/Exclude Files
sonar.inclusions=**/*.php,**/*.js,**/*.css,**/*.html
sonar.exclusions=**/vendor/**,**/node_modules/**,**/*.min.js,**/*.min.css,logs/**,**/*.log,**/*.sql

# Test Coverage Settings
sonar.coverage.exclusions=**/tests/**,**/vendor/**,**/logs/**
sonar.php.coverage.reportPaths=coverage.xml
sonar.javascript.coverage.reportPaths=coverage/lcov.info

# Security Analysis
sonar.security.hotspots.inheritFromParent=true

# Quality Gate
sonar.qualitygate.wait=true

# Analysis Parameters
sonar.analysis.mode=publish
sonar.log.level=INFO
"@

$sonarConfig | Out-File -FilePath "sonar-project.properties" -Encoding UTF8
Write-Status "sonar-project.properties updated for SonarCloud"

# Step 4: Create/Update SonarCloud workflow
Write-Info "Creating optimized GitHub Actions workflow for SonarCloud..."

$workflowContent = Get-Content ".github\workflows\sonarcloud.yml" -Raw 2>$null
if ($workflowContent) {
    # Update the workflow with the correct organization and project key
    $updatedWorkflow = $workflowContent -replace "your-org_password-checker-tool", $ProjectKey
    $updatedWorkflow = $updatedWorkflow -replace "your-sonarcloud-org", $Organization
    
    $updatedWorkflow | Out-File -FilePath ".github\workflows\sonarcloud.yml" -Encoding UTF8
    Write-Status "Updated existing SonarCloud workflow"
} else {
    Write-Warning "SonarCloud workflow not found - please ensure .github\workflows\sonarcloud.yml exists"
}

# Step 5: Create environment configuration
$envContent = @"
# SonarCloud Configuration for Local Development
SONAR_HOST_URL=https://sonarcloud.io
SONAR_ORGANIZATION=$Organization
SONAR_PROJECT_KEY=$ProjectKey
"@

$envContent | Out-File -FilePath ".env.sonarcloud" -Encoding UTF8
Write-Status "Created .env.sonarcloud configuration file"

# Step 6: Open SonarCloud for setup
if (-not $SkipBrowserOpen) {
    Write-Info "Opening SonarCloud in your browser..."
    Start-Process "https://sonarcloud.io/projects/create"
    Start-Sleep -Seconds 2
    Start-Process "https://sonarcloud.io/organizations/$Organization"
}

# Step 7: Display setup instructions
Write-Host ""
Write-Status "SonarCloud Configuration Complete!" -ForegroundColor Green
Write-Host ""

Write-Info "Next Steps:"
Write-Host ""
Write-Host "1. 🌐 Complete SonarCloud Setup:" -ForegroundColor Yellow
Write-Host "   • Go to https://sonarcloud.io"
Write-Host "   • Sign in with your GitHub account"
Write-Host "   • Import your repository: $gitRemote"
Write-Host "   • Verify organization: $Organization"
Write-Host "   • Confirm project key: $ProjectKey"
Write-Host ""

Write-Host "2. 🔐 Configure GitHub Repository Secrets:" -ForegroundColor Yellow
Write-Host "   • Go to your repository settings"
Write-Host "   • Navigate to 'Secrets and Variables' → 'Actions'"
Write-Host "   • Add secret: SONAR_TOKEN (get this from SonarCloud)"
Write-Host "   • SONAR_HOST_URL is not needed (defaults to sonarcloud.io)"
Write-Host ""

Write-Host "3. 🚀 Trigger Analysis:" -ForegroundColor Yellow
Write-Host "   • Push changes to main or develop branch"
Write-Host "   • Or create a pull request"
Write-Host "   • GitHub Actions will automatically run SonarCloud analysis"
Write-Host ""

Write-Host "4. 📊 View Results:" -ForegroundColor Yellow
Write-Host "   • SonarCloud Dashboard: https://sonarcloud.io/project/overview?id=$ProjectKey"
Write-Host "   • GitHub Actions: Check the workflow results"
Write-Host "   • Pull Request Comments: SonarCloud will comment on PRs"
Write-Host ""

Write-Info "Configuration Files Created:"
Write-Host "   • ✅ sonar-project.properties (updated for SonarCloud)"
Write-Host "   • ✅ .env.sonarcloud (local development configuration)"
Write-Host "   • ✅ .github\workflows\sonarcloud.yml (GitHub Actions workflow)"

Write-Host ""
Write-Info "SonarCloud Advantages:"
Write-Host "   • 🎯 Zero infrastructure management"
Write-Host "   • 🔄 Automatic updates and maintenance"
Write-Host "   • 🐙 Native GitHub integration"
Write-Host "   • 🔒 Enterprise security and compliance"
Write-Host "   • 📈 Rich analytics and insights"
Write-Host "   • 🆓 Free for public repositories"

Write-Host ""
Write-Info "Need Help?"
Write-Host "   • 📖 SonarCloud Documentation: https://docs.sonarcloud.io/"
Write-Host "   • 💬 Community Forum: https://community.sonarsource.com/"
Write-Host "   • 📋 Project Setup Guide: SONARCLOUD-SETUP.md"

Write-Host ""
Write-Status "Ready to enhance your DevSecOps pipeline with SonarCloud! 🚀" -ForegroundColor Green

# Step 8: Offer to commit changes
Write-Host ""
$commitChanges = Read-Host "Would you like to commit these configuration changes? (y/N)"
if ($commitChanges -eq 'y' -or $commitChanges -eq 'Y') {
    try {
        git add sonar-project.properties .env.sonarcloud .github/workflows/sonarcloud.yml 2>$null
        git commit -m "Configure SonarCloud integration for DevSecOps pipeline

- Update sonar-project.properties for SonarCloud
- Add optimized GitHub Actions workflow
- Configure organization: $Organization
- Set project key: $ProjectKey" 2>$null
        
        Write-Status "Changes committed to Git"
        Write-Info "Push to GitHub to trigger the first SonarCloud analysis!"
    } catch {
        Write-Warning "Failed to commit changes. Please commit manually."
    }
}

Write-Host ""
Write-Info "Quick Commands:"
Write-Host "   • Push changes: git push origin main"
Write-Host "   • View workflow: https://github.com/your-repo/actions"
Write-Host "   • Check SonarCloud: https://sonarcloud.io/organizations/$Organization"