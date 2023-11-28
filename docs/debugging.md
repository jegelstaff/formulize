---
layout: default
permalink: developers/debugging/
---

# Debugging with XDebug

If you have setup a [local development environment using Docker](../development_environment/), then you can use XDebug with PHP to do live debugging in your local environment.

(You can also setup XDebug to do remote debugging on a server elsewhere, and all sorts of other fun things, but this page just focuses on the local Docker environment.)

The Docker environment includes XDebug by default. Your IDE should more or less do the rest. We have detailed instructions for VS Code on Windows.

## Setting up XDebug with Formulize and Docker in VS Code on Windows

1. Install [VS Code](https://code.visualstudio.com/).

2. Install [Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/).

3. Add the following extensions to VS Code: Docker, PHP Debug. Also, other recommended extensions are: EditorConfig for VS Code, GitLens, PHP Intelephense, SQLTools MySQL/MariaDB/TiDB (depends on SQLTools)

	![VS Code Extensions](../../images/vscode-extensions.PNG)

4. Open the folder for your [local development environment](../development_environment/).

5. Find the docker-compose.yml file, right click on it, and select 'Compose Up.'

    ![Running Docker Compose Up](../../images/vscode-compose-up.PNG)

6. After a few moments, the Docker containers will be running. You will know it's all done when you get a response browsing to [http://localhost:8080](http://localhost:8080)

    ![Docker is working](../../images/vscode-docker-working.PNG)

7. Switch to the Run and Debug view by clicking the icon in the Activity Bar on the side.

    ![Switch to Run and Debug view](../../images/vscode-run-and-debug.PNG)

8. Click the play icon to start XDebug.

    ![Click the play icon](../../images/vscode-play-icon.PNG)

9. Open a file, set breakpoints, and browse to [http://localhost:8080](http://localhost:8080) in your web browser. VS Code will show you the value of variables, let you step through the code, etc.



