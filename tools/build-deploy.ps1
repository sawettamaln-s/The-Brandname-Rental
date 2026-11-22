# สร้างไฟล์ zip สำหรับอัปโหลดขึ้นโฮสต์ (แตกไฟล์ใน htdocs ได้ทันที)
# ใช้: powershell -ExecutionPolicy Bypass -File tools\build-deploy.ps1
# ผลลัพธ์: dist\brandname-rental-htdocs.zip
# - เนื้อหาของโฟลเดอร์ project\ (ไม่รวม config.local.php และไฟล์ที่ผู้ใช้อัปโหลด)
# - database\schema.sql คัดลอกไปไว้ที่ includes\schema.sql เพื่อให้โหมดเดโมรีเซ็ตข้อมูลได้
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$stage = Join-Path $root 'dist\stage'
$zip = Join-Path $root 'dist\brandname-rental-htdocs.zip'

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force $stage | Out-Null
Copy-Item (Join-Path $root 'project\*') $stage -Recurse -Force
Copy-Item (Join-Path $root 'project\.htaccess') $stage -Force -ErrorAction SilentlyContinue

# ไม่ส่งค่าตั้งค่าในเครื่องและไฟล์ที่ผู้ใช้อัปโหลดขึ้นโฮสต์
Remove-Item (Join-Path $stage 'includes\config.local.php') -Force -ErrorAction SilentlyContinue
Get-ChildItem (Join-Path $stage 'uploads') -Force | Where-Object { $_.Name -ne '.htaccess' } | Remove-Item -Recurse -Force
New-Item -ItemType Directory -Force (Join-Path $stage 'uploads\slips') | Out-Null

Copy-Item (Join-Path $root 'database\schema.sql') (Join-Path $stage 'includes\schema.sql') -Force

# ใช้ tar ของ Windows สร้าง zip เพราะ Compress-Archive ของ PowerShell 5 ใช้ \ ในชื่อไฟล์ ทำให้แตกไฟล์บนเซิร์ฟเวอร์ Linux ผิด
if (Test-Path $zip) { Remove-Item $zip -Force }
& tar.exe -a -c -f $zip -C $stage .
if ($LASTEXITCODE -ne 0) { throw 'สร้าง zip ไม่สำเร็จ' }
Remove-Item $stage -Recurse -Force
Write-Host "สร้างแล้ว: $zip"
