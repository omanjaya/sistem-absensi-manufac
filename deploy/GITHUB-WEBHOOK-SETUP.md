# 🔗 GitHub Webhook Setup - Auto Deploy ke Hostinger

## ✅ **Status: Hostinger Ready!**

Hostinger sudah berhasil detect `composer.json` dan generate webhook URL:

```
https://webhooks.hostinger.com/deploy/7f59fddf8be7857f24d3de0010477ddf
```

## 🚀 **Setup GitHub Webhook (Step-by-Step)**

### **Step 1: Open GitHub Webhook Settings**

1. **Go to**: https://github.com/omanjaya/sistem-absensi-manufac/settings/hooks/new
2. **Or navigate**: Repository → Settings → Webhooks → Add webhook

### **Step 2: Configure Webhook**

#### **Webhook Configuration:**

```
Payload URL: https://webhooks.hostinger.com/deploy/7f59fddf8be7857f24d3de0010477ddf
Content type: application/json
Secret: (leave empty)
```

#### **Which events trigger this webhook?**

- ✅ **Select**: "Just the push event"
- ✅ **Active**: Check the "Active" checkbox

#### **Detailed Settings:**

```
Payload URL: https://webhooks.hostinger.com/deploy/7f59fddf8be7857f24d3de0010477ddf
Content type: application/json
Secret: (kosong)
SSL verification: Enable SSL verification (default)
Events: Push events only
Active: ✅ (checked)
```

### **Step 3: Add Webhook**

- **Click**: "Add webhook"
- **Wait**: GitHub akan test webhook connection
- **Check**: Green checkmark ✅ jika berhasil

## 🔄 **Auto-Deploy Workflow**

Setelah webhook setup, workflow akan menjadi:

```
1. Developer push code ke GitHub
   ↓
2. GitHub trigger webhook ke Hostinger
   ↓
3. Hostinger auto-pull latest code
   ↓
4. Hostinger run build script (build.sh)
   ↓
5. Website live di https://manufac.id
```

## 📋 **Build Process (Automatic)**

Ketika auto-deploy triggered, Hostinger akan:

### **1. Pull Latest Code:**

```bash
git pull origin main
```

### **2. Run Build Script:**

```bash
chmod +x build.sh
./build.sh
```

### **3. Build Process akan:**

- ✅ Build frontend: `cd frontend-web && npm install && npm run build:production`
- ✅ Install backend: `cd backend-api && composer install --no-dev`
- ✅ Copy frontend ke root: `cp -r frontend-web/dist/* ./`
- ✅ Copy backend ke api/: `mkdir -p api && cp -r backend-api/* api/`
- ✅ Set permissions: `chmod -R 755 api/storage api/bootstrap/cache`

### **4. Result Structure:**

```
public_html/             # manufac.id
├── index.html          # Vue.js frontend
├── assets/             # CSS, JS, images
├── api/                # Laravel backend
│   ├── app/
│   ├── database/
│   ├── .env
│   └── ...
└── ...
```

## ✅ **Testing Auto-Deploy**

### **Test 1: Make a Small Change**

```bash
# Edit README.md atau file lain
echo "# Test Auto Deploy" >> README.md

# Commit & push
git add .
git commit -m "Test: Auto-deploy webhook"
git push origin main
```

### **Test 2: Monitor Deployment**

1. **Check** Hostinger hPanel → Git → Deployments
2. **Watch** build logs in real-time
3. **Verify** changes appear di https://manufac.id

### **Test 3: Check Webhook Delivery**

1. **Go to**: https://github.com/omanjaya/sistem-absensi-manufac/settings/hooks
2. **Click** pada webhook yang baru dibuat
3. **Check** "Recent Deliveries" tab
4. **Verify** response 200 OK dari Hostinger

## 🎯 **Expected Results**

### **GitHub Webhook Dashboard:**

- ✅ **Status**: Active webhook with green checkmark
- ✅ **Recent Deliveries**: 200 OK responses
- ✅ **Payload**: JSON data sent to Hostinger

### **Hostinger Deployment Dashboard:**

- ✅ **Auto-Deploy**: Enabled and working
- ✅ **Build Logs**: Successful build process
- ✅ **Live Site**: https://manufac.id updated automatically

### **Development Workflow:**

```bash
# Daily development workflow:
git checkout -b feature/new-feature
# ... make changes
git commit -m "Add new feature"
git push origin feature/new-feature

# Create Pull Request → Review → Merge to main
# Auto-deploy akan trigger setelah merge!
```

## 🔧 **Troubleshooting**

### **Webhook Issues:**

- **Red X** pada webhook: Check URL dan network connectivity
- **Failed deliveries**: Check Hostinger Git service status
- **Timeout**: Check build script performance

### **Build Issues:**

- **npm install fails**: Check Node.js version di Hostinger
- **composer install fails**: Check PHP version & memory limits
- **Permission denied**: Check file permissions & ownership

### **Deploy Issues:**

- **Files not updated**: Check build script copy commands
- **Database errors**: Check .env configuration
- **Site not loading**: Check web server configuration

## 📊 **Monitoring & Logs**

### **GitHub Webhook Logs:**

```
Repository → Settings → Webhooks → [Your webhook] → Recent Deliveries
```

### **Hostinger Deploy Logs:**

```
hPanel → Website → Git → Deployments → View logs
```

### **Live Site Monitoring:**

```bash
# Check if site is up
curl -I https://manufac.id

# Check API health
curl https://manufac.id/api/health
```

## 🎉 **Benefits of Auto-Deploy**

### **Development Benefits:**

- ✅ **No Manual Upload**: Push code → Live automatically
- ✅ **Team Collaboration**: Multiple developers, same workflow
- ✅ **Version Control**: All changes tracked di Git
- ✅ **Easy Rollback**: Revert commits untuk undo changes

### **Production Benefits:**

- ✅ **Always Up-to-Date**: Latest code always live
- ✅ **Consistent Builds**: Same build process setiap deploy
- ✅ **Zero Downtime**: Seamless updates
- ✅ **Automated Testing**: Build fails jika ada errors

### **Business Benefits:**

- ✅ **Faster Delivery**: Features live dalam minutes
- ✅ **Reduced Errors**: Automated process lebih reliable
- ✅ **Better Productivity**: Developer focus on coding
- ✅ **Professional Workflow**: Industry standard practices

---

## 🚀 **Next Steps:**

1. ✅ **Setup GitHub webhook** dengan URL di atas
2. ✅ **Test auto-deploy** dengan small change
3. ✅ **Monitor** build logs untuk memastikan success
4. ✅ **Verify** https://manufac.id working properly
5. ✅ **Document** the workflow untuk tim

**Auto-deploy setup akan memberikan professional development workflow untuk Sistem Absensi Manufac.id!** 🎉

## 📝 **Quick Reference:**

```
Webhook URL: https://webhooks.hostinger.com/deploy/7f59fddf8be7857f24d3de0010477ddf
GitHub Settings: https://github.com/omanjaya/sistem-absensi-manufac/settings/hooks/new
Content Type: application/json
Events: Push events only
```
