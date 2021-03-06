# Symfony2 Deploy Bundle

**WIP**

[![Build Status](https://secure.travis-ci.org/jordillonch/JordiLlonchDeployBundle.png?branch=master)](http://travis-ci.org/jordillonch/JordiLlonchDeployBundle)

## What it is?

This bundle aims to be a deploy system for your projects.

It is a Symfony2 Bundle but it can be used to deploy several kind of projects.

The bundle provides some commands to automatize deploy process. Here are main commands:

* **Initialize**: Prepare deployer and remote servers creating a directories structure to host new code.
* **Download**: Download code from repository, adapt, warn up… and ship it to remote servers in order to put new code to production.
* **Code to production**. Deploy new code to production atomically and reload web server, app…
* **Migrate**: Run database migrations.
* **Rollback**. Return back to previous deployed version.

Deployer have zones configured to deploy new code.

Zones are a project and environment (e.g. prod_api, our project Api for the production environment).

Deployer uses GitHub repository, a configured branch, and HEAD as a target to deploy.


You can use this bundle adding it to your projects via composer (see installation section) but my recommendation is that you create a new project to deploy because you may be want to not have your production configurations in your repository project. So it is a good idea to delegate add productions configuration to the deploy system.


Furthermore this bundle offers **helpers** to automatize common operations like install *composer* dependencies, do a *cache warm up* for *Symfony2* projects, refresh *php-fpm* after put code to production, get url to *GitHub* with diffs of new deployed code...


## How it works?

There are two basic ideas that allows to deploy several code versions and put one of them to production and rollback between them.


When you want to deploy new code you have to do a "*download*" operation. That operation clones code from your git repository to a new directory (e.g. 20130704_180131_a618a56b08549794ec4c9d5db29058a01a58977f) then do adaptations to the code. Adaptations are the step where configurations are added, app cache is warned up, dependencies are downloaded and installed, symlinks are created to shared directories…

Shared directories are directories where there are data that you want to keep between deploys. (e.g. logs, reports, generated images...)

After that, code is copied to configured servers. Ssh authorized keys are used to allow copy and execute commands to remote servers.


Then, when you want to use last downloaded code to production you have to execute "*code to production*" operation. This operation modify a symlink to the directory where last version are downloaded.

Usually, after that you should restart webserver or php-fpm.


*Note*: These ideas are taken from Capistrano.


## Quick start


### Create a new Symfony 2.3 project


```sh
php composer.phar create-project symfony/framework-standard-edition path/ 2.3.0
```


### Install the bundle

Add following lines to your `composer.json` file:

```json
"require": {
  "jordillonch/deploy-bundle": "dev-master"
},
"minimum-stability": "dev",
```
Execute:

```sh
php composer.phar update
```

Add it to the `AppKernel.php` class:

```php
new JordiLlonch\Bundle\DeployBundle\JordiLlonchDeployBundle(),
```



### Configure general settings and a zone

`app/config/parameters.yml`

```yml
jordi_llonch_deploy:
    config:
        project: MyProject
        vcs: git
        servers_parameter_file: app/config/parameters_deployer_servers.yml
        local_repository_dir: /home/deploy/local_repository
        clean_max_deploys: 7
        ssh:
            user: myuser
            public_key_file: '/home/myuser/.ssh/id_rsa.pub'
            private_key_file: '/home/myuser/.ssh/id_rsa'
            private_key_file_pwd: 'mypassword'
    zones:
        prod_myproj:
            deployer: myproj
            environment: prod
            checkout_url: 'git@github.com:myrepo/myproj.git'
            checkout_branch: master
            repository_dir: /var/www/production/myproj/deploy
            production_dir: /var/www/production/myproj/code
```

`app/config/parameters_deployer_servers.yml`

```yml
prod_myproj:
    urls:
        - deploy@testserver1:8822
        - deploy@testserver2:8822
```

* Note: Servers urls are in other file (`parameters_deployer_servers.yml`) because this file contains a dynamic configuration that would be changed in a scalable environment like AWS.


### Create a deploy class to myproj

* First create your own bundle:

```sh
php app/console generate:bundle --namespace=MyProj/DeployBundle --dir=src --no-interaction
```

* Create your own class to deploy:

`src/MyProj/DeployBundle/Service/Test.php:`


```php
<?php

namespace MyProj/DeployBundle/Service;

use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;

class Test extends BaseDeployer
{
    public function downloadCode()
    {
        $this->logger->debug('Downloading code...');
        $this->output->writeln('<info>Downloading code...</info>');
        $this->downloadCodeVcs();

        $this->logger->debug('Adapting code');
        $this->output->writeln('<info>Adapting code...</info>');
        // Here you can download vendors, add productions configuration,
        // do cache warm up, set shared directories...

        $this->logger->debug('Copying code to zones...');
        $this->output->writeln('<info>Copying code to zones...</info>');
        $this->code2Servers();
    }

    public function downloadCodeRollback()
    {
    }

    protected function runClearCache()
    {
        $this->logger->debug('Clearing cache...');
        $this->output->writeln('<info>Clearing cache...</info>');
        $this->execRemoteServers('sudo pkill -USR2 -o php-fpm');
    }
}
```

* Add your deploy class as a service with tag: `jordi_llonch_deploy`

```xml
<service id="myproj.deployer.test" class="MyProj/DeployBundle/Service/Test">
    <tag name="jordi_llonch_deploy" deployer="myproj"/>
</service>
```

* `myproj` is used in the configuration as `deployer` value.


### Allow ssh access to remote servers

It is necessary to add the public key of the deploy user to `.ssh/authorized_keys` in remote servers in order to allow access to the deploy system.


### Initialize

After configure the deployer you have to do the initialization.

```sh
app/console deployer:initialize --zones=prod_myproj
```


### Download

Now you can download code from your repository and copy to your servers.

```sh
app/console deployer:download --zones=prod_myproj
```


### Code to production

After download you just need to put code into production.

```sh
app/console deployer:code2production --zones=prod_myproj
```

### Rollback

If there is any problem you can roll back to a previous version. See rollback command help.



## Commands

### initialize

Prepare deployer and remote servers creating a directories structure to host new code.

```sh
app/console deployer:initialize --zones=[zone1,zone2...]
```


### download

Download code from repository, adapt, warn up… and ship it to remote servers in order to put new code to production.

```sh
app/console deployer:download --zones=[zone1,zone2...]
```

### code2production

Deploy new code to production atomically and reload web server, app...

```sh
app/console deployer:code2production --zones=[zone1,zone2...]
```
### Migrate
Perform migrations on your enviroment database
```sh
app/console deployer:migrate [version_number] --zones=prod_myproj
```

### syncronize

Ensure that all downloaded versions of code are copied to all servers.
Useful when you add a new server to a zone. If you not syncronize the new server the rollback operation will break the code in the new server.

```sh
app/console deployer:syncronize --zones=prod_myproj
```


### rollback

If there is any problem and you need to roll back to a previous version you have two options:


#### Using number of steps to roll back

```sh
app/console deployer:rollback execute [steps_backward] --zones=[zone1]
```

* If you want to roll back to a previous version `steps_backward` should be `1`.


#### Referencing to a concrete version

1) Ask deploy for available versions to rollback.

```sh
app/console deployer:rollback list --zones=[zone1]
```

2) Execute rollback to specific version

