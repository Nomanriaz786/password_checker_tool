# 🔐 GitHub Secrets Setup Guide

## Overview

This guide explains exactly what secrets you need to add to your GitHub repository for the DevSecOps pipeline to work properly.

## 📋 Required GitHub Repository Secrets

### Step 1: Access Repository Secrets

1. Go to your GitHub repository: `https://github.com/Nomanriaz786/password_checker_tool`
2. Click on **Settings** tab
3. In the left sidebar, click **Secrets and variables**
4. Click **Actions**
5. Click **New repository secret**

### Step 2: Add Required Secrets

#### 🔑 SONAR_TOKEN (REQUIRED)

**What it is**: Authentication token for SonarCloud access

**How to get it**:
1. Go to https://sonarcloud.io
2. Sign in with your GitHub account
3. Click on your avatar (top right) → **My Account**
4. Go to **Security** tab
5. In the **Generate Tokens** section:
   - **Name**: `password-checker-tool-github`
   - **Type**: `User Token`
   - **Expires in**: `90 days` (or longer)
6. Click **Generate**
7. **Copy the token immediately** (starts with `squ_`)

**GitHub Secret Configuration**:
- **Name**: `SONAR_TOKEN`
- **Value**: `squ_1234567890abcdef1234567890abcdef12345678` (your actual token)

---

## 📋 Optional GitHub Repository Secrets

#### 🏢 SONAR_ORGANIZATION (Optional)

**What it is**: Your SonarCloud organization key (usually your GitHub username)

**Default**: Auto-detected from your repository (`Nomanriaz786`)

**When to set**: Only if you want to use a different organization

**GitHub Secret Configuration**:
- **Name**: `SONAR_ORGANIZATION` 
- **Value**: `Nomanriaz786` (or your custom organization)

---

#### 🎯 SONAR_PROJECT_KEY (Optional)

**What it is**: Custom project identifier in SonarCloud

**Default**: `Nomanriaz786_password-checker-tool`

**When to set**: Only if you want a custom project key

**GitHub Secret Configuration**:
- **Name**: `SONAR_PROJECT_KEY`
- **Value**: `your-custom-project-key`

---

## ✅ Verification Checklist

After adding secrets, verify they're set correctly:

- [ ] **SONAR_TOKEN** is added (starts with `squ_`)
- [ ] Token is not expired
- [ ] Repository has **Actions** enabled
- [ ] SonarCloud project exists with same name
- [ ] GitHub Actions workflow file exists (`.github/workflows/devsecops-pipeline.yml`)

## 🚀 Test Your Setup

1. **Trigger the workflow**:
   ```bash
   # Make a small change and push
   git add .
   git commit -m "Test SonarCloud integration"
   git push origin main
   ```

2. **Check the workflow**:
   - Go to **Actions** tab in your GitHub repository
   - Look for "SonarCloud DevSecOps Analysis" workflow
   - Click on the running/completed workflow to see logs

3. **Check SonarCloud**:
   - Go to https://sonarcloud.io/projects
   - Look for your project: `password-checker-tool`
   - Verify analysis results appear

## 🔍 Troubleshooting

### Common Issues:

#### ❌ "Invalid SONAR_TOKEN" Error
**Solution**: 
1. Regenerate token in SonarCloud
2. Update GitHub secret with new token
3. Ensure token hasn't expired

#### ❌ "Project not found" Error  
**Solution**:
1. Import project in SonarCloud first
2. Verify project key matches GitHub secret
3. Check organization name is correct

#### ❌ "Dependencies lock file not found" Error
**Solution**: 
This is now fixed with the package.json file. If you still see this:
1. Commit and push the package.json file
2. GitHub Actions will create package-lock.json automatically

#### ❌ Workflow doesn't start
**Solution**:
1. Check GitHub Actions is enabled in repository settings
2. Verify workflow file exists in `.github/workflows/`
3. Check branch protection rules aren't blocking workflows

## 📊 What Happens After Setup

Once secrets are configured correctly:

1. **Automatic Analysis**: Every push to `main`/`develop` triggers analysis
2. **Pull Request Checks**: PRs get security analysis and comments
3. **Quality Gates**: Code must pass security checks before merging
4. **Dashboard**: View results at https://sonarcloud.io/projects
5. **Security Tab**: GitHub Security tab shows vulnerabilities

## 🔐 Security Best Practices

- **Token Expiry**: Set reasonable expiration (90 days recommended)
- **Token Rotation**: Regularly rotate tokens
- **Minimal Permissions**: SonarCloud tokens only need analysis permissions
- **Monitor Usage**: Check SonarCloud audit logs regularly
- **Team Access**: Only give tokens to necessary team members

## 📞 Support

- **SonarCloud Issues**: https://community.sonarsource.com/
- **GitHub Actions**: https://docs.github.com/en/actions
- **Repository Issues**: https://github.com/Nomanriaz786/password_checker_tool/issues

---

## 🎯 Quick Summary

**Minimum Required Setup**:
1. Add `SONAR_TOKEN` to GitHub repository secrets
2. Push code to trigger workflow  
3. Check results in SonarCloud dashboard

That's it! The pipeline will handle everything else automatically. 🚀