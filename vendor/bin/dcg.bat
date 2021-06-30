@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../chi-teck/drupal-code-generator/bin/dcg
php "%BIN_TARGET%" %*
