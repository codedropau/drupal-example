FROM skpr/php-cli:7.3-1.x as build

RUN composer global require hirak/prestissimo

RUN composer create-project drupal/recommended-project . --no-interaction && \
    composer require drush/drush:^10 && \
    mv web app

ADD dockerfiles/settings.k8s.php app/sites/default/settings.k8s.php
ADD dockerfiles/settings.php app/sites/default/settings.php
ADD dockerfiles/services.yml app/sites/default/services.yml

FROM skpr/php-fpm:7.3-1.x as run
COPY --chown=skpr:skpr --from=build /data /data