```sh
app/console deployer:rollback execute [version] --zones=[zone1]
```


### status

Shows running version and last downloaded version prepared to put to production.

```sh
app/console deployer:status [--zones=[zone1,zone2...]]
```


### configure

Configure remote servers for zones. Useful for automatize scaling.

```sh
app/console deployer:configure zone [add, set, rm, list, list_json] [url]
```


### clean

Remove old code. Left `clean_max_deploys` deploys.

```sh
app/console deployer:clean
```


### exec2servers

Executes command passed as argument to all configured servers.

```sh
app/console deployer:exec2servers [command]
```


## Configuration

Deployer configurations are set in `parameters.yml`.

You must set general configurations and zones.


### General configuration

`app/config/parameters.yml`

```yml
jordi_llonch_deploy:
    config:
        project: MyProject
        vcs: git
        local_repository_dir: /home/deploy/deploy_repository
        clean_max_deploys: 7
        sudo: true
        ssh:
            user: myuser
            public_key_file: '/home/myuser/.ssh/id_rsa.pub'
            private_key_file: '/home/myuser/.ssh/id_rsa'
            private_key_file_pwd: 'mypassword'
```

#### project

Your project name.


#### mail_from

Mail from.


#### mail_to

Mail to send emails about deployments. It is an array.


#### servers_parameter_file

Path where the servers parameters file is. A common configuration could be `app/config/parameters_deployer_servers.yml`.

Servers urls are in this file because it contains a dynamic configuration that would be changed in a scalable environment like AWS by some script.


#### local_repository_dir

Directory where deployer clone your repositories, adapt code and save data about versions in the deploy system.


