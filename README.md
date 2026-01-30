FrankenPHP
================

### 1\. What is it?

[FrankenPHP](https://frankenphp.dev/) is a modern application server for PHP built on top of Caddy (a fast Go web server). It completely removes the need for Nginx, PHP-FPM, and complex container setups, delivering a single binary that handles everything.

### 2\. The Architecture Comparison

The Old Way (Standard PHP):

-   Stack: Nginx → PHP-FPM → PHP Worker.

-   Inefficiency: On every single request, the server has to boot the app, load config, connect to the DB, run code, and then destroy it all.

-   Result: A "Boot Tax" of 30ms--100ms per request.

The New Way (FrankenPHP):

-   Stack: Single Binary (Caddy + PHP).

-   Efficiency: It boots the application once into memory (Worker Mode).

-   Result: Requests are handled instantly. The "Boot Tax" is paid only once at startup.

### 3\. Demo

Clone the repo, then run ./setup.sh (or just the curl command) to get the server binary. We don't store the engine in Git, just the code.

Step A: Run it with a single command  ./frankenphp run --config Caddyfile

We are not running Docker. We are not running Nginx. This is a single 80MB file running my entire web stack natively on my Mac.

Step B: Dashboard

1.  Open http://localhost:9090.

2.  Click the "Hit Server" button.

3.  In a standard stack, we would wait this long for the framework to start on every page load. FrankenPHP skips this step entirely.

### 4\. Key Takeaways

-   Performance: 3x--4x speed increase for heavy frameworks (Laravel/Symfony).

-   Simplicity: Dev/Prod parity. One binary to run anywhere.

-   Modern Features: HTTP/3, HTTPS, and Real-time (Mercure) are built-in by default, not complex add-ons.

### 5\. Production Deployment (The Container)

This demonstrates how we package the application for production using standard Docker containers. This is the industry standard for deploying modern apps.

1\. Create the Dockerfile (Show the Dockerfile we created).

This recipe tells Docker to grab the official FrankenPHP image, copy our code into the /app folder, and---crucially---overwrite the default server config with our custom Caddyfile."

```Dockerfile

FROM  dunglas/frankenphp

ENV  SERVER_NAME=":80"

ENV  FRANKENPHP_CONFIG="worker  ./worker.php"

COPY  .  /app

COPY  Caddyfile  /etc/frankenphp/Caddyfile
```

2\. Build the Image Run this command to package the app into a sealed container:

```Bash

docker  build  -t  my-app-prod  .
```

3\. Run the Container Simulate a production server running the app. We map port 8097 to the container's internal port 80.

```Bash

docker  run  -p  8097:80  my-app-prod
```

4\. The Result

We have packaged the Web Server, the PHP Runtime, and our Application Code into a single, portable Docker Image. To deploy this, we don't need to configure Nginx or FPM on the server. We simply push this image to our registry and tell our servers (Azure/AWS/Kubernetes) to run it. It boots instantly in Worker Mode.

### 6\. Questions

1\. Does it work with WordPress?  Yes, absolutely.

-   Standard Mode: You can run WordPress "out of the box" just like Nginx. It is faster just because Caddy is faster than Nginx.

-   Worker Mode: There is a specific WordPress Worker mode. This keeps WordPress booted in memory. This is game-changing for WordPress performance (which is notoriously slow to boot), but you have to be careful with plugins that rely on "memory leaks" or global state cleanup.

-   Configuration: You lose .htaccess (Apache) files. You must convert those rules to Caddyfile format (which is usually much simpler).

2\. Will it work on Pantheon.io?  Short Answer: No. 

Long Answer: Pantheon (and similar "Managed WordPress" hosts like WP Engine or Kinsta) is a PaaS (Platform as a Service).

-   How they work: They control the entire stack. They give you a container running their tuned version of Nginx and PHP-FPM. You only have permission to upload your PHP code, not replace the web server binary itself.

-   The Conflict: FrankenPHP replaces Nginx and PHP-FPM. Pantheon's infrastructure won't know how to talk to it, and you likely don't have the root permissions to open the ports it needs.

3\. Where should I host FrankenPHP? Since FrankenPHP is a self-contained binary (or Docker container), it fits best on modern "Container" or "VPS" infrastructure:

-   VPS: Azure, AWS, Linode, Digital Ocean droplets (Just upload the binary and run it).

-   Modern PaaS: Fly.io, Railway, or Render. These platforms say "Give us a Dockerfile" or "Give us a Binary," and they run it. FrankenPHP thrives here.

-   Serverless: AWS Lambda (FrankenPHP can actually run inside Lambda to handle requests insanely fast).

4\. Would I need to compile it every time I add changes?  Yes, specifically for the "Single Binary" approach. Because the PHP code is embedded inside the executable file, that file is now immutable (sealed).

-   If you change a line of code: You must run that Docker build command again to create a new binary.

-   Why this is good: It guarantees that "Version 1.0" is identical on every single server. It cannot be tampered with or broken by a missing file on the server.

However, you do NOT do this while developing. Your workflow looks like this:

1.  Development (Local): You use the generic frankenphp binary (like we did in Option A/B). It loads files from your folder in real-time. (Change code -> Refresh browser -> See changes instantly).

2.  Deployment (Production): You run the Build Command (Option C). (Bake the code -> SCP the file to the server -> Restart the service).

5\. Alternative Deployment: The Container Method

-   What it is: Instead of a single binary file, we ship a Docker Image.

-   Why use it: This fits into standard "Enterprise" pipelines (Azure, Kubernetes, AWS ECS) where the infrastructure expects containers, not raw binaries.

-   How it works: We build an image (docker build), push it to a registry, and our servers pull that image to run the application. It runs exactly the same (Worker Mode), just wrapped in a standard container.
