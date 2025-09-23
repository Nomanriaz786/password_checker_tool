# 🚀 SonarCloud Project Setup Guide

## Issue: Project Not Found Error

You're getting this error because the SonarCloud project doesn't exist yet:
```
ERROR Could not find a default branch for project with key 'Nomanriaz786_password-checker-tool'
```

## 📋 Step-by-Step Solution

### 1. Create SonarCloud Account & Project

#### Option A: Automatic Import (Recommended)
1. Visit [SonarCloud.io](https://sonarcloud.io)
2. Click **"Log in with GitHub"**
3. Authorize SonarCloud to access your repositories
4. Click **"Import a project from GitHub"**
5. Find and select: `Nomanriaz786/password_checker_tool`
6. Click **"Set up"**

#### Option B: Manual Setup
1. Go to [SonarCloud.io](https://sonarcloud.io) 
2. Sign in with GitHub
3. Click **"Create new project"** → **"Manually"**
4. Fill in:
   - **Organization**: `Nomanriaz786`
   - **Project Key**: `Nomanriaz786_password-checker-tool`
   - **Display Name**: `Password Checker Tool`
   - **Repository**: Select your GitHub repo

### 2. Configure Project Settings

After creating the project in SonarCloud:

1. Go to your project dashboard
2. Click **"Administration"** → **"General Settings"**
3. Set **Main Branch** to: `main`
4. Click **"Analysis Method"** → **"GitHub Actions"**

### 3. Generate SONAR_TOKEN

1. In SonarCloud, click your **profile picture** (top right)
2. Go to **"My Account"** → **"Security"**
3. Click **"Generate Tokens"**
4. Enter token name: `password-checker-tool-ci`
5. Click **"Generate"**
6. **Copy the token immediately** (you can't see it again!)

### 4. Add Token to GitHub Secrets

1. Go to your GitHub repository: `https://github.com/Nomanriaz786/password_checker_tool`
2. Click **"Settings"** tab
3. Go to **"Secrets and variables"** → **"Actions"**
4. Click **"New repository secret"**
5. Add:
   - **Name**: `SONAR_TOKEN`
   - **Value**: [paste the token you copied]
6. Click **"Add secret"**

### 5. Test the Setup

1. Make any small change to your code (add a comment)
2. Commit and push to the `main` branch:
   ```bash
   git add .
   git commit -m "Test SonarCloud integration"
   git push origin main
   ```
3. Check **Actions** tab to see if the workflow runs successfully

## 🔧 Alternative: Quick Setup with Updated Configuration

If you want to try a different project key format, update the workflow:

**File**: `.github/workflows/devsecops-pipeline.yml`

Change this line:
```yaml
-Dsonar.projectKey=Nomanriaz786_password-checker-tool
```

To match your actual GitHub repository name:
```yaml
-Dsonar.projectKey=Nomanriaz786_password_checker_tool
```

## 🎯 Expected SonarCloud Project Structure

Once set up correctly, you should see:
- **Organization**: `Nomanriaz786`
- **Project Key**: `Nomanriaz786_password-checker-tool` (or `Nomanriaz786_password_checker_tool`)
- **Repository**: `Nomanriaz786/password_checker_tool`
- **Main Branch**: `main`

## ⚠️ Troubleshooting

### Problem: Organization doesn't exist
**Solution**: Create organization in SonarCloud first, or use your username instead

### Problem: Wrong repository name
**Solution**: Make sure the GitHub repository name matches exactly

### Problem: Token permissions
**Solution**: Regenerate token and ensure it has project analysis permissions

## 📞 Need Help?

1. **SonarCloud Dashboard**: Check if your project appears at [sonarcloud.io/projects](https://sonarcloud.io/projects)
2. **GitHub Integration**: Verify SonarCloud has access to your repository
3. **Logs**: Check GitHub Actions logs for detailed error messages

## ✅ Success Indicators

When everything is working:
- ✅ SonarCloud project exists and is visible
- ✅ GitHub Actions workflow completes without errors
- ✅ SonarCloud analysis results appear in dashboard
- ✅ Quality gate status is displayed