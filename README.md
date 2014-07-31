**rackspace-cloud-files-cdn-sync**

Requirements
------------
* PHP >=5.3.3
* Composer

Installation
------------
You must install this through Composer to get dependencies:

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php

# install dependencies
php composer.phar install
```

Basic Use
---------

Either make the script executable...

```bash
chmod +x /path/to/file/rackspace-cdn-sync
/path/to/file/rackspace-cdn-sync
```

... or run it with your interpreter ... 

```bash
bash /path/to/file/rackspace-cdn-sync
```

Settings File
-------------

Place a settings.ini file (in the same folder as the script) to avoid being prompted for settings each time.

Here are some sample contents of the settings.ini file:

```bash
[api]
key=e62435243534574567456887878c6
username=theusername
container=mycontainername
region=DFW
id_endpoint=US
;id_endpoint=UK

[files]
path=/path/to/existing/files

[mysql]
host=localhost
database=wp_db_name
username=user_with_rw
password=xxx
```