@echo off
echo Copying widget assets to public directory...

if not exist "public\css\widget" mkdir "public\css\widget"
if not exist "public\js\widget" mkdir "public\js\widget"

copy /Y "resources\css\widget\wheel.css" "public\css\widget\wheel.css"
xcopy /E /I /Y "resources\js\widget\*" "public\js\widget\"

echo Done! Files copied to public directory.
pause