#### clean_max_deploys

Used in the clean command to remove previous downloaded versions. Left `clean_max_deploys` downloaded versions.


#### sudo

Add `sudo` to all commands send to remote servers. If you want to use, you should set your deploy user to sudoers on all remote servers with NOPASSWD:

`/etc/sudoers`:

```
deployer_user	ALL=(ALL) NOPASSWD: ALL
```


#### ssh

Ssh configuration to establish connections on remote servers to execute commands.

Here an example of configuration:

```yml
ssh:
    proxy: cli
    user: jllonch
    password: 'mypassword'
    public_key_file: '/home/myuser/.ssh/id_rsa.pub'
    private_key_file: '/home/myuser/.ssh/id_rsa'
    private_key_file_pwd: 'mykeypassword'
```

##### proxy

Client to use to execute remote commands.

It could be:

* `pecl`: php-ssh2 extension
* `cli`: console ssh command

If it is not defined it will try to use `pecl` if it is installed, otherwise it will use `cli`.

##### user

Username used in auth by password and auth by public key.

##### password

Password used in auth by password.

##### public_key_file

(Optional) Public key file path. If not defined will use current user default public key usually stored in ~/.ssh/id_rsa.pub

##### private_key_file

(Optional) Private key file path. If not defined will use current user default private key usually stored in ~/.ssh/id_rsa.pub

##### private_key_file_pwd

Private key password.


#### helper

Helper parameters that can be get in your deploy class.


### Zones configuration

You need to set a minimum of one zone. Here is created your zone `prod_myproj`:

`app/config/parameters.yml`

```
jordi_llonch_deploy:
...
    zones:
        prod_myproj:
            deployer: myproj
            environment: prod
            checkout_url: 'git@github.com:myrepo/myproj.git'
            checkout_branch: master
            checkout_proxy: true
            repository_dir: /var/www/production/myproj/deploy
            production_dir: /var/www/production/myproj/code
            custom:
                my_key1: value1
                my_key2: value2
```

`app/config/parameters_deployer_servers.yml`

```yml
prod_myproj:
    urls:
        - deploy@testserver1:8822
        - deploy@testserver2:8822
```

#### deployer

Name of the service used to deploy the zone.

```xml
<service id="myproj.deployer.test" class="MyProj/DeployBundle/Service/Test">
    <tag name="jordi_llonch_deploy" deployer="myproj"/>
</service>
```


#### environment

Environment.


#### urls

Remote servers where deployed code will be copied and set to production.

Format is: `[user]@[server]:[port]`


#### checkout_url

Url to git repository.


#### checkout_branch

Git branch to clone.


#### checkout_proxy

Deployer always clone a repository for every deploy. If you want to avoid to download from remote server you can clone your repository locally, then set `checkout_url` to your local local repository (`file:///home/deploy/proxy_repositories/myproj`). Then before download operation, deployer execute a `git pull` to your local repository.


#### repository_dir

Path on remote servers where to copy new deployed code.


#### production_dir

Path that is updated by deployer when new deployed code is set to production. So you must set this path to your webserver as a root path.


#### custom

Custom parameters that can be get in your deploy class.


#### helper

Helper parameters that must be set to use some helpers.

If helper parameters are set in general configuration those configuration are merged here.


## Helpers

Hepers automatize common operations like to install *composer* dependencies, to do a *cache warm up* for *Symfony2* projects, to restart *php-fpm* after to put code to production, to get url to *GitHub* with differences of new deployed code...


### How use it?

You can use `Helpers` in your Deployer classes to execute common operartions.

Here and example:

`src/MyProj/DeployBundle/Service/Test.php:`


```php
<?php

namespace MyProj/DeployBundle/Service;

use JordiLlonch\Bundle\DeployBundle\Service\BaseDeployer;

class Test extends BaseDeployer
{
    public function initialize()
    {
        parent::initialize();

        // Shared dirs
        $this->getHelper('shared_dirs')->initialize('logs');
    }

    public function downloadCode()
    {
        $this->logger->debug('Downloading code...');
        $this->output->writeln('<info>Downloading code...</info>');
        $this->downloadCodeVcs();

        $this->logger->debug('Adapting code');
        $this->output->writeln('<info>Adapting code...</info>');

        // composer
        $this->getHelper('composer')->install();
        $this->getHelper('composer')->executeInstall();

        // cache warm:up
        $this->getHelper('symfony2')->cacheWarmUp();

        // shared directories
        $this->getHelper('shared_dirs')->set('app/logs', 'logs');

        $this->logger->debug('Copying code to zones...');
        $this->output->writeln('<info>Copying code to zones...</info>');
        $this->code2Servers();
    }

    public function downloadCodeRollback()
    {
    }

    protected function runClearCache()
    {
        $this->logger->debug('Clearing cache...');
        $this->output->writeln('<info>Clearing cache...</info>');
        $this->getHelper('phpfpm')->refresh();
    }
}
```

