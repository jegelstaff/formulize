---
layout: default
permalink: developers/debugging/
---

# Debugging




Make a new branch based on the master branch, and set it up with the trust path files and database dump from a live Formulize installation (this lets you run a local copy of a website, so you can do debugging and other development work).

4. If you simply checkout the _master_ branch and start a Docker container to run it locally, you will end up on the installer. See the If you go through the installer, there are several key things you need to know:
	1. You must create a folder called _/trust/_ at the root of the repository. Then, when the installer you need to specify the trust path, it must be _/var/www/trust_. Additionally, you must create a folder called _/trust/_  folder This folder is mapped to you will need to specify the "trust path" where the Formulize database credentials are stored

## Setting up Formulize and Docker in VSCode on Windows

1. Install [https://code.visualstudio.com/](VSCode).

2. Install [https://docs.docker.com/desktop/install/windows-install/](Docker Desktop for Windows).

2. Add the following extensions to VSCode: Docker, PHP Debug. Also, other recommended extensions are: EditorConfig for VS Code, GitLens, PHP Intelephense, SQLTools MySQL/MariaDB/TiDB (depends on SQLTools)

	![VSCode Extensions](../../images/vscode-extensions.PNG)

3. In the Checkout the _master_ branch

	![Checkout the master branch](../../images/checkout-master.PNG)

