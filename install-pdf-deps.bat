@echo off
echo Installing PDF dependencies...
cd frontend
call npm install jspdf html2canvas
echo.
echo Dependencies installed! Please restart the dev server.
pause