* As you can see we used some methods prefixed by `helper…` to install composer dependencies, doing cache warm up, setting shared directories and restarting php-fpm.


### Provided helpers

#### SharedDirs

Easy way to manage shared directories.

```php
$this->getHelper('shared_dirs')->initialize($path)
```

* Initialize given path in the shared directory.

```php
$this->getHelper('shared_dirs')->set($pathInAppToLink, $pathInSharedDir)
```

* Set shared directory for a given path in the project that will be removed and replaced by a symlink to given path to shared directory.

#### PhpFpm

Provides methods to restart php-fpm gracefully.

```php
$this->getHelper('phpfpm')->refresh()
```

* Refresh php-fpm gracefully but you must configure your webserver (e.g. Nginx) to retry the request.

```php
$this->getHelper('phpfpm')->refreshCommand()
```

* Command used to reload php-fpm


#### Composer

Helpers to manage composer installation and some commands.

```php
$this->getHelper('composer')->install()
```

* Install composer.phar in the new repository dir.

```php
$this->getHelper('composer')->executeInstall()
```

* Executes composer install in the new repository dir.
* If environment is dev or test --dev parameter is added to composer install
* If environment is prod --no-dev parameter is added to composer install


#### Symfony2

Helper to manage cache warmup and other symfony commands like assets:dump and assetic:dump

`$this->getHelper('symfony2')->cacheWarmupOnServers()`

* Do a cache:warmup for production environment. This operation should be executed after code2Servers() operation.


##### Deprecated:

```php
$this->getHelper('symfony2')->cacheWarmUp()
```

* This warmup it is not a secure operation because serialized data could be corrupted.

```php
$this->getHelper('symfony2')->assetsDump()
```

* Do a assets:dump for production environment

```php
$this->getHelper('symfony2')->asseticDump()
```

* Do a assetic:dump for production environment

```php
$this->getHelper('symfony2')->clearDoctrineMetadataCache();
```

* Clear doctrine metadata cache

```php
$this->getHelper('symfony2')->clearDoctrineQueryCache();
```

* Clear doctrine query cache

```php
$this->getHelper('symfony2')->clearDoctrineResultCache();
```

* Clear doctrine result cache


#### GitHub

Useful methods to have feedback of your deploy in GitHub.

```php
$this->getHelper('github')->getCompareUrl($gitUidFrom, $gitUidTo)
```

* Give an url comparing two commits
* You must set your http url to your GitHub repository in jordi_llonch_deploy.zones parameters:

```
helper:
    github:
        url: https://github.com/YourUser/Repository
```

```php
$this->getHelper('github')->getCompareUrlFromCurrentCodeToNewRepository()
```

* Give an url comparing commits between current running code and the new downloaded code.


#### HipChat

Provides a method to send messages to a room in a HipChat.

```php
$this->getHelper('hipchat')->send($msg, $color='purple')
```

* Send a message to a given room
* You must set your token and room_id to your HipChat in jordi_llonch_deploy.general parameters:

```
helper:
    hipchat:
        token: your_token
        room_id: your_room_id
```
#### Files
Provides several methods to work with files.
* Replace strings that matches the given regular expression in the given array of files:

```php
 $this->getHelper('files')->filesReplacePattern(
              array($this->getLocalNewRepositoryDir() . '/app/config/parameters.yml'),
              '/database_user: developer_user/',
              'database_user: production_user'
  );
```

* Copy files:

```php
$this->getHelper('files')->copyFile(
    $this->getLocalNewRepositoryDir() . '/app/config/parameters.yml.dist',
    $this->getLocalNewRepositoryDir() . '/app/config/parameters.yml'
 );
```

#### Supervisord
Provides a method to restart supervisord programs on remote servers
* Restart a certain program

```php
  $this->getHelper('supervisord')->restart('program');
```


## TODO

- Tests
- Ssh with Process component


## Author

- Jordi Llonch - llonch.jordi at gmail dot com
- Miquel Company - miquel at solilokiam dot com


## License

DeployBundle is licensed under the MIT License. See the LICENSE file for full details.

