ARG PHP_IMAGE=scratch
FROM ${PHP_IMAGE} as php

FROM skpr/nginx:1.x
COPY --chown=skpr:skpr --from=php /data /data