Drupal Example
==============

## Usage

```bash
export NAMESPACE=project1-dev
export VERSION=0.0.1

# OPTIONAL - Create the namespace if it does not exist.
kubectl create ns $NAMESPACE

# Update the images which will be deployed.
# https://kubectl.docs.kubernetes.io/pages/app_management/container_images.html
kustomize edit set image nginx:$VERION
kustomize edit set image php:$VERION

# Rollout the new version of the application.
# https://kubectl.docs.kubernetes.io/pages/app_management/apply.html
kubectl -n $NAMESPACE apply -k .

# Wait until the rollout has been completed and then execute post deployment steps.
kubectl -n $NAMESPACE rollout status -w deployment/drupal
kubectl -n $NAMESPACE exec deployment/drupal -- drush cr
```