MariaDB stores metadata; MinIO stores bytes; Redis stores disposable shared 
state; RabbitMQ carries small messages; workers perform slow processing.

two containers in each web pod:

    Nginx: serves the React/Vite build and forwards PHP requests to PHP-FPM.
    PHP-FPM/Laravel: API, session authentication, upload handling.


Browser
  |
  v
Nginx Ingress
  |
  v
Laravel web pods (Nginx + PHP-FPM)
  |        |        |        |
  |        |        |        +--> RabbitMQ: “file uploaded”
  |        |        +-----------> MinIO: actual uploaded bytes
  |        +--------------------> Redis: sessions and cache
  +-----------------------------> MariaDB: file metadata

RabbitMQ worker pod
  |
  +--> marks file record as complete
  
  
  What each service owns
Component	Purpose
React	Small upload/list/download page
Laravel	Login, authorization, API, upload handling
MariaDB	Users and file metadata
Redis	Shared login sessions across Laravel replicas
MinIO	Actual file bytes
RabbitMQ	“A file was uploaded” messages
Worker	Demonstrates background work by updating status
Nginx	Receives browser traffic and passes PHP requests to Laravel
Kubernetes	Starts, connects, and scales all of the above


Start Minikube and enable its Ingress controller:

minikube start --cpus=6 --memory=8192
minikube addons enable ingress


Build the application image inside Minikube:

minikube image build -t media-vault:dev .


Create the namespace and then create your local-only Secret:

kubectl apply -f infra/k8s/all.yaml --dry-run=client -o yaml >/dev/null

kubectl create namespace media --dry-run=client -o yaml | kubectl apply -f -

php artisan key:generate --show


Copy the output of php artisan key:generate --show, then use it here:

kubectl -n media create secret generic media-secrets \
  --from-literal=APP_KEY='base64:PASTE_THE_GENERATED_KEY_HERE' \
  --from-literal=DB_PASSWORD='local-db-password' \
  --from-literal=MARIADB_ROOT_PASSWORD='local-root-password' \
  --from-literal=AWS_ACCESS_KEY_ID='minioadmin' \
  --from-literal=AWS_SECRET_ACCESS_KEY='local-minio-password' \
  --from-literal=RABBITMQ_USER='media_user' \
  --from-literal=RABBITMQ_PASSWORD='local-rabbit-password'


Apply everything:

kubectl apply -f infra/k8s/all.yaml

kubectl -n media get pods --watch


Wait until MariaDB is running, then run the Laravel migrations:

kubectl -n media exec deployment/media-web -- \
  php artisan migrate --force


Wait for the bucket creation Job:

kubectl -n media wait \
  --for=condition=complete \
  job/minio-create-bucket \
  --timeout=180s


use:
minikube ip

in:
etc/hosts
<MINIKUBE_IP> media.test

http://media.test
http://media.test/files


kubectl -n media get pods -l app=media-web
kubectl -n media logs deployment/media-web --tail=100
kubectl -n media logs deployment/media-worker --tail=100

open rabbitmq's management ui:
kubectl -n media port-forward service/rabbitmq 15672:15672
http://localhost:15672

Username: media_user
Password: local-rabbit-password

open MinIO's console
kubectl -n media port-forward service/minio 9001:9001
http://localhost:9001

Username: minioadmin
Password: local-minio-password


Final success test:

The simple app is working when this happens:

    Register at http://media.test.
    Go to /files.
    Upload any file smaller than 50 MiB.
    The page initially shows:

Status: pending

    Refresh the list.
    The worker has consumed the RabbitMQ message and the status becomes:

Status: complete

    Click Download.
    In MinIO, confirm the actual file is in:

media/uploads/<your-user-id>/<generated-file-name>

    Scale Laravel web pods and confirm your login remains valid:

kubectl -n media scale deployment/media-web --replicas=3
