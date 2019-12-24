Drupal Tutorial
===============

@todo, Goals for this repository.

## Prerequisites

* [Docker Compose](https://docs.docker.com/compose/install)
* [Kubectl](https://kubernetes.io/docs/tasks/tools/install-kubectl)
* [Kustomize](https://github.com/kubernetes-sigs/kustomize/blob/master/docs/INSTALL.md)

## Step 1. Setup a Kubernetes cluster

This tutorial includes a local Kubernetes cluster.

In this step we will provision it.

Using Docker Compose, "up" the cluster.

```bash
docker-compose up -d
```

Next we will configure our command line so the Kubernetes command line (`kubectl`) can connect to this cluster.

`kubectl` uses the _$KUBECONFIG_ environment variable to discover the file which contains cluster connection configuration.

This configuration is automatically created by the K3s service running inside the Docker Compose stack.

We can now set the environment variable.

```bash
export KUBECONFIG=$(pwd)/.kube/config
```

Let's verify you can connect to the cluster.

```bash
kubectl get pods --all-namespaces
```

The above command should yield a result similiar to the following:

```bash
$ kubectl get pods --all-namespaces

NAMESPACE     NAME                                      READY   STATUS    RESTARTS   AGE
kube-system   local-path-provisioner-58fb86bdfd-wxqhg   1/1     Running   0          35s
kube-system   metrics-server-6d684c7b5-m7hss            1/1     Running   0          35s
kube-system   coredns-d798c9dd-plr6c                    0/1     Running   0          35s
```

The Docker Compose stack also comes with a Docker Registry.

Now we need to ensure we have a consistent DNS entry inside the cluster and on your local command line.

Add the following record to you local `/etc/hosts` file.

`127.0.0.1      registry.drupal-tutorial.svc.cluster.local`

In the next step we will package the Drupal application and push it to this registry.

## Step 2. Package

```bash
export REGISTRY=registry.drupal-tutorial.svc.cluster.local:5000/project1

# This variable is generally derived from the command: git describe --tags --always
export VERSION=0.0.1

# Builds the Drupal application using Composer.
docker build -t ${REGISTRY}/php:${VERSION} -f dockerfiles/php.dockerfile .

# Builds the Nginx container by copying the application from the PHP image.
docker build -t ${REGISTRY}/nginx:${VERSION} -f dockerfiles/nginx.dockerfile --build-arg PHP_IMAGE=${REGISTRY}/php:${VERSION} .

# Push the images to the registry.
docker push ${REGISTRY}/php:${VERSION}
docker push ${REGISTRY}/nginx:${VERSION}
```

In the next step we will be deploying the Drupal application.

## Step 3. Deploy

```bash
export NAMESPACE=project1-dev

# OPTIONAL - Create the namespace if it does not exist.
kubectl create ns $NAMESPACE

# Update the images which will be deployed.
# https://kubectl.docs.kubernetes.io/pages/app_management/container_images.html
kustomize edit set image nginx=$REGISTRY/nginx:$VERSION
kustomize edit set image php=$REGISTRY/php:$VERSION

# Rollout the new version of the application.
# https://kubectl.docs.kubernetes.io/pages/app_management/apply.html
kubectl -n $NAMESPACE apply -k .
```

We can now verify the application by running the following command:

```bash
$ kubectl -n $NAMESPACE get -k .

NAME               DATA   AGE
configmap/drupal   0      8m26s

NAME             TYPE        CLUSTER-IP     EXTERNAL-IP   PORT(S)    AGE
service/drupal   ClusterIP   10.43.106.13   <none>        8080/TCP   8m26s

NAME                     READY   UP-TO-DATE   AVAILABLE   AGE
deployment.apps/drupal   3/3     3            3           8m26s

NAME                        SCHEDULE   SUSPEND   ACTIVE   LAST SCHEDULE   AGE
cronjob.batch/drupal-cron   @hourly    False     0        <none>          8m26s

NAME                                         REFERENCE           TARGETS           MINPODS   MAXPODS   REPLICAS   AGE
horizontalpodautoscaler.autoscaling/drupal   Deployment/drupal   1%/90%, 0%/300%   2         4         3          8m26s

NAME                                     STATUS   VOLUME                                     CAPACITY   ACCESS MODES   STORAGECLASS   AGE
persistentvolumeclaim/drupal-private     Bound    pvc-9e2725d9-7936-47dd-9ee8-8b773440f1a6   20Gi       RWO            local-path     8m26s
persistentvolumeclaim/drupal-public      Bound    pvc-11828727-0ca8-4941-beb4-3fd9fadeea5a   20Gi       RWO            local-path     8m26s
persistentvolumeclaim/drupal-temporary   Bound    pvc-d8663c34-81f4-4c52-bc7c-c2eb4e1030eb   20Gi       RWO            local-path     8m26s

```

The deployment will be complete once:

* The _Deployment_ has 3/3 _Ready_ replicas.
* The _PersistentVolumeClaims_ have becomes _Bound_.

Now we configure the application to talk to backend resources.

## Step 4. Configure

In this section we will be configuring our application to connect to the MySQL instance already setup on the cluster.

We have the MySQL instace separate to emulate a production setup where the MySQL database is hosted on a managed service.

The [Twelve-Factor App](https://12factor.net) manifesto recommends applications store config in the environment, not the application.

We also don't want to use environment variables for [security reasons](https://diogomonica.com/2017/03/27/why-you-shouldnt-use-env-variables-for-secret-data).

To achieve this for Drupal site we:

* Mount a file with our database credentials: `/etc/drupal/config.json`
* Load it using a helper function (see [settings.k8s.php](/dockerfiles/settings.k8s.php)).

This is all achieved by a Kubernetes ConfigMap, which we are going to _PATCH_ with our `config.json` file.

```bash
kubectl -n $NAMESPACE patch configmap -p '{"data": {"config.json": "{\"mysql.database\": \"drupal\", \"mysql.username\": \"root\", \"mysql.password\": \"password\", \"mysql.hostname\": \"nonprod.mysql\"}"}}' drupal
```

## Step 5. Verify

* Drush site install it up
* Port forward
* Check it out

## OPTIONAL - Add to a deployment pipeline

The following is a snippet which can be used as part of a deployment pipeline.

```bash
export NAMESPACE=project1-dev
export REGISTRY=registry.drupal-tutorial.svc.cluster.local:5000/project1
export VERSION=$(git describe --tags --always)

kustomize edit set image nginx=$REGISTRY:$VERSION
kustomize edit set image php=$REGISTRY:$VERSION

kubectl -n $NAMESPACE apply -k .

kubectl -n $NAMESPACE rollout status -w deployment/drupal
kubectl -n $NAMESPACE exec deployment/drupal -- drush cr
```