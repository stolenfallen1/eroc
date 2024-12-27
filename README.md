
# CDGCORE

The backend codebase for multiple CDG Software / Applications




## Installation

Before installation always ask Our Beloved Senior and mamaw Jucel Estribo gwapo and make sure you have this tools installed in your machine.

## Git, PHP 7.4.25 and Composer 2.7.2 ( Compatible with PHP 7.4.25)

To check if you have PHP and Composer installed, Open your terminal and input this command
```bash
php -v   
composer --version 
```
If you don't have it installed please ask for you Senior's guidance or ask him regarding the installer.

NOTE: If you have PHP Installed but are using different version. Please do downgrade or upgrade to the specified version. Thanks 😊

TIP: No need to update the composer version since it will automatically do so base on your PHP version.

To check if you have Git installed, if not please do so but usually windows already have it pre installed in your computer.
```bash
git -v 
```
To check if you have associated your GitHub account to your computer.
```bash
git config --global user.name
git config --global user.email
```
To check if you have SSH Configured in your computer.
```bash
ls ~/.ssh
```
To check if you already connected to GitHub
```bash
ssh -T git@github.com
```

If you haven't configured your ssh and connected your GitHub account to your computer well it's your problem! Just kidding haha 😜

Please follow this steps or ask ChatGPT about it

Connect your GitHub username and email
```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```
Verify the configuration
```bash
git config --global --list
```
Generate and SSH Key
```bash
ssh-keygen -t ed25519 -C "your.email@example.com"
```
If naa error, try this one 
```bash
ssh-keygen -t rsa -b 4096 -C "your.email@example.com"
```
After adding the SSH key ( private and public ) use the public and add it to your GitHub account manually. To get your public ssh key 
```bash
cat ~/.ssh/id_rsa.pub
```
Copy and paste the id then navigate to GITHUB => SETTINGS => SSH AND GPG KEYS.

After this you are ready to clone the project!

## Cloning the Codebase

```bash
git clone git@github.com:CEBU-DOCTORS-UNIVERISTY-HOSPITAL/cdgcore.git
```

Create separate branch for yourself during development ( Two ways to do so )

- One using Git Terminal Commands
```bash
cd cdgcore ( navigate to the file )
git branch <branch_name>
```

Switch to the New Branch.
```bash
git checkout <branch_name>
```

- Two doing it manually in the GitHub Repository by navigating to the Following

=> Go To GitHub Repo -> Locate Branches -> Create a New Branch

Then after that in your terminal you have to Run this command
```bash
git fetch
git checkout <branch_name>
```

To get started install the following in the Project repo locally 

```bash
composer install 
php artisan config:clear
php artisan config:cache
php artisan key:generate 
```

After doing this ask your Senior regarding the projects Environment Variables ( .env ) file.

Running the server / project locally
```bash
php artisan serve 
```
Running it on specific ports ( sample )
```bash
php artisan serve --host 10.4.15.12 --port 666
```

After development you can push your changes on to your branch then merge it to the master branch. But always ask your Senior for guidance before merging to master.
```bash
git push origin <branch_name>
```

If any error happens please ask your Senior for guidance.
