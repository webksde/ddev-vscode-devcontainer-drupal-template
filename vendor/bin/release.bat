@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../consolidation/self-update/scripts/release
php "%BIN_TARGET%" %*
