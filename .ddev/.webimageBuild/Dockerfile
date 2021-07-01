
ARG BASE_IMAGE
FROM $BASE_IMAGE

ARG username
ARG uid
ARG gid
RUN (groupadd --gid $gid "$username" || groupadd "$username" || true) && (useradd  -l -m -s "/bin/bash" --gid "$username" --comment '' --uid $uid "$username" || useradd  -l -m -s "/bin/bash" --gid "$username" --comment '' "$username")

RUN if command -v composer >/dev/null 2>&1 ; then export XDEBUG_MODE=off && (composer self-update --2 || composer self-update --2 ) && chmod 777 /usr/local/bin/composer;  fi
