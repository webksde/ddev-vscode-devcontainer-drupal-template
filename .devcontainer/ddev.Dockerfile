# [Choice] PHP version: 8, 8.0, 7, 7.4, 7.3
ARG VARIANT="8.0"
FROM mcr.microsoft.com/vscode/devcontainers/php:0-${VARIANT}